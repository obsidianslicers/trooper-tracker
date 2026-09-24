<?php

declare(strict_types=1);

namespace Database\Seeders\Scenarios;

use App\Services\Forums\XenforoService;
use Illuminate\Database\Seeder;

class XenforoTestThreadSeeder extends Seeder
{
    public function run(): void
    {
        $node_id = (int) $this->command->ask('Node ID?');

        $title = $this->command->ask('Thread title?');

        $message = $this->command->ask('Thread message?');

        $user_id = $this->command->confirm('Specify a user ID?')
            ? (int) $this->command->ask('User ID')
            : null;

        $prefix_id = $this->command->confirm('Specify a prefix ID?')
            ? (int) $this->command->ask('Prefix ID')
            : null;

        $extra_fields = [];

        if ($this->command->confirm('Add extra fields?', false))
        {
            while (true)
            {
                $key = $this->command->ask('Field name (leave blank to finish)');

                if (blank($key))
                {
                    break;
                }

                $extra_fields[$key] = $this->command->ask("Value for '{$key}'");
            }
        }

        $xenforo = app(XenforoService::class);

        $result = $xenforo->create_thread(
            $node_id,
            $title,
            $message,
            $user_id,
            $prefix_id,
            $extra_fields
        );

        $this->command->info('Status: ' . $result['status']);
        $this->command->line('JSON Response:');
        $this->command->line(
            json_encode($result['body'], JSON_PRETTY_PRINT)
        );
    }
}