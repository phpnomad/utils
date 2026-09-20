<?php

namespace PHPNomad\Utils\Helpers;

use Closure;
use ReflectionException;
use ReflectionFunction;
use SplFileObject;

class ClosureAdapter
{
    /**
     * Converts a closure to something that can be safely converted to a string.
     *
     * @param Closure $data
     *
     * @return array{string, array<string, mixed>}
     * @throws ReflectionException
     */
    public static function getClosureData(Closure $data): array
    {
        $ref  = new ReflectionFunction($data);
        $filename = $ref->getFileName();
        if ($filename === false) {
            throw new ReflectionException('Cannot read source for an internal closure.');
        }

        try {
            $file = new SplFileObject($filename);
        } catch (\RuntimeException $e) {
            throw new ReflectionException('Cannot open closure source file.', 0, $e);
        }
        $file->seek($ref->getStartLine() - 1);
        $content = '';
        while ($file->key() < $ref->getEndLine()) {
            $content .= $file->current();
            $file->next();
        }

        return array(
            $content,
            $ref->getStaticVariables(),
        );
    }
}
