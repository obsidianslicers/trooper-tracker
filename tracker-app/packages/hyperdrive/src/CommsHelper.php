<?php

declare(strict_types=1);

namespace Hyperdrive;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CommsHelper
{
    /**
     * Returns a formatted flash message for an updated model.
     *
     * @param Model $model
     * @return string
     */
    public static function updated(Model $model): string
    {
        return self::formatModelAction($model, 'updated');
    }

    /**
     * Returns a formatted flash message for a created model.
     * 
     * @param Model $model
     * @return string
     */
    public static function created(Model $model): string
    {
        return self::formatModelAction($model, 'created');
    }

    /**
     * Returns a formatted flash message for a deleted model.
     * 
     * @param Model $model
     * @return string
     */
    public static function deleted(Model $model): string
    {
        return self::formatModelAction($model, 'deleted');
    }

    /**
     * Formats a flash message string for a model action.
     *
     * @param  Model  $model
     * @param  string $action
     * @return string
     */
    private static function formatModelAction(Model $model, string $action): string
    {
        $objectName = Str::headline(class_basename($model));

        // Grab 'name' or fallback to 'title'
        $identifier = $model->getAttribute('name') ?? $model->getAttribute('title');

        if (!empty($identifier))
        {
            return "{$objectName} \"{$identifier}\" was {$action} successfully.";
        }

        return "{$objectName} was {$action} successfully.";
    }
}