<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ExceptionLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__ex/boom', fn () => throw new RuntimeException('boom failure'));
        Route::get('/__ex/not-found', fn () => abort(404));
    }

    public function test_server_error_is_logged_once_with_request_context(): void
    {
        Log::spy();

        $this->get('/__ex/boom')->assertServerError();

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'boom failure'
                    && $context['status_code'] === 500
                    && array_key_exists('url', $context)
                    && array_key_exists('user_id', $context)
                    && $context['exception'] instanceof RuntimeException;
            });

        // return false in the reportable callback suppresses the default handler's
        // context-less duplicate line.
        Log::shouldNotHaveReceived('log');
    }

    public function test_client_error_is_not_logged_by_the_reportable_callback(): void
    {
        Log::spy();

        $this->get('/__ex/not-found')->assertNotFound();

        Log::shouldNotHaveReceived('error');
    }
}
