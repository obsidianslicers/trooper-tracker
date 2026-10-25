<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SystemChecks;

use App\Enums\SystemCheckStatus;
use App\Services\SystemChecks\IniValue;
use App\Services\SystemChecks\PhpIniCheck;
use Tests\TestCase;

class PhpIniCheckTest extends TestCase
{
    public function test_ini_value_parses_shorthand_and_unlimited(): void
    {
        $this->assertSame(100 * 1024 ** 2, IniValue::toNumber('100M'));
        $this->assertSame(1024 ** 3, IniValue::toNumber('1G'));
        $this->assertSame(512 * 1024, IniValue::toNumber('512K'));
        $this->assertSame(300, IniValue::toNumber('300'));
        $this->assertNull(IniValue::toNumber('-1'));
    }

    public function test_passes_when_values_meet_targets(): void
    {
        $subject = new PhpIniCheck;

        $result = $subject->check($this->values(), 'php.ini');

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
    }

    public function test_treats_unlimited_values_as_passing(): void
    {
        $subject = new PhpIniCheck;

        $result = $subject->check(
            array_merge($this->values(), ['memory_limit' => '-1', 'max_execution_time' => '0']),
            'php.ini',
        );

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
    }

    public function test_warns_when_a_value_is_below_target(): void
    {
        $subject = new PhpIniCheck;

        $result = $subject->check(array_merge($this->values(), ['post_max_size' => '8M']), 'php.ini');

        $this->assertSame(SystemCheckStatus::WARN, $result->status);
        $this->assertStringContainsString('post_max_size=8M', (string) $result->detail);
    }

    /**
     * @return array<string, string>
     */
    private function values(): array
    {
        return [
            'upload_max_filesize' => '100M', 'post_max_size' => '256M', 'memory_limit' => '1G',
            'max_execution_time' => '300', 'max_input_time' => '300', 'max_file_uploads' => '30',
        ];
    }
}
