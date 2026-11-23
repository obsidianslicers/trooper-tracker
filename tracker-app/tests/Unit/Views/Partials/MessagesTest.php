<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Partials;

use App\Enums\FlashType;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class MessagesTest extends TestCase
{
    public function test_flash_messages_go_to_the_toasts_as_json(): void
    {
        FlashType::success('Costume "Vader" was created successfully.');
        FlashType::danger('<b>Nope</b>');

        $subject = $this->render();

        $this->assertSame(1, preg_match('/id="tt-flash">(.*)<\/script>/', $subject, $json));
        $this->assertSame([
            ['type' => 'success', 'message' => 'Costume "Vader" was created successfully.'],
            ['type' => 'danger', 'message' => '<b>Nope</b>'],
        ], json_decode($json[1], true));
        $this->assertStringNotContainsString('<b>', $subject);
        $this->assertStringNotContainsString('alert-success', $subject);
    }

    public function test_nothing_is_sent_without_flash_messages(): void
    {
        $this->assertStringNotContainsString('tt-flash', $this->render());
    }

    public function test_validation_errors_stay_on_the_page(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['name' => 'The name field is required.']));

        $subject = $this->render($errors);

        $this->assertStringContainsString('alert alert-danger', $subject);
        $this->assertStringContainsString('The name field is required.', $subject);
    }

    private function render(?ViewErrorBag $errors = null): string
    {
        return view('partials.messages', ['errors' => $errors ?? new ViewErrorBag])->render();
    }
}
