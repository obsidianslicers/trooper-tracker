<?php

declare(strict_types=1);

namespace App\Plugins;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Throwable;

/**
 * Checks a plugin's requirements against what the site already has: the PHP
 * version, PHP extensions, Composer packages and the plugin API version.
 *
 * An uploaded plugin cannot install packages, so it may only use what the app
 * already provides. Every plugin is checked again before it is turned on.
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

    /**
     * Like missing(), but each unmet requirement is described for an administrator,
     * e.g. "PHP ^8.4 (this server has 8.3.6)".
     *
     * @param  array<string, string>  $requirements
     * @return array<int, string>
     */
    public function unmet(array $requirements): array
    {
        $unmet = [];

        foreach ($requirements as $package => $constraint)
        {
            if (!$this->isMet((string) $package, (string) $constraint))
            {
                $unmet[] = $this->describe((string) $package, (string) $constraint);
            }
        }

        return $unmet;
    }

    private function describe(string $package, string $constraint): string
    {
        return match (true)
        {
            $package === 'php' => 'PHP '.$constraint.' (this server has '.$this->phpVersion().')',
            str_starts_with($package, 'ext-') => 'the '.substr($package, 4).' PHP extension',
            $package === PluginApi::REQUIREMENT => "TroopTracker plugin API {$constraint} "
                .'(this app has '.PluginApi::VERSION.')',
            default => $this->describePackage($package, $constraint),
        };
    }

    private function describePackage(string $package, string $constraint): string
    {
        $installed = InstalledVersions::isInstalled($package)
            ? InstalledVersions::getPrettyVersion($package)
            : null;

        return $installed === null
            ? "the Composer package {$package} {$constraint}"
            : "the Composer package {$package} {$constraint} ({$installed} is installed)";
    }

    private function isMet(string $package, string $constraint): bool
    {
        $parser = new VersionParser;

        try
        {
            return match (true)
            {
                $package === 'php' => $this->satisfies($this->phpVersion(), $constraint),
                $package === PluginApi::REQUIREMENT => $this->satisfies(
                    PluginApi::VERSION,
                    $constraint,
                ),
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

    private function phpVersion(): string
    {
        return PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
    }

    /**
     * Whether a version is in a constraint's range. A constraint that can't be
     * parsed is never met.
     */
    public function satisfies(string $version, string $constraint): bool
    {
        $parser = new VersionParser;

        try
        {
            return $parser->parseConstraints($constraint)
                ->matches($parser->parseConstraints($parser->normalize($version)));
        }
        catch (Throwable)
        {
            return false;
        }
    }
}
