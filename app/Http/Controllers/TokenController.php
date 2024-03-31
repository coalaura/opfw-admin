<?php

namespace App\Http\Controllers;

use App\Http\Resources\TokenResource;
use App\Helpers\PermissionHelper;
use App\Token;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $tokens = Token::all();

        return Inertia::render('Tokens/Index', [
            'tokens'  => TokenResource::collection($tokens),
            'methods' => Token::ValidMethods,
        ]);
    }

    public function update(Request $request, Token $token)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $permissions = $request->input('permissions', '');

        if (!Token::permissionsValid($permissions)) {
            return $this->json(false, null, 'Invalid permissions');
        }

        $note = trim($request->input('note', ''));

        $token->update([
            'note'        => $note,
            'permissions' => $permissions,
        ]);

        return $this->json(true);
    }

    public function create(Request $request)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $token = Token::create([
            'token'                  => Token::generateToken(),
            'permissions'            => '',
            'note'                   => '',
            'total_requests'         => 0,
            'last_request_timestamp' => 0,
        ]);

        return $this->json(true, TokenResource::make($token));
    }

    public function delete(Request $request, Token $token)
    {
        if (!PermissionHelper::hasPermission($request, PermissionHelper::PERM_API_TOKENS)) {
            abort(401);
        }

        $token->delete();

        return $this->json(true);
    }
}
