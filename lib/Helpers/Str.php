<?php

namespace Phoenix\Core\Helpers;

use Closure;
use Phoenix\Utils\Helpers\Arr;
use Phoenix\Utils\Helpers\ClosureAdapter;
use ReflectionException;

class Str
{
    /**
     * Makes a word plural.
     *
     * @param $singular
     * @param $count
     * @param $plural
     * @return string
     */
    public static function pluarize($singular, $count, $plural = 's'): string
    {
        if ($count === 1) {
            return $singular;
        }

        return $singular . $plural;
    }

    /**
     * Converts the given string to use camelCase
     *
     * @param string $subject
     *
     * @return string
     */
    public static function camelCase(string $subject): string
    {
        return lcfirst(static::pascalCase($subject));
    }

    /**
     * Converts the given string to use PascalCase
     *
     * @param string $subject
     *
     * @return string
     */
    public static function pascalCase(string $subject): string
    {
        return Arr::process(explode(' ', str_replace(['-', '_'], ' ', $subject)))
            ->map(fn (string $piece) => ucfirst($piece))
            ->setSeparator('')
            ->toString();
    }

    /**
     * Creates a 32 character hash from the provided value.
     *
     * @param mixed $data The value to hash.
     * @param ?string $key Optional. The secret key to provide. Required if hash needs to be secure.
     *
     * @return string a 32 character hash from the provided value.
     * @throws ReflectionException
     */
    public static function createHash(mixed $data, ?string $key = null): string
    {
        // If object, convert to array.
        if (is_object($data)) {
            $data = (array)$data;
        }

        // Normalize the array
        if (is_array($data)) {
            $data = Arr::normalize($data);
        }

        // Convert closures
        if ($data instanceof Closure) {
            $data = ClosureAdapter::getClosureData($data);
        }

        if (!$key) {
            return hash('md5', serialize($data));
        } else {
            return hash_hmac('md5', serialize($data), $key);
        }
    }

    /**
     * Trim the specified item from the end of the string.
     *
     * @param string $haystack Original string
     * @param string $needle String to check
     *
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Trim the specified item from the front of the string.
     *
     * @param string $haystack Original string
     * @param string $needle String to check
     *
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Trim the specified item from the end of the string.
     *
     * @param string $subject Original string
     * @param string $trim Content to trim from the end, if it exists.
     *
     * @return string
     */
    public static function trimTrailing(string $subject, string $trim): string
    {
        if ($subject === $trim) {
            return '';
        }

        if (self::endsWith($subject, $trim)) {
            return substr($subject, 0, strlen($subject) - strlen($trim));
        }

        return $subject;
    }

    /**
     * Trim the specified item from the front of the string.
     *
     * @param string $subject Original string
     * @param string $trim Content to trim from the beginning, if it exists.
     *
     * @return string
     */
    public static function trimLeading(string $subject, string $trim): string
    {
        if ($subject === $trim) {
            return '';
        }

        if (self::startsWith($subject, $trim)) {
            return substr($subject, strlen($trim));
        }

        return $subject;
    }

    /**
     * Append the specified string, if that string is not already appended.
     *
     * @param string $subject The subject to append to
     * @param string $append The string to append if it isn't already appended.
     *
     * @return string
     */
    public static function append(string $subject, string $append): string
    {
        if (self::endsWith($subject, $append)) {
            return $subject;
        }

        return $subject . $append;
    }

    /**
     * Prepends the specified string, if that string is not already prepended.
     *
     * @param string $subject The subject to prepend to
     * @param string $prepend The string to prepend if it isn't already prepended.
     *
     * @return string
     */
    public static function prepend(string $subject, string $prepend): string
    {
        if (self::startsWith($subject, $prepend)) {
            return $subject;
        }

        return $prepend . $subject;
    }

    public static function basename($subject, $divider = '/'): ?string
    {
        $items = explode($divider, $subject);

        return array_pop($items);
    }

    public static function after($subject, $after = ' '): string
    {
        return substr($subject, strpos($subject, $after) + 1);
    }

    public static function before($subject, $before = ' '): string
    {
        return substr($subject, 0, strpos($subject, $before) - strlen($subject));
    }

    public static function getBuffer(callable $callback, ...$args): string
    {
        ob_start();
        $callback(...$args);
        return (string) ob_get_clean();
    }
}