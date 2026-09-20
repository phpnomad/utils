<?php

namespace PHPNomad\Tests\Unit;

use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Helpers\Arr;

class ArrTest extends TestCase
{
    public function testDotReturnsDefaultWhenAnIntermediateValueIsNotAnArray(): void
    {
        $this->assertSame(
            'missing',
            Arr::dot(['user' => 'alex'], 'user.name', 'missing')
        );
        $this->assertSame(
            'missing',
            Arr::dot(['user' => 42], 'user.name', 'missing')
        );
    }

    public function testReplaceCombinesArraysWithoutNestingTheArgumentList(): void
    {
        $this->assertSame(
            ['settings' => ['color' => 'red'], 'enabled' => true],
            Arr::replace(
                ['settings' => ['color' => 'blue', 'size' => 'm'], 'enabled' => true],
                ['settings' => ['color' => 'red']]
            )
        );
    }

    public function testReplaceRecursiveCombinesNestedArrays(): void
    {
        $this->assertSame(
            ['settings' => ['color' => 'red', 'size' => 'm'], 'enabled' => true],
            Arr::replaceRecursive(
                ['settings' => ['color' => 'blue', 'size' => 'm'], 'enabled' => true],
                ['settings' => ['color' => 'red']]
            )
        );
    }

    public function testReplaceMethodsReturnAnEmptyArrayWithoutInputs(): void
    {
        $this->assertSame([], Arr::replace());
        $this->assertSame([], Arr::replaceRecursive());
    }

    public function testReplaceMethodsHonorNamedVariadicCallOrder(): void
    {
        $first = ['settings' => ['color' => 'blue', 'size' => 'm'], 'enabled' => true];
        $second = ['settings' => ['color' => 'red']];

        $this->assertSame(
            ['settings' => ['color' => 'red'], 'enabled' => true],
            Arr::replace(first: $first, second: $second)
        );
        $this->assertSame(
            ['settings' => ['color' => 'red', 'size' => 'm'], 'enabled' => true],
            Arr::replaceRecursive(first: $first, second: $second)
        );
    }

    public function testComparableArrayHelpersPreserveKeysAndValues(): void
    {
        $first = new ArrStringableValue('first');
        $duplicate = new ArrStringableValue('first');
        $second = new ArrStringableValue('second');

        $this->assertSame(
            ['first' => $first, 'second' => $second],
            Arr::unique(['first' => $first, 'duplicate' => $duplicate, 'second' => $second])
        );
        $this->assertSame(
            ['first' => $first],
            Arr::intersect(['first' => $first, 'second' => $second], [$duplicate])
        );
        $this->assertSame(
            ['second' => $second],
            Arr::diff(['first' => $first, 'second' => $second], [$duplicate])
        );
    }

    public function testPluckUsesTheDefaultForScalarItems(): void
    {
        $this->assertSame(
            ['missing', 'found'],
            Arr::pluck([42, ['name' => 'found']], 'name', 'missing')
        );
    }

    /**
     * @dataProvider hasDottedPathCases
     * @param array<string, mixed> $subject
     */
    public function testHasWalksDottedPaths(array $subject, string $dot, bool $expected): void
    {
        $this->assertSame($expected, Arr::has($subject, $dot));
    }

    /** @return iterable<string, array{array<string, mixed>, string, bool}> */
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

final class ArrStringableValue
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
