<?php

declare(strict_types=1);

namespace App\Models\Scopes;

trait HasToOptionScope
{
    public function scopeToOptions($query, string $name, string $id)
    {
        return $query->get()
            ->mapWithKeys(function ($model) use ($name, $id)
            {
                return [$model->{$id} => $model->{$name}];
            })
            ->toArray();

    }
}