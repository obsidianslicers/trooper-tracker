<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Forums\XenforoService;

class XenforoTestThreadCommand extends Command
{
    protected $signature = 'xenforo:test-thread {node_id} {title} {message} {--user_id=} {--prefix_id=} {--extra=*}';
    protected $description = 'Test Xenforo create_thread and show JSON response';

    public function handle()
    {
        $node_id = (int) $this->argument('node_id');
        $title = $this->argument('title');
        $message = $this->argument('message');
        $user_id = $this->option('user_id') ? (int) $this->option('user_id') : null;
        $prefix_id = $this->option('prefix_id') ? (int) $this->option('prefix_id') : null;
        $extra_fields = [];
        foreach ($this->option('extra') as $pair) {
            [$key, $val] = explode(':', $pair, 2) + [null, null];
            if ($key !== null && $val !== null) {
                $extra_fields[$key] = $val;
            }
        }

        $xenforo = new XenforoService();
        $result = $xenforo->create_thread($node_id, $title, $message, $user_id, $prefix_id, $extra_fields);

        $this->line('Status: ' . $result['status']);
        $this->line('JSON Response:');
        $this->line(json_encode($result['body'], JSON_PRETTY_PRINT));
    }
}
