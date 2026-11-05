<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Contracts\DonationSource;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * The donation sources enabled plugins have added.
 */
final class PluginDonations
{
    /**
     * @var array<int, class-string<DonationSource>>
     */
    private array $sources = [];

    public function __construct(private readonly Application $app) {}

    /**
     * @param  class-string<DonationSource>  $source_class
     */
    public function add(string $source_class): void
    {
        if (!is_subclass_of($source_class, DonationSource::class))
        {
            throw new InvalidArgumentException(
                "[{$source_class}] must implement ".DonationSource::class.'.'
            );
        }

        $this->sources[] = $source_class;
    }

    /**
     * Fresh instances, one per source.
     *
     * @return array<int, DonationSource>
     */
    public function sources(): array
    {
        return array_map(
            fn (string $source_class): DonationSource => $this->app->make($source_class),
            $this->sources,
        );
    }
}
