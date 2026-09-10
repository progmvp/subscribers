<?php

namespace App\Console\Commands;

use App\Models\SubscrWpApiToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscrWpTokenCommand extends Command
{
    protected $signature = 'subscr:wp-token';

    protected $description = 'Сохранить WordPress API Application Password';

    public function handle(): int
    {
        $this->info('Сохранение WordPress API token');
        $this->newLine();

        $name = $this->ask(
            'Название',
            'pmr-api production'
        );

        $baseUrl = $this->ask(
            'WordPress base URL',
            'https://podberimuzyku.ru'
        );

        $username = $this->ask(
            'WordPress API username',
            'pmr-api'
        );

        $token = $this->secret(
            'WordPress Application Password'
        );

        if (!$token) {
            $this->error('Application Password не введён.');
            return self::FAILURE;
        }

        $validator = Validator::make(
            [
                'name' => $name,
                'token' => $token,
                'base_url' => $baseUrl,
                'username' => $username,
            ],
            [
                'name' => ['required', 'string', 'max:100'],
                'token' => ['required', 'string'],
                'base_url' => ['required', 'url', 'max:255'],
                'username' => ['required', 'string', 'max:100'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $baseUrl = rtrim($baseUrl, '/');

        DB::transaction(function () use (
            $name,
            $token,
            $baseUrl,
            $username
        ) {
            /*
             * Деактивируем предыдущий token.
             * В рабочем состоянии у нас должен быть только один активный.
             */
            SubscrWpApiToken::where('is_active', true)
                ->update([
                    'is_active' => false,
                ]);

            /*
             * Модель SubscrWpApiToken сама зашифрует token
             * через Crypt::encryptString().
             */
            SubscrWpApiToken::create([
                'name' => $name,
                'token' => $token,
                'base_url' => $baseUrl,
                'username' => $username,
                'is_active' => true,
            ]);
        });

        $this->newLine();
        $this->info('WordPress API token успешно сохранён.');
        $this->info('Token записан в БД в зашифрованном виде.');

        return self::SUCCESS;
    }
}
