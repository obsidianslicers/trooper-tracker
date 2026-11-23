<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\PageData\CommandStaffPageData;
use App\Messages\Troopers\Queries\GetCommandStaff;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class CommandStaffPageDataTest extends TestCase
{
    use FakesMessages;

    public function test_handle_returns_the_command_staff(): void
    {
        $troopers = collect(['staff']);
        $this->fakeMessages([GetCommandStaff::class => $troopers]);

        $subject = new CommandStaffPageData;

        $this->assertSame(['troopers' => $troopers], $subject->handle());
    }
}
