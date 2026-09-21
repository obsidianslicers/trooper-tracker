<?php

declare(strict_types=1);

namespace App\Enums;

use Hyperdrive\CommsHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * Defines the types of flash notifications that can be displayed to a user.
 *
 * This is typically used for flash messages or alerts to convey information
 * with a certain level of importance or context (e.g., success, error).
 *
 * Wired into the naming conventions of the Bootstrap alert classes for consistent styling.
 */
enum FlashType: string
{
    use HasEnumHelpers;

    /**
     * For informational messages.
     */
    case INFO = 'info';

    /**
     * For success messages (e.g., after a form is submitted correctly).
     */
    case SUCCESS = 'success';

    /**
     * For warnings that require user attention.
     */
    case WARNING = 'warning';

    /**
     * For critical errors or failure messages.
     */
    case DANGER = 'danger';

    /**
     * Adds to the built-in flash session collection
     *
     * @param  mixed  $message
     */
    public function flash($message): void
    {
        session()->flash($this->value, $message);
    }

    /**
     * Adds to the built-in flash session collection
     */
    public static function created(Model $model): void
    {
        self::success(CommsHelper::created($model));
    }

    /**
     * Adds to the built-in flash session collection
     */
    public static function updated(Model $model): void
    {
        self::success(CommsHelper::updated($model));
    }

    /**
     * Adds to the built-in flash session collection
     */
    public static function deleted(Model $model): void
    {
        self::warning(CommsHelper::deleted($model));
    }

    /**
     * Adds to the built-in flash session collection
     *
     * @param  mixed  $message
     */
    public static function info($message): void
    {
        session()->flash(self::INFO->value, $message);
    }

    /**
     * Adds to the built-in flash session collection
     *
     * @param  mixed  $message
     */
    public static function success($message): void
    {
        session()->flash(self::SUCCESS->value, $message);
    }

    /**
     * Adds to the built-in flash session collection
     *
     * @param  mixed  $message
     */
    public static function warning($message): void
    {
        session()->flash(self::WARNING->value, $message);
    }

    /**
     * Adds to the built-in flash session collection
     *
     * @param  mixed  $message
     */
    public static function danger($message): void
    {
        session()->flash(self::DANGER->value, $message);
    }
}
