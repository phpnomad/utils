<?php

namespace PHPNomad\Utils\Helpers;

use Exception;
use PHPNomad\Core\Exceptions\ItemNotFound;

class Obj
{
    /**
     * Gets a field from an object. Attempts to call get_field, and the fields accessor.
     *
     * @return mixed
     * @throws ItemNotFound
     */
    public static function pluck(object $value, string $fields)
    {
        $fields = explode('.', $fields);
        foreach ($fields as $field) {
            $name = ucfirst($field);
            $camelGetter = "get$name";
            $snakeGetter = "get_$field";
            // Bail early if this field is not in this object.
            if (is_callable([$value, $camelGetter])) {
                $value = $value->{$camelGetter}();
            } elseif (is_callable([$value, $snakeGetter])) {
                $value = $value->{$snakeGetter}();
            } else {
                try {
                    $value = $value->$field;
                } catch (Exception $e) {
                    throw new ItemNotFound();
                }
            }
        }

        return $value;
    }

    /**
     * @param class-string $instance
     * @param class-string $implements
     * @param class-string ...$moreImplements
     * @return bool
     */
    public static function implements(string $instance, string $implements, string ...$moreImplements): bool
    {
        $items = class_implements($instance);
        $implements = Arr::merge([$implements], $moreImplements);
        $test = Arr::intersect($items, $implements);

        return count($test) === count($implements);
    }
}
