<?php
namespace App\Helpers;

use App\Player;
use DateInterval;
use DateTimeImmutable;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha384;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\Validator;
use Illuminate\Support\Str;

class JwtHelper
{
    const Cookie = 'op_jwt';

    const Mappings = [
        'user'          => 'usr',
        'license'       => 'lcs',
        'discord'       => 'dsc',
        'name'          => 'nme',
        'tokens'        => 'tkn',

        'flash_error'   => 'err',
        'flash_success' => 'scs',
        'lastVisit'     => 'lvs',

        'username'      => 'unm',
        'global_name'   => 'gnm',
        'avatar'        => 'avt',
        'discriminator' => 'dcr',
    ];

    /**
     * JWT signing secret.
     *
     * @var ?string
     */
    private static ?string $secret = null;

    /**
     * Logged in user.
     *
     * @var ?Player
     */
    private static ?Player $user = null;

    /**
     * Has the jwt data been changed.
     *
     * @var bool
     */
    private static bool $changed = false;

    /**
     * JWT claims.
     *
     * @var ?array
     */
    private static ?array $claims = null;

    /**
     * Load the JWT signing secret.
     */
    private static function loadSecret()
    {
        $secret = base_path(sprintf('envs/%s/.jwt', CLUSTER));

        if (file_exists($secret)) {
            self::$secret = file_get_contents($secret);

            return;
        }

        $directory = dirname($secret);

        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $fp = fopen($secret, 'x');

        if (! $fp) {
            throw new \Exception('failed to open jwt key');
        }

        try {
            self::$secret = base64_encode(random_bytes(48));

            fwrite($fp, self::$secret);
            fflush($fp);
        } finally {
            fclose($fp);
        }
    }

    /**
     * Map keys to shorted versions.
     */
    private static function map(array $unmapped): array
    {
        $mapped = [];

        foreach ($unmapped as $key => $val) {
            if (! $val) {
                continue;
            }

            $mp = self::Mappings[$key] ?? $key;

            if (is_array($val)) {
                $mapped[$mp] = self::map($val);
            } else {
                $mapped[$mp] = $val;
            }
        }

        return $mapped;
    }

    /**
     * Reverse mapping of keys to shorted versions.
     */
    private static function unmap(array $mapped): array
    {
        $unmapped = [];

        foreach ($mapped as $mp => $val) {
            if (! $val) {
                continue;
            }

            $key = array_search($mp, self::Mappings) ?: $mp;

            if (is_array($val)) {
                $unmapped[$key] = self::unmap($val);
            } else {
                $unmapped[$key] = $val;
            }
        }

        return $unmapped;
    }

    /**
     * Validate authentication.
     */
    private static function authenticate(array $claims): ?Player
    {
        if (empty($claims['user']) || empty($claims['discord'])) {
            return null;
        }

        $userId = $claims['user'];

        $user = Player::query()
            ->where('user_id', '=', $userId)
            ->first();

        if (! $user || ! $user->isStaff()) {
            LoggingHelper::log('User from JWT token is not staff');

            return null;
        }

        return $user;
    }

    /**
     * Read the JWT token data.
     */
    private static function read()
    {
        if (! self::$secret) {
            return;
        }

        $jwt = request()->cookie(self::Cookie);

        if (empty($jwt)) {
            return;
        }

        $claims = [];

        try {
            $parser    = new Parser(new JoseEncoder());
            $validator = new Validator();

            $algorithm = new Sha384();
            $key       = InMemory::base64Encoded(self::$secret);

            $token = $parser->parse($jwt);

            if (! ($token instanceof Plain)) {
                throw new \Exception('invalid token');
            }

            $validator->assert($token, new ValidAt(SystemClock::fromSystemTimezone(), new DateInterval('P1M')));
            $validator->assert($token, new SignedWith($algorithm, $key));

            $nonce = $token->headers()->get('nnc');

            if (! $nonce || substr(sha1(CLUSTER), 0, 8) !== $nonce) {
                throw new \Exception('invalid nonce');
            }

            $claims = self::unmap($token->claims()->all());

            // Remove privileged claims
            unset($claims['iat']);
            unset($claims['exp']);
        } catch (\Throwable $t) {
            LoggingHelper::log(sprintf('Failed to read JWT token: %s', $t->getMessage()));

            return;
        }

        self::$user   = self::authenticate($claims);
        self::$claims = $claims;

        if (! self::$user) {
            self::forget('user');
            self::forget('discord');
        }
    }

    private static function build(array $claims, string $validFor)
    {
        $now = new DateTimeImmutable();

        $builder   = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $algorithm = new Sha384();
        $key       = InMemory::base64Encoded(self::$secret);

        $builder->expiresAt($now->modify(sprintf('+%s', $validFor)));

        $nonce = substr(sha1(CLUSTER), 0, 8);
        $builder->withHeader('nnc', $nonce);

        $payload = self::map($claims);

        foreach ($payload as $name => $value) {
            $builder->withClaim($name, $value);
        }

        return $builder->getToken($algorithm, $key)->toString();
    }

    public static function init()
    {
        if (self::$claims !== null) {
            return;
        }

        LoggingHelper::log("Initialized JWTHelper");

        self::loadSecret();

        self::read();

        // Unauthenticated
        if (self::$claims === null) {
            self::$claims = [];

            self::$changed = true;
        }
    }

    public static function login(Player $user, array $discord)
    {
        self::$user = $user;

        self::$claims['user']    = $user->user_id;
        self::$claims['discord'] = [
            'id'            => $discord['id'] ?? false,
            'username'      => $discord['username'] ?? false,
            'global_name'   => $discord['global_name'] ?? false,
            'discriminator' => $discord['discriminator'] ?? false,
            'avatar'        => $discord['avatar'] ?? false,
            'sso'           => $discord['sso'] ?? false,
        ];

        self::$changed = true;
    }

    public static function logout()
    {
        self::$user = null;

        unset(self::$claims['user']);
        unset(self::$claims['discord']);

        self::$changed = true;
    }

    public static function shutdown()
    {
        if (! self::$secret || self::$claims === null || ! self::$changed) {
            return;
        }

        $token = self::build(self::$claims, '2 days');

        if (! $token) {
            return;
        }

        $isSecure = Str::startsWith(env('APP_URL'), 'https://');

        setcookie(self::Cookie, $token, [
            'expires'  => time() + 172800,
            'secure'   => $isSecure,
            'httponly' => true,
            'path'     => '/',
            'samesite' => $isSecure ? 'None' : 'Lax',
        ]);
    }

    public static function token(): ?string
    {
        if (! self::$secret || ! self::$user) {
            return null;
        }

        $license = self::$user->license_identifier;
        $discord = self::get('discord')['id'];
        $name    = self::$user->getSafePlayerName();

        return self::build([
            'license' => $license,
            'discord' => $discord,
            'name'    => $name,
        ], '4 hours');
    }

    public static function user(): ?Player
    {
        return self::$user;
    }

    public static function get(string $key)
    {
        if (self::$claims === null || empty(self::$claims[$key])) {
            return null;
        }

        return self::$claims[$key];
    }

    public static function put(string $key, $value)
    {
        if (self::$claims === null || (isset(self::$claims[$key]) && self::$claims[$key] === $value)) {
            return;
        }

        self::$claims[$key] = $value;

        self::$changed = true;
    }

    public static function forget(string $key)
    {
        if (self::$claims === null || ! isset(self::$claims[$key])) {
            return;
        }

        unset(self::$claims[$key]);

        self::$changed = true;
    }
}
