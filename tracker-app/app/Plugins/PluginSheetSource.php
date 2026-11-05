<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Contracts\SheetSource;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * The spreadsheet source an enabled plugin set for the club synchronizers.
 * A site has at most one.
 */
final class PluginSheetSource
{
    /**
     * @var class-string<SheetSource>|null
     */
    private ?string $source_class = null;

    public function __construct(private readonly Application $app) {}

    /**
     * @param  class-string<SheetSource>  $source_class
     */
    public function set(string $source_class): void
    {
        if (!is_subclass_of($source_class, SheetSource::class))
        {
            throw new InvalidArgumentException(
                "[{$source_class}] must implement ".SheetSource::class.'.'
            );
        }

        $this->source_class = $source_class;
    }

    public function get(): ?SheetSource
    {
        return $this->source_class === null ? null : $this->app->make($this->source_class);
    }
}
