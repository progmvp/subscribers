<?php

namespace App\Console\Commands;

use App\Services\SubscrWordPressService;
use Illuminate\Console\Command;
use Throwable;

class SubscrWpTestCommand extends Command
{
    protected $signature = 'subscr:wp-test {email=progmvp@gmail.com} {status=active}';

    protected $description = 'Проверить связь Laravel с WordPress API подписки';

    public function handle(SubscrWordPressService $wordpress): int
    {
        $email = $this->argument('email');
        $status = $this->argument('status');

        $this->info('Проверка Laravel → WordPress');
        $this->line('Email: ' . $email);
        $this->line('Status: ' . $status);
        $this->newLine();

        try {
            $result = $wordpress->updateSubscription($email, $status);

            $this->info('WordPress API ответил успешно.');
            $this->newLine();

            $this->line(
                json_encode(
                    $result,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Ошибка при обращении к WordPress API.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}