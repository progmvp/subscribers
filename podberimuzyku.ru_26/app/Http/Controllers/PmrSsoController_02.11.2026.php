<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PmrSsoController extends Controller
{
    /**
     * Создать одноразовый токен и перенаправить пользователя в WordPress.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        /*
         * Для SSO необходима действующая подписка.
         */
        if (
            !$user->subscription_expires_at
            || !$user->subscription_expires_at->isFuture()
        ) {
            return redirect()
                ->route('lk.dashboard')
                ->withErrors([
                    'sso' => 'Для перехода на сайт необходима активная подписка.',
                ]);
        }

        /*
         * Страница возврата должна принадлежать нашему WordPress.
         * При отсутствии сохранённого адреса открываем главную.
         */
        $returnUrl = session('pmr_sso_return_url');

        if (!$this->isAllowedReturnUrl($returnUrl)) {
            $returnUrl = 'https://podberimuzyku.ru/';
        }

        /*
         * Создаём случайный токен.
         * В таблицу записываем только его SHA-256-хеш.
         */
        $token = Str::random(64);

        DB::table('subscr_sso_tokens')->insert([
            'token_hash' => hash('sha256', $token),
            'user_id' => $user->id,
            'return_url' => $returnUrl,
            'expires_at' => now()->addMinutes(2),
            'consumed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * Передаём токен WordPress для однократного входа.
         */
        return redirect()->away(
            'https://podberimuzyku.ru/wp-json/pmr/v1/token?token='
            . rawurlencode($token)
        );
    }

    /**
     * Серверная проверка и однократное погашение токена WordPress.
     */
    public function validate(Request $request)
    {
        $expectedSecret = (string) config('services.pmr_sso.shared_secret');
        $providedSecret = (string) $request->header('X-PMR-SSO-Secret');

        if (
            $expectedSecret === ''
            || $providedSecret === ''
            || !hash_equals($expectedSecret, $providedSecret)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'min:32', 'max:200'],
        ]);

        $tokenHash = hash('sha256', $validated['token']);

        /*
         * Блокировка строки исключает повторное использование токена
         * при одновременных запросах.
         */
        $result = DB::transaction(function () use ($tokenHash) {
            $tokenRow = DB::table('subscr_sso_tokens')
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (
                !$tokenRow
                || $tokenRow->consumed_at !== null
                || now()->greaterThanOrEqualTo($tokenRow->expires_at)
            ) {
                return null;
            }

            $user = User::query()
                ->where('id', $tokenRow->user_id)
                ->lockForUpdate()
                ->first();

            if (
                !$user
                || !$user->subscription_expires_at
                || !$user->subscription_expires_at->isFuture()
            ) {
                return null;
            }

            DB::table('subscr_sso_tokens')
                ->where('id', $tokenRow->id)
                ->update([
                    'consumed_at' => now(),
                    'updated_at' => now(),
                ]);

            return [
                'user_id' => (int) $user->id,
                'subscription_expires_at' =>
                    $user->subscription_expires_at->toIso8601String(),
                'return_url' => $tokenRow->return_url,
            ];
        });

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid, expired or already used.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user_id' => $result['user_id'],
            'subscription_expires_at' => $result['subscription_expires_at'],
            'return_url' => $result['return_url'],
        ]);
    }

    /**
     * Разрешаем возврат только на основной домен WordPress.
     */
    private function isAllowedReturnUrl(?string $url): bool
    {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);

        if (!$parts) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        $port = $parts['port'] ?? null;

        return $scheme === 'https'
            && in_array($host, [
                'podberimuzyku.ru',
                'www.podberimuzyku.ru',
            ], true)
            && ($port === null || $port === 443)
            && !isset($parts['user'])
            && !isset($parts['pass']);
    }
}
