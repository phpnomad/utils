<?php

namespace PHPNomad\Utils\Helpers;

use PHPNomad\Core\Exceptions\ItemNotFound;
use Throwable;

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
            if (!is_object($value)) {
                throw new ItemNotFound("Cannot read field '$field' from a non-object value.");
            }

            $name = ucfirst($field);
            $camelGetter = "get$name";
            $snakeGetter = "get_$field";
            // Bail early if this field is not in this object.
            if (is_callable([$value, $camelGetter])) {
                $value = $value->{$camelGetter}();
            } elseif (is_callable([$value, $snakeGetter])) {
                $value = $value->{$snakeGetter}();
            } elseif (array_key_exists($field, get_object_vars($value))) {
                try {
                    $value = self::readPublicProperty($value, $field);
                } catch (Throwable $e) {
                    throw new ItemNotFound("Cannot read field '$field'.", 0, $e);
                }
            } elseif (property_exists($value, $field)) {
                throw new ItemNotFound("Cannot read inaccessible or uninitialized field '$field'.");
            } elseif (method_exists($value, '__get')) {
                $value = $value->$field;
            } else {
                throw new ItemNotFound("Field '$field' was not found.");
            }
        }

        return $value;
    }

    /**
     * @return mixed
     * @throws Throwable
     */
    private static function readPublicProperty(object $value, string $field)
    {
        return $value->$field;
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
