<?php

namespace App\Services;

use App\Models\SubscrWpApiToken;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SubscrWordPressService
{
    /**
     * Send subscription status to WordPress.
     *
     * @param string $email
     * @param string $status
     * @return array
     */
    public function updateSubscription(string $email, string $status): array
    {
        $token = SubscrWpApiToken::active();

        if (!$token) {
            throw new RuntimeException(
                'Активный WordPress API token не найден.'
            );
        }

        $url = $token->base_url . '/wp-json/pmr/v1/subscription';

        /** @var Response $response */
        $response = Http::acceptJson()
            ->withBasicAuth(
                $token->username,
                $token->token
            )
            ->timeout(10)
            ->post($url, [
                'email' => $email,
                'status' => $status,
            ]);

        $token->update([
            'last_used_at' => now(),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'WordPress API вернул HTTP ' . $response->status()
                . ': ' . $response->body()
            );
        }

        return $response->json();
    }
}
