<?php

namespace App\Http\Controllers;

use App\Models\SubscrWpApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class SubscribeWPApiTokenController extends Controller
{
    /**
     * Store a new WordPress API token.
     *
     * This token belongs to the technical WP user "pmr-api".
     */
    public function store(Request $request): JsonResponse
    {
        if (!Session::get('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация администратора.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'token' => ['required', 'string'],
            'base_url' => ['required', 'url', 'max:255'],
            'username' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации.',
                'errors' => $validator->errors(),
            ], 422);
        }

        /*
         * Disable currently active tokens and create
         * the new token inside one database transaction.
         *
         * This guarantees that the old active token is disabled
         * only together with successful creation of the new token.
         */
        $token = DB::transaction(function () use ($request) {

            SubscrWpApiToken::where('is_active', true)
                ->update([
                    'is_active' => false,
                ]);

            return SubscrWpApiToken::create([
                'name' => $request->input('name'),
                'token' => $request->input('token'),
                'base_url' => rtrim($request->input('base_url'), '/'),
                'username' => $request->input('username'),
                'is_active' => true,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'WP API token сохранён.',
            'token' => [
                'id' => $token->id,
                'name' => $token->name,
                'base_url' => $token->base_url,
                'username' => $token->username,
                'is_active' => $token->is_active,
                'created_at' => $token->created_at,
            ],
        ]);
    }

    /**
     * Return the currently active WordPress API token metadata.
     *
     * The actual token is never returned.
     */
    public function show(): JsonResponse
    {
        if (!Session::get('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация администратора.',
            ], 403);
        }

        $token = SubscrWpApiToken::active();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Активный WP API token не найден.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'token' => [
                'id' => $token->id,
                'name' => $token->name,
                'base_url' => $token->base_url,
                'username' => $token->username,
                'is_active' => $token->is_active,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'updated_at' => $token->updated_at,
            ],
        ]);
    }
}
