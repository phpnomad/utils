<?php

namespace PHPNomad\Utils\Helpers;

use Exception;
use PHPNomad\Core\Exceptions\ItemNotFound;

class Obj
{
    /**
     * Gets a field from an object. Attempts to call get_field, and the fields accessor.
     *
     * @throws ItemNotFound
     */
    public static function pluck(object $value, string $fields)
    {
        $fields = explode('.', $fields);
        foreach ($fields as $field) {
            $name = ucfirst($field);
            // Bail early if this field is not in this object.
            if (is_callable([$value, "get$name"])) {
                $value = call_user_func([$value, "get$name"]);
            } elseif (is_callable([$value, "get_$field"])) {
                $value = call_user_func([$value, "get_$field"]);
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
}