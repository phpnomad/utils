<?php

namespace Phoenix\Utils\Processors;

use Phoenix\Core\Exceptions\ItemNotFound;
use Phoenix\Utils\Helpers\Arr;
use Phoenix\Utils\Helpers\Obj;

class ListFilter
{

    protected array $filter_args = [];
    protected array $items;

    public function __construct(array $items)
    {
        $this->items = $items;

    }

    /**
     * @param string $field
     * @param numeric $number
     * @return $this
     */
    public function lessThan(string $field, $number)
    {
        $this->filter_args['field__less_than'] = $number;

        return $this;
    }

    /**
     * @param string $field
     * @param numeric $number
     * @return $this
     */
    public function lessThanOrEqual(string $field, $number)
    {
        $this->filter_args['field__less_than_or_equal_to'] = $number;

        return $this;
    }

    /**
     * @param string $field
     * @param numeric $number
     * @return $this
     */
    public function greaterThan(string $field, $number)
    {
        $this->filter_args['field__greater_than'] = $number;

        return $this;
    }

    /**
     * @param string $field
     * @param numeric $number
     * @return $this
     */
    public function greaterThanOrEqual(string $field, $number)
    {
        $this->filter_args['field__greater_than_or_equal_to'] = $number;

        return $this;
    }

    /**
     * @param string $field
     * @param callable $callback
     * @return $this
     */
    public function filterFromCallback(string $field, callable $callback)
    {
        $this->filter_args['field__callback'] = $callback;

        return $this;
    }

    /**
     * Sets the query to only include items that are not an of the provided instances.
     *
     * @param array $values The values to filter.
     *
     * @return $this
     */
    public function notInstanceOf(...$values)
    {
        $this->filter_args['instanceof__not_in'] = $values;

        return $this;
    }

    /**
     * Sets the query to only include items that are an instance of all the provided instances.
     *
     * @param array $values The values to filter.
     *
     * @return $this
     */
    public function hasAllInstances(...$values)
    {
        $this->filter_args['instanceof__and'] = $values;

        return $this;
    }

    /**
     * Sets the query to only include items that are instance of any the provided instances.
     *
     * @param array $values The values to filter.
     *
     * @return $this
     */
    public function hasAnyInstances(...$values)
    {
        $this->filter_args['instanceof__in'] = $values;

        return $this;
    }

    /**
     * Sets the query to only include items that are instance provided instances.
     *
     * @param string $value the instance
     *
     * @return $this
     */
    public function instanceOf(string $value)
    {
        $this->filter_args['instanceof__equals'] = $value;

        return $this;
    }

    /**
     * Sets the query to filter out items whose field has any of the provided values.
     *
     * @param string $field The field to check against.
     * @param array $values The values to filter.
     *
     * @return $this
     */
    public function notIn(string $field, ...$values)
    {
        $this->filter_args['field__not_in'] = $values;

        return $this;
    }

    /**
     * Sets the query to filter out items whose field does not have all the provided values.
     *
     * @param string $field The field to check against.
     * @param array $values The values to filter.
     *
     * @return $this
     */
    public function and(string $field, ...$values)
    {
        $this->filter_args['field__and'] = $values;

        return $this;
    }

    /**
     * Sets the query to filter out items whose field does not have all the provided values.
     *
     * @param string $field The field to check against.
     * @param array $values The values to filter.
     *
     * @return $this
     */
    public function in(string $field, ...$values)
    {
        $this->filter_args['field__in'] = $values;

        return $this;
    }


    /**
     * Sets the query to filter out items whose value is not identical to the provided value.
     *
     * @param string $field The field to check against.
     * @param mixed $value The value to check.
     *
     * @return $this
     */
    public function equals(string $field, $value)
    {
        $this->filter_args['field__equals'] = $value;

        return $this;
    }

    /**
     * Sets the query to filter out items whose key has any of the provided values.
     *
     * @param array $values The values to filter.
     *
     * @return $this
     */
    public function keyNotIn(...$values)
    {
        $this->filter_args['filter_enum_key__not_in'] = $values;

        return $this;
    }

    /**
     * Sets the query to filter out items whose key does not have all the provided values.
     *
     * @param array $values The values to filter.
     *
     * @return $this
     */
    public function keyIn(...$values)
    {
        $this->filter_args['filter_enum_key__in'] = $values;

        return $this;
    }

    /**
     * @param $key
     *
     * @return array
     */
    protected function prepareField($key): array
    {
        // Process the argument key
        $processed = explode('__', $key);

        // Set the field type to the first item in the array.
        $field = $processed[0];

        // If there was some specificity after a __, use it.
        $type = count($processed) > 1 ? $processed[1] : 'in';

        return ['field' => $field, 'type' => $type];
    }


    /**
     * Determines if a registry item passes the arguments.
     *
     * @param object $item Item to filter
     *
     * @return ?object The instance, if it matches the filters.
     */
    protected function filterItem(object $item): ?object
    {
        $valid = true;

        foreach ($this->filter_args as $key => $arg) {
            /* @var string $field */
            /* @var string $type */
            extract($this->prepareField($key));


            try {
                if ('instanceof' === $field) {
                    $value = array_keys(array_merge(class_uses($item), class_implements($item), class_parents($item)));
                } else {
                    $value = Obj::pluck($item, $field);
                }
            } catch (ItemNotFound $e) {
                continue;
            }

            if ($type === 'callback') {
                $valid = $arg($value);
            } else {

                $fields = Arr::intersect(Arr::wrap($arg), Arr::wrap($value));

                switch ($type) {
                    case 'not_in':
                        $valid = empty($fields);
                        break;
                    case 'in':
                        $valid = !empty($fields);
                        break;
                    case 'and':
                        $valid = count($fields) === count($arg);
                        break;
                    case 'equals':
                        $valid = isset($fields[0]) && $fields[0] === $arg;
                        break;
                    case 'less_than':
                        $valid = array_sum(Arr::wrap($value)) < $arg;
                        break;
                    case 'greater_than':
                        $valid = array_sum(Arr::wrap($value)) > $arg;
                        break;
                    case 'greater_than_or_equal_to':
                        $valid = array_sum(Arr::wrap($value)) >= $arg;
                        break;
                    case 'less_than_or_equal_to':
                        $valid = array_sum(Arr::wrap($value)) <= $arg;
                        break;
                }
            }

            if (false === $valid) {
                break;
            }
        }

        if (true === $valid) {
            return $item;
        }

        return null;
    }


    /**
     * Pre-filters the list of items.
     *
     * @return array
     */
    protected function filterItemKeys(): array
    {
        $items = array_keys($this->items);

        // Filter out keys, if keys are specified
        if (isset($this->filter_args['filter_enum_key__in'])) {
            $items = Arr::intersect($items, $this->filter_args['filter_enum_key__in']);
            unset($this->filter_args['filter_enum_key__in']);
        }

        if (isset($this->filter_args['filter_enum_key__not_in'])) {
            $items = Arr::diff($items, $this->filter_args['filter_enum_key__not_in']);
            unset($this->filter_args['filter_enum_key__not_in']);
        }

        return $items;
    }


    /**
     * Finds the first loader item that matches the provided arguments.
     *
     * @return ?object loader item if found.
     */
    public function find(): ?object
    {
        foreach ($this->filterItemKeys() as $item_key) {
            if (!isset($this->items[$item_key])) {
                continue;
            }

            $item = $this->filterItem($this->items[$item_key]);

            if ($item) {
                return $item;
            }
        }

        return null;
    }


    /**
     * Queries a loader registry.
     *
     * @return object[] Array of registry items.
     */
    public function filter(): array
    {
        $results = [];
        foreach ($this->filterItemKeys() as $item_key) {
            if (!isset($this->items[$item_key])) {
                continue;
            }

            $item = $this->filterItem($this->items[$item_key]);

            if ($item) {
                $results[$item_key] = $item;
            }
        }

        return $results;
    }

    /**
     * Seeds a new instance of the list filter, using pre-generated arguments and items.
     *
     * @param array $items
     * @param array $args
     *
     * @return static
     */
    public static function seed(array $items, array $args)
    {
        $self = new static($items);
        $self->filter_args = $args;

        return $self;
    }

}
