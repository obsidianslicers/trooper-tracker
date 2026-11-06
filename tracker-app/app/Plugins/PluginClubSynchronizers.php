<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Contracts\MemberLookupInterface;
use App\Contracts\MemberLookupSource;
use App\Contracts\SynchronizerInterface;
use App\Models\Organization;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * The ways a club's roster and costumes can be kept in step with the club's own
 * records. Core registers the built-in clubs; plugins add more. A club stores
 * the key of the one it uses.
 *
 * A synchronizer class takes the Organization as its `organization` constructor
 * argument and implements SynchronizerInterface, MemberLookupSource, or both.
 */
final class PluginClubSynchronizers
{
    /**
     * @var array<string, array{label: string, class: class-string}>
     */
    private array $synchronizers = [];

    public function __construct(private readonly Application $app) {}

    /**
     * @param  class-string  $class
     */
    public function add(string $key, string $label, string $class): void
    {
        if (isset($this->synchronizers[$key]))
        {
            throw new InvalidArgumentException("Club synchronizer [{$key}] already exists.");
        }

        if (!is_subclass_of($class, SynchronizerInterface::class)
            && !is_subclass_of($class, MemberLookupSource::class))
        {
            throw new InvalidArgumentException(
                "[{$class}] must implement ".SynchronizerInterface::class
                .' or '.MemberLookupSource::class.'.'
            );
        }

        $this->synchronizers[$key] = ['label' => $label, 'class' => $class];
    }

    public function has(string $key): bool
    {
        return isset($this->synchronizers[$key]);
    }

    /**
     * @return array<string, string> labels keyed by synchronizer key, sorted by label
     */
    public function options(): array
    {
        $options = array_map(fn (array $entry): string => $entry['label'], $this->synchronizers);
        asort($options);

        return $options;
    }

    public function synchronizer(Organization $organization): ?SynchronizerInterface
    {
        $service = $this->make($organization);

        return $service instanceof SynchronizerInterface ? $service : null;
    }

    public function memberLookup(Organization $organization): ?MemberLookupInterface
    {
        $service = $this->make($organization);

        return $service instanceof MemberLookupSource ? $service->lookupMember() : null;
    }

    private function make(Organization $organization): ?object
    {
        $key = $organization->synchronizer;

        if ($key === null || !$this->has($key))
        {
            return null;
        }

        return $this->app->make(
            $this->synchronizers[$key]['class'],
            ['organization' => $organization],
        );
    }
}
