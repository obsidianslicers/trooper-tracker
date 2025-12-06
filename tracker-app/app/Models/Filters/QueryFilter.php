<?php

declare(strict_types=1);

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $query): Builder
    {
        foreach ($this->filters() as $name => $method)
        {
            if ($this->request->filled($name))
            {
                $query = $this->{$method}($query, $this->request->input($name));
            }
            else
            {
                $defaults = $this->defaults();

                if (array_key_exists($name, $defaults))
                {
                    $query = $this->{$method}($query, $defaults[$name]);
                }
            }

        }

        return $query;
    }

    /**
     * Optional: map filter keys to default values.
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Map request keys to filter methods.
     */
    abstract protected function filters(): array;
}