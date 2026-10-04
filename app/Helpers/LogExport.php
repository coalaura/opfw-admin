<?php
namespace App\Helpers;

use App\Log;
use App\MoneyLog;
use App\WeaponDamageEvent;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogExport
{
    public const MAX_ROWS = 500;

    public const SORT_COLUMNS = [
        'server' => [
            'timestamp'  => 'timestamp',
            'identifier' => 'identifier',
            'action'     => 'action',
        ],
        'damage' => [
            'timestamp' => 'timestamp',
            'attacker'  => 'license_identifier',
            'damage'    => 'weapon_damage',
            'distance'  => 'distance',
        ],
        'money'  => [
            'timestamp'    => 'money_logs.timestamp',
            'amount'       => 'money_logs.amount',
            'character_id' => 'money_logs.character_id',
            'type'         => 'money_logs.type',
        ],
    ];

    private const HEADERS = [
        'server' => ['id', 'timestamp_utc', 'license_identifier', 'player_name', 'server', 'server_id', 'action', 'details', 'minigame', 'metadata'],
        'damage' => ['id', 'timestamp_utc', 'attacker_identifier', 'attacker_name', 'victim_identifier', 'victim_name', 'entity_type', 'vehicle_id', 'network_id', 'health_before', 'damage', 'bonus_damage', 'distance', 'hit_component', 'weapon', 'damage_flags', 'silenced', 'tire_index', 'suspension_index', 'canceled', 'minigame'],
        'money'  => ['id', 'timestamp_utc', 'license_identifier', 'player_name', 'character_id', 'character_name', 'type', 'balance_before', 'amount', 'balance_after', 'details'],
    ];

    public static function rules(string $type): array
    {
        $rules = [
            'limit'  => ['required', 'integer', 'min:1', 'max:' . self::MAX_ROWS],
            'sort'   => ['required', Rule::in(array_keys(self::SORT_COLUMNS[$type]))],
            'order'  => ['required', Rule::in(['asc', 'desc'])],
            'before' => ['nullable', 'integer'],
            'after'  => ['nullable', 'integer'],
        ];

        foreach (['identifier', 'action', 'details', 'server', 'minigame', 'attacker', 'victim', 'damage', 'weapon', 'entity', 'typ', 'direction', 'amount', 'character_id'] as $filter) {
            $rules[$filter] = ['nullable', 'string'];
        }

        return $rules;
    }

    public static function download(string $type, iterable $logs, array $playerNames = []): StreamedResponse
    {
        return new StreamedResponse(function () use ($type, $logs, $playerNames) {
            $stream = fopen('php://output', 'w');

            $weapons = null;

            try {
                fputcsv($stream, self::HEADERS[$type], ',', '"', '', "\r\n");

                foreach ($logs as $log) {
                    $row = match ($type) {
                        'server' => self::serverRow($log, $playerNames),
                        'damage' => self::damageRow($log, $playerNames, $weapons ??= WeaponDamageEvent::getWeaponList()),
                        'money'  => self::moneyRow($log),
                    };

                    foreach ($row as &$cell) {
                        // Keep untrusted text from becoming a spreadsheet formula; numeric values stay numeric.
                        if (is_string($cell) && preg_match('/^(?:[\t\r\n]|\s*[=+@-])/u', $cell)) {
                            $cell = "'" . $cell;
                        }
                    }

                    unset($cell);

                    fputcsv($stream, $row, ',', '"', '', "\r\n");
                }
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'Content-Disposition'    => 'attachment; filename="' . $type . '_logs.csv"',
            'Cache-Control'          => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private static function serverRow(Log $log, array $playerNames): array
    {
        $metadata = $log->metadata;

        return [
            $log->id,
            $log->timestamp->copy()->utc()->format('Y-m-d\TH:i:s.v\Z'),
            $log->identifier,
            $playerNames[$log->identifier] ?? null,
            $metadata['serverId'] ?? null,
            $metadata['playerServerId'] ?? null,
            $log->action,
            $log->details,
            $metadata['minigame'] ?? null,
            $metadata === null ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
        ];
    }

    private static function damageRow(WeaponDamageEvent $log, array $playerNames, array $weapons): array
    {
        $entityType = $log->hit_player ? 'player' : ([1 => 'ped', 2 => 'vehicle', 3 => 'object'][$log->hit_entity_type] ?? 'unknown');

        return [
            $log->id,
            Carbon::createFromTimestampMs($log->timestamp, 'UTC')->format('Y-m-d\TH:i:s.v\Z'),
            $log->license_identifier,
            $playerNames[$log->license_identifier] ?? null,
            $log->hit_player,
            $playerNames[$log->hit_player] ?? null,
            $entityType,
            $log->hit_vehicle_id === null ? null : (int) $log->hit_vehicle_id,
            $log->hit_global_id === null ? null : (int) $log->hit_global_id,
            $log->hit_health === null ? null : (float) $log->hit_health,
            $log->weapon_damage === null ? null : (float) $log->weapon_damage,
            $log->bonus_damage === null ? null : (float) $log->bonus_damage,
            $log->distance === null ? null : (float) $log->distance,
            WeaponDamageEvent::getHitComponent($log->hit_component),
            WeaponDamageEvent::getDamageWeapon($log->weapon_type, $weapons),
            (int) $log->damage_flags,
            (int) $log->silenced,
            $log->tyre_index === null ? null : (int) $log->tyre_index,
            $log->suspension_index === null ? null : (int) $log->suspension_index,
            (int) $log->canceled,
            $log->minigame,
        ];
    }

    private static function moneyRow(MoneyLog $log): array
    {
        return [
            $log->id,
            $log->timestamp->copy()->utc()->format('Y-m-d\TH:i:s.v\Z'),
            $log->license_identifier,
            $log->player_name,
            $log->character_id,
            $log->character_name,
            $log->type,
            $log->balance_after - $log->amount,
            $log->amount,
            $log->balance_after,
            $log->details,
        ];
    }
}
