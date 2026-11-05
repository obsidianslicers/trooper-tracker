<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Plugins\PluginSheetSource;
use Mockery;
use Mockery\MockInterface;
use Tests\Fixtures\Sheets\FakeSheetSource;

trait UsesFakeSheetSource
{
    protected function fakeSheetSource(): MockInterface
    {
        $source = Mockery::mock(FakeSheetSource::class);

        $this->app->instance(FakeSheetSource::class, $source);
        $this->app->make(PluginSheetSource::class)->set(FakeSheetSource::class);

        return $source;
    }
}
