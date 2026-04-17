<?php

namespace PHPNomad\Tests\Unit;

use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Helpers\Arr;

class ArrTest extends TestCase
{
    /**
     * @dataProvider hasDottedPathCases
     */
    public function testHasWalksDottedPaths(array $subject, string $dot, bool $expected): void
    {
        $this->assertSame($expected, Arr::has($subject, $dot));
    }

    public static function hasDottedPathCases(): iterable
    {
        yield 'single key present' => [
            ['key' => 'value'],
            'key',
            true,
        ];

        yield 'single key missing' => [
            ['key' => 'value'],
            'missing',
            false,
        ];

        yield 'nested key present' => [
            ['user' => ['name' => 'John']],
            'user.name',
            true,
        ];

        yield 'nested key missing' => [
            ['user' => ['name' => 'John']],
            'user.email',
            false,
        ];

        yield 'three-level nested key present' => [
            ['a' => ['b' => ['c' => 1]]],
            'a.b.c',
            true,
        ];

        yield 'top segment missing' => [
            ['user' => ['name' => 'John']],
            'missing.name',
            false,
        ];

        // Regression: before the iterative rewrite, the recursive version
        // TypeErrored here because it passed a scalar back into itself.
        yield 'scalar intermediate returns false (no TypeError)' => [
            ['user' => 'alex'],
            'user.name',
            false,
        ];

        yield 'integer intermediate returns false (no TypeError)' => [
            ['engagementTypes' => ['referredSiteVisit' => 42]],
            'engagementTypes.referredSiteVisit.engagementValue',
            false,
        ];

        // Preserves existing `isset` semantics: null leaves read as "absent".
        yield 'null leaf counts as absent' => [
            ['user' => null],
            'user',
            false,
        ];

        yield 'null intermediate returns false' => [
            ['user' => null],
            'user.name',
            false,
        ];
    }
}
