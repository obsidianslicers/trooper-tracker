<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Plugins\PluginManifest;
use App\Plugins\PluginSetting;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PluginManifestTest extends TestCase
{
    public function test_from_array_reads_every_field(): void
    {
        $subject = PluginManifest::fromArray([
            'slug' => 'my-plugin',
            'name' => 'My Plugin',
            'version' => '1.2.3',
            'description' => 'Does things.',
            'author' => 'Someone',
            'namespace' => '\\Acme\\MyPlugin\\',
            'provider' => '\\Acme\\MyPlugin\\MyServiceProvider',
            'settings' => [['key' => 'api_key', 'type' => 'secret']],
        ], '/plugins/my-plugin/', false);

        $this->assertSame('my-plugin', $subject->slug);
        $this->assertSame('My Plugin', $subject->name);
        $this->assertSame('Acme\\MyPlugin\\', $subject->namespace);
        $this->assertSame('Acme\\MyPlugin\\MyServiceProvider', $subject->provider);
        $this->assertSame('/plugins/my-plugin', $subject->path);
        $this->assertSame('/plugins/my-plugin/src', $subject->source_path);
        $this->assertSame('/plugins/my-plugin/database/migrations', $subject->migrationsPath());
        $this->assertTrue($subject->hasSettings());
        $this->assertSame('secret', $subject->settings[0]->type);
    }

    public function test_composer_packages_do_not_need_a_namespace(): void
    {
        $subject = PluginManifest::fromArray(['slug' => 'pkg', 'provider' => 'Acme\\Provider'], '/vendor/acme/pkg', true);

        $this->assertNull($subject->namespace);
        $this->assertNull($subject->source_path);
        $this->assertSame('pkg', $subject->name);
        $this->assertFalse($subject->hasSettings());
    }

    #[DataProvider('invalidManifests')]
    public function test_from_array_rejects_invalid_manifests(array $data): void
    {
        $this->expectException(InvalidArgumentException::class);

        PluginManifest::fromArray($data, '/plugins/x', false);
    }

    public static function invalidManifests(): array
    {
        return [
            'missing slug' => [['provider' => 'A\\B', 'namespace' => 'A']],
            'bad slug' => [['slug' => 'Bad Slug', 'provider' => 'A\\B', 'namespace' => 'A']],
            'missing provider' => [['slug' => 'ok', 'namespace' => 'A']],
            'local without namespace' => [['slug' => 'ok', 'provider' => 'A\\B']],
            'bad setting type' => [['slug' => 'ok', 'provider' => 'A\\B', 'namespace' => 'A', 'settings' => [['key' => 'x', 'type' => 'color']]]],
            'bad setting key' => [['slug' => 'ok', 'provider' => 'A\\B', 'namespace' => 'A', 'settings' => [['key' => 'Bad-Key']]]],
        ];
    }

    public function test_setting_cast_and_rules_follow_the_type(): void
    {
        $number = PluginSetting::fromArray(['key' => 'limit', 'type' => 'number']);
        $boolean = PluginSetting::fromArray(['key' => 'flag', 'type' => 'boolean']);
        $url = PluginSetting::fromArray(['key' => 'hook', 'type' => 'url']);

        $this->assertSame(5, $number->cast('5'));
        $this->assertNull($number->cast(''));
        $this->assertTrue($boolean->cast('1'));
        $this->assertFalse($boolean->cast(null));
        $this->assertSame('https://x.test', $url->cast(' https://x.test '));
        $this->assertContains('url', $url->rules());
    }
}
