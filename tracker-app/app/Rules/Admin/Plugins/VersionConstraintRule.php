<?php

declare(strict_types=1);

namespace App\Rules\Admin\Plugins;

use Closure;
use Composer\Semver\VersionParser;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * A Composer version constraint, such as "^1.0", with no spaces so the deploy can
 * pass it to composer require as one argument.
 */
class VersionConstraintRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('/\s/', (string) $value) === 1)
        {
            $fail('Write the version without spaces, such as ^1.0||^2.0.');

            return;
        }

        try
        {
            (new VersionParser)->parseConstraints((string) $value);
        }
        catch (Throwable)
        {
            $fail('Use a Composer version constraint, such as ^1.0.');
        }
    }
}
