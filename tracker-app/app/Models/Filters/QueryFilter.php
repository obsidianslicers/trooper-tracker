<?php

declare(strict_types=1);

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Abstract base class for applying HTTP request filters to Eloquent queries.
 *
 * This class provides a framework for filtering database queries based on HTTP request parameters.
 * Child classes define specific filters by implementing the filters() method and creating
 * corresponding protected methods to handle each filter.
 */
abstract class QueryFilter
{
    protected Request $request;

    /**
     * Create a new QueryFilter instance.
     *
     * @param Request $request The HTTP request containing filter parameters.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Magic getter for filter parameter values.
     *
     * Allows accessing filter values as properties (e.g., $filter->status).
     * Only works for keys defined in the filters() method.
     *
     * @param string $name The filter parameter key.
     * @return mixed The value from the request for the given filter key.
     * @throws \InvalidArgumentException If the key is not a valid filter.
     */
    public function __get(string $name)
    {
        $filters = $this->filters();

        if (array_key_exists($name, $filters))
        {
            return $this->request->input($name);
        }

        throw new InvalidArgumentException("Filter key '{$name}' not found.");
    }

    /**
     * Check if any filters are present in the request.
     *
     * @return bool True if at least one filter parameter is present in the request.
     */
    public function hasFilter(): bool
    {
        foreach ($this->filters() as $name => $method)
        {
            if ($this->request->filled($name))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply all applicable filters to the query builder.
     *
     * Iterates through defined filters and applies them to the query if their corresponding
     * request parameters are present. If a filter is not present in the request but has a
     * default value, the default is applied instead.
     *
     * @param Builder $query The Eloquent query builder.
     * @return Builder The modified query builder with filters applied.
     */
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
     * Define default values for filter parameters.
     *
     * Override this method to provide default values for filters when they are not
     * present in the request. The returned array should map filter keys to default values.
     *
     * @return array An associative array of filter keys to default values.
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Map request parameter keys to filter method names.
     *
     * This method must be implemented by child classes to define which request parameters
     * should trigger which filter methods. The returned array should map request parameter
     * keys to method names (without parentheses).
     *
     * @return array An associative array mapping request keys to filter method names.
     */
    abstract protected function filters(): array;
}