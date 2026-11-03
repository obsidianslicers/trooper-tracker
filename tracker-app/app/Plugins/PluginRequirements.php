<?php

declare(strict_types=1);

namespace App\Plugins;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Throwable;

/**
 * Checks a plugin's composer.json "require" list against what the app already has.
 *
 * An uploaded plugin cannot install packages, so it may only use packages, PHP
 * extensions and a PHP version the app already provides.
 */
final class PluginRequirements
{
    /**
     * @param  array<string, string>  $requirements  Package name => version constraint.
     * @return array<int, string> Each unmet requirement, e.g. "vendor/pkg ^2.0".
     */
    public function missing(array $requirements): array
    {
        $missing = [];

        foreach ($requirements as $package => $constraint)
        {
            if (!$this->isMet((string) $package, (string) $constraint))
            {
                $missing[] = trim("{$package} {$constraint}");
            }
        }

        return $missing;
    }

    private function isMet(string $package, string $constraint): bool
    {
        $parser = new VersionParser;

        try
        {
            return match (true)
            {
                $package === 'php' => $parser->parseConstraints($constraint)
                    ->matches($parser->parseConstraints($parser->normalize(PHP_VERSION))),
                str_starts_with($package, 'ext-') => extension_loaded(substr($package, 4)),
                str_starts_with($package, 'lib-') => true,
                default => InstalledVersions::isInstalled($package)
                    && InstalledVersions::satisfies($parser, $package, $constraint),
            };
        }
        catch (Throwable)
        {
            return false;
        }
    }
}
