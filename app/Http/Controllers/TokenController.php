<?php
namespace App\Http\Controllers;

use App\AuditLog;
use App\Helpers\PermissionHelper;
use App\Http\Resources\TokenResource;
use App\Token;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $tokens = Token::all();

        return Inertia::render('Tokens/Index', [
            'panel'   => env('OP_FW_TOKEN', ''),
            'tokens'  => TokenResource::collection($tokens),
            'methods' => Token::ValidMethods,
            'routes'  => Token::getAvailableRoutes(),
            'rest'    => Token::RestTables,
        ]);
    }

    public function update(Request $request, Token $token)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $permissions = $request->input('permissions', '');

        if (! Token::permissionsValid($permissions)) {
            return $this->json(false, null, 'Invalid permissions');
        }

        $note = trim($request->input('note', ''));

        $tokenBefore = [
            'note'        => $token->note,
            'permissions' => $token->permissions,
        ];

        $token->update([
            'note'        => $note,
            'permissions' => $permissions,
        ]);

        $user = user();

        AuditLog::log(license(), 'token.update', 'token', $token->id, sprintf('%s updated API token #%d.', $user->consoleName(), $token->id), [
            'token_id'    => $token->id,
            'before'      => $tokenBefore,
            'after'       => [
                'note'        => $note,
                'permissions' => $permissions,
            ],
        ]);

        return $this->json(true);
    }

    public function create(Request $request)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $token = Token::create([
            'token'                  => Token::generateToken(),
            'permissions'            => '',
            'note'                   => '',
            'total_requests'         => 0,
            'last_request_timestamp' => 0,
        ]);

        $user = user();

        AuditLog::log(license(), 'token.create', 'token', $token->id, sprintf('%s created API token #%d.', $user->consoleName(), $token->id), [
            'token_id' => $token->id,
        ]);

        return $this->json(true, TokenResource::make($token));
    }

    public function delete(Request $request, Token $token)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $tokenId = $token->id;
        $tokenNote = $token->note;

        $token->delete();

        $user = user();

        AuditLog::log(license(), 'token.delete', 'token', $tokenId, sprintf('%s deleted API token #%d.', $user->consoleName(), $tokenId), [
            'token_id' => $tokenId,
            'note'     => $tokenNote,
        ]);

        return $this->json(true);
    }

    public function logs(Request $request)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $tokenId = $request->input('id');

        if (! $tokenId || ! is_numeric($tokenId)) {
            return $this->json(false, null, 'Invalid token ID');
        }

        $before = $request->input('before') ?: null;

        $logs = Token::getRecentLogs($tokenId, $before, 50);

        return $this->json(true, $logs);
    }

    public function rps(Request $request)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $tokenId = $request->input('id');

        if (! $tokenId || ! is_numeric($tokenId)) {
            return $this->json(false, null, 'Invalid token ID');
        }

        return $this->json(true, Token::getRequestsPerSecond($tokenId));
    }
}
