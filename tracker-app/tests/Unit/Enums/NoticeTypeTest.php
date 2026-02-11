<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NoticeType;
use Tests\TestCase;

class NoticeTypeTest extends TestCase
{
    public function test_description_returns_star_wars_themed_message(): void
    {
        $this->assertEquals('NOW HEAR THIS!', NoticeType::INFO->description());
        $this->assertEquals('MISSION ACCOMPLISHED!', NoticeType::SUCCESS->description());
        $this->assertEquals('ATTENTION TROOPERS!', NoticeType::WARNING->description());
        $this->assertEquals('BATTLE STATIONS!', NoticeType::DANGER->description());
    }

    public function test_description_returns_string_for_all_types(): void
    {
        foreach (NoticeType::cases() as $type)
        {
            $description = $type->description();
            $this->assertIsString($description);
            $this->assertNotEmpty($description);
        }
    }

    public function test_to_descriptions_returns_array_with_all_types(): void
    {
        $result = NoticeType::toDescriptions();

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('danger', $result);
    }

    public function test_to_descriptions_returns_correct_messages(): void
    {
        $result = NoticeType::toDescriptions();

        $this->assertEquals('NOW HEAR THIS!', $result['info']);
        $this->assertEquals('MISSION ACCOMPLISHED!', $result['success']);
        $this->assertEquals('ATTENTION TROOPERS!', $result['warning']);
        $this->assertEquals('BATTLE STATIONS!', $result['danger']);
    }

    public function test_to_descriptions_values_match_description_method(): void
    {
        $descriptions = NoticeType::toDescriptions();

        foreach (NoticeType::cases() as $type)
        {
            $this->assertEquals(
                $type->description(),
                $descriptions[$type->value],
                sprintf('Description method and toDescriptions() mismatch for %s', $type->value)
            );
        }
    }

    public function test_to_options_returns_array_with_emoji_and_description(): void
    {
        $result = NoticeType::toOptions();

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('danger', $result);
    }

    public function test_to_options_includes_emoji_icons(): void
    {
        $result = NoticeType::toOptions();

        // Check for emoji presence
        $this->assertStringContainsString('ℹ️', $result['info']);
        $this->assertStringContainsString('✔️', $result['success']);
        $this->assertStringContainsString('⚠️', $result['warning']);
        $this->assertStringContainsString('❌', $result['danger']);
    }

    public function test_to_options_includes_descriptions(): void
    {
        $result = NoticeType::toOptions();

        $this->assertStringContainsString('NOW HEAR THIS!', $result['info']);
        $this->assertStringContainsString('MISSION ACCOMPLISHED!', $result['success']);
        $this->assertStringContainsString('ATTENTION TROOPERS!', $result['warning']);
        $this->assertStringContainsString('BATTLE STATIONS!', $result['danger']);
    }

    public function test_to_options_has_emoji_before_text(): void
    {
        $result = NoticeType::toOptions();

        // Emoji should come before the text
        $this->assertEquals('ℹ️ NOW HEAR THIS!', $result['info']);
        $this->assertEquals('✔️ MISSION ACCOMPLISHED!', $result['success']);
        $this->assertEquals('⚠️ ATTENTION TROOPERS!', $result['warning']);
        $this->assertEquals('❌ BATTLE STATIONS!', $result['danger']);
    }

    public function test_each_type_has_unique_description(): void
    {
        $descriptions = [];
        foreach (NoticeType::cases() as $type)
        {
            $description = $type->description();
            $this->assertNotContains($description, $descriptions, sprintf(
                'Description "%s" is not unique for %s',
                $description,
                $type->value
            ));
            $descriptions[] = $description;
        }
    }

    public function test_each_type_has_unique_option_text(): void
    {
        $options = NoticeType::toOptions();
        $this->assertCount(count(array_unique($options)), $options);
    }
}
