<?php

namespace PHPNomad\Utils\Processors;

use InvalidArgumentException;
use PHPNomad\Core\Exceptions\ItemNotFound;
use PHPNomad\Utils\Helpers\Arr;
use PHPNomad\Utils\Helpers\Obj;

/** @phpstan-consistent-constructor */
class ListFilter
{

    /** @var array<string, mixed> */
    protected array $filter_args = [];

    /** @var array<array-key, object> */
    protected array $items;
    protected ?int $limit = null;
    protected ?int $offset = null;

    /** @param array<array-key, object> $items */
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
        $this->filter_args["{$field}__less_than"] = $number;

        return $this;
    }

    /**
     * @param string $field
     * @param numeric $number
     * @return $this
     */
    public function lessThanOrEqual(string $field, $number)
    {
        $this->filter_args["{$field}__less_than_or_equal_to"] = $number;

        return $this;
    }

    /**
     * @param string $field
     * @param numeric $number
     * @return $this
     */
    public function greaterThan(string $field, $number)
    {
        $this->filter_args["{$field}__greater_than"] = $number;

        return $this;
    }

    /**
     * @param string $field
     * @param numeric $number
     * @return $this
     */
    public function greaterThanOrEqual(string $field, $number)
    {
        $this->filter_args["{$field}__greater_than_or_equal_to"] = $number;

        return $this;
    }

    /**
     * @param string $field
     * @param callable $callback
     * @return $this
     */
    public function filterFromCallback(string $field, callable $callback)
    {
        $this->filter_args["{$field}__callback"] = $callback;

        return $this;
    }

    /**
     * Sets the query to only include items that are not an of the provided instances.
     *
     * @param class-string ...$values The values to filter.
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
     * @param class-string ...$values The values to filter.
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
     * @param class-string ...$values The values to filter.
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
     * @param scalar|\Stringable|null ...$values The values to filter.
     *
     * @return $this
     */
    public function notIn(string $field, ...$values)
    {
        $this->filter_args["{$field}__not_in"] = $values;

        return $this;
    }

    /**
     * Sets the query to filter out items whose field does not have all the provided values.
     *
     * @param string $field The field to check against.
     * @param scalar|\Stringable|null ...$values The values to filter.
     *
     * @return $this
     */
    public function and(string $field, ...$values)
    {
        $this->filter_args["{$field}__and"] = $values;

        return $this;
    }

    /**
     * Sets the query to filter out items whose field does not have any of the provided values.
     *
     * @param string $field The field to check against.
     * @param scalar|\Stringable|null ...$values The values to filter.
     *
     * @return $this
     */
    public function in(string $field, ...$values)
    {
        $this->filter_args["{$field}__in"] = $values;

        return $this;
    }


    /**
     * Sets the query to filter out items whose value is not identical to the provided value.
     *
     * @param string $field The field to check against.
     * @param scalar|\Stringable|null $value The value to check.
     *
     * @return $this
     */
    public function equals(string $field, $value)
    {
        $this->filter_args["{$field}__equals"] = $value;

        return $this;
    }

    /**
     * Sets the query to filter out items whose key has any of the provided values.
     *
     * @param array-key ...$values The values to filter.
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
     * @param array-key ...$values The values to filter.
     *
     * @return $this
     */
    public function keyIn(...$values)
    {
        $this->filter_args['filter_enum_key__in'] = $values;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return array{field: string, type: string}
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
                if (!is_callable($arg)) {
                    throw new InvalidArgumentException("Filter '$key' must contain a callable value.");
                }
                $valid = $arg($value);
            } else {

                $fields = Arr::intersect(
                    $this->getComparableValues(Arr::wrap($arg), $key),
                    $this->getComparableValues(Arr::wrap($value), $key)
                );

                switch ($type) {
                    case 'not_in':
                        $valid = empty($fields);
                        break;
                    case 'in':
                        $valid = !empty($fields);
                        break;
                    case 'and':
                        if (!is_array($arg)) {
                            throw new InvalidArgumentException("Filter '$key' must contain an array of values.");
                        }
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
     * @return array<array-key, array-key>
     */
    protected function filterItemKeys(): array
    {
        $items = array_keys($this->items);

        // Filter out keys, if keys are specified
        if (isset($this->filter_args['filter_enum_key__in'])) {
            $includedKeys = $this->filter_args['filter_enum_key__in'];
            if (!$this->isArrayOfKeys($includedKeys)) {
                throw new InvalidArgumentException('Key inclusion filters must contain an array of integer or string keys.');
            }
            $items = $this->keepMatchingKeys($items, $includedKeys);
            unset($this->filter_args['filter_enum_key__in']);
        }

        if (isset($this->filter_args['filter_enum_key__not_in'])) {
            $excludedKeys = $this->filter_args['filter_enum_key__not_in'];
            if (!$this->isArrayOfKeys($excludedKeys)) {
                throw new InvalidArgumentException('Key exclusion filters must contain an array of integer or string keys.');
            }
            $items = $this->removeMatchingKeys($items, $excludedKeys);
            unset($this->filter_args['filter_enum_key__not_in']);
        }

        return $items;
    }

    /**
     * @param mixed $value
     * @phpstan-assert-if-true array<array-key, array-key> $value
     */
    private function isArrayOfKeys($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!is_int($item) && !is_string($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<array-key, array-key> $items
     * @param array<array-key, array-key> $includedKeys
     * @return array<array-key, array-key>
     */
    private function keepMatchingKeys(array $items, array $includedKeys): array
    {
        $result = [];
        foreach (Arr::intersect($items, $includedKeys) as $key => $item) {
            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * @param array<array-key, array-key> $items
     * @param array<array-key, array-key> $excludedKeys
     * @return array<array-key, array-key>
     */
    private function removeMatchingKeys(array $items, array $excludedKeys): array
    {
        $result = [];
        foreach (Arr::diff($items, $excludedKeys) as $key => $item) {
            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $values
     * @return array<array-key, scalar|\Stringable|null>
     */
    private function getComparableValues(array $values, string $filter): array
    {
        $comparable = [];
        foreach ($values as $key => $value) {
            if (!is_scalar($value) && $value !== null && !($value instanceof \Stringable)) {
                throw new InvalidArgumentException(
                    "Filter '$filter' must contain only scalar, null, or stringable values."
                );
            }
            $comparable[$key] = $value;
        }

        return $comparable;
    }

    /**
     * Queries a loader registry.
     *
     * @return array<array-key, object> Array of registry items.
     */
    public function filter(): array
    {
        $results = [];
        $actualLimit = null;
        if ($this->limit !== null) {
            $actualLimit = $this->limit + ($this->offset ?? 0);
        }

        foreach ($this->filterItemKeys() as $item_key) {
            if (!isset($this->items[$item_key])) {
                continue;
            }

            $item = $this->filterItem($this->items[$item_key]);

            if ($item) {
                $results[$item_key] = $item;
            }

            if ($actualLimit !== null && $actualLimit === count($results)) {
                break;
            }
        }

        if ($this->offset !== null) {
            $results = Arr::after($results, $this->offset);
        }

        return $results;
    }

    /**
     * Stops after reaching the specified number of results.
     *
     * @param int|null $limit Must be positive when provided.
     * @return $this
     * @throws InvalidArgumentException
     */
    public function limit(?int $limit = null)
    {
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('Limit must be null or a positive integer.');
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int|null $offset Must not be negative when provided.
     * @return $this
     * @throws InvalidArgumentException
     */
    public function offset(?int $offset = null)
    {
        if ($offset !== null && $offset < 0) {
            throw new InvalidArgumentException('Offset must be null or a non-negative integer.');
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilterArgs(): array
    {
        return $this->filter_args;
    }

    /**
     * @param array<string, mixed> $filterArgs
     * @param array<array-key, object> $inputData
     */
    public static function seed(array $filterArgs, array $inputData): ListFilter
    {
        $new = new static($inputData);
        $new->filter_args = $filterArgs;

        return $new;
    }
}
