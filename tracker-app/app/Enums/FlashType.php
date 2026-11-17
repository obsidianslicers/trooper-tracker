<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

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
        self::success(self::modelMessage($model, 'created'));
    }

    /**
     * Adds to the built-in flash session collection
     */
    public static function updated(Model $model): void
    {
        self::success(self::modelMessage($model, 'updated'));
    }

    /**
     * Adds to the built-in flash session collection
     */
    public static function deleted(Model $model): void
    {
        self::warning(self::modelMessage($model, 'deleted'));
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

    /**
     * "Costume \"Vader\" was created successfully." in the current language. The model's
     * name comes from lang/<locale>/models.php when that file has it.
     */
    private static function modelMessage(Model $model, string $action): string
    {
        $object = Str::headline(class_basename($model));
        $object = Lang::has("models.{$object}") ? __("models.{$object}") : $object;
        $name = $model->getAttribute('name') ?? $model->getAttribute('title');

        if (empty($name))
        {
            return match ($action)
            {
                'created' => __('common.:object was created successfully.', ['object' => $object]),
                'updated' => __('common.:object was updated successfully.', ['object' => $object]),
                default => __('common.:object was deleted successfully.', ['object' => $object]),
            };
        }

        $replace = ['object' => $object, 'name' => $name];

        return match ($action)
        {
            'created' => __('common.:object ":name" was created successfully.', $replace),
            'updated' => __('common.:object ":name" was updated successfully.', $replace),
            default => __('common.:object ":name" was deleted successfully.', $replace),
        };
    }
}
