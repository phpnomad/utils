<?php

namespace PHPNomad\Tests\Unit;

use InvalidArgumentException;
use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Processors\ArrayProcessor;

class ArrayProcessorTest extends TestCase
{
    public function testFlipPreservesValidBehavior(): void
    {
        $processor = new ArrayProcessor(['first' => 'one', 'second' => 2]);

        $returned = $processor->flip();

        $this->assertSame($processor, $returned);
        $this->assertSame(['one' => 'first', 2 => 'second'], $processor->toArray());
    }

    public function testFlipRejectsInvalidItemsBeforeMutation(): void
    {
        $invalid = new \stdClass();
        $processor = new ArrayProcessor(['valid', $invalid]);

        $this->assertInvalidOperationLeavesSubject(
            $processor,
            static function () use ($processor): void {
                $processor->flip();
            },
            ['valid', $invalid]
        );
    }

    public function testHydrateRejectsInvalidItemsBeforeMutation(): void
    {
        $processor = new ArrayProcessor([['value'], 42]);

        $this->assertInvalidOperationLeavesSubject(
            $processor,
            static function () use ($processor): void {
                $processor->hydrate(ArrayProcessorFixture::class);
            },
            [['value'], 42]
        );
    }

    public function testFlattenRejectsInvalidGroupsBeforeMutation(): void
    {
        $processor = new ArrayProcessor([[['value' => 1]], 42]);

        $this->assertInvalidOperationLeavesSubject(
            $processor,
            static function () use ($processor): void {
                $processor->flatten();
            },
            [[['value' => 1]], 42]
        );
    }

    public function testGroupRejectsInvalidItemsBeforeMutation(): void
    {
        $processor = new ArrayProcessor([['value' => 1], 42]);

        $this->assertInvalidOperationLeavesSubject(
            $processor,
            static function () use ($processor): void {
                $processor->group('value');
            },
            [['value' => 1], 42]
        );
    }

    public function testReduceKeepsAnArrayAccumulatorInTheFluentProcessor(): void
    {
        $processor = new ArrayProcessor([
            ['id' => 10],
            ['id' => 20],
        ]);

        $returned = $processor->reduce(
            static function (array $ids, $item): array {
                if (!is_array($item) || !is_int($item['id'] ?? null)) {
                    throw new \UnexpectedValueException('Expected an integer ID.');
                }
                $ids[] = $item['id'];

                return $ids;
            },
            []
        );

        $this->assertSame($processor, $returned);
        $this->assertSame([10, 20], $processor->toArray());
    }

    public function testComparableOperationsPreserveSupportedValues(): void
    {
        $stringable = new ArrayProcessorStringableValue('object');
        $values = [false, 1.5, 2, 'three', null, $stringable];

        foreach ($values as $value) {
            $unique = new ArrayProcessor([$value]);
            $this->assertSame($unique, $unique->unique());
            $this->assertSame([$value], $unique->toArray());
        }

        $nativeCollision = new ArrayProcessor([false, null]);
        $nativeCollision->unique();
        $this->assertSame([false], $nativeCollision->toArray());

        $intersect = new ArrayProcessor($values);
        $this->assertSame($intersect, $intersect->intersect($values));
        $this->assertSame($values, $intersect->toArray());

        $diff = new ArrayProcessor($values);
        $this->assertSame($diff, $diff->diff([]));
        $this->assertSame($values, $diff->toArray());
    }

    /** @dataProvider invalidComparableOperations */
    public function testComparableOperationsRejectInvalidSubjectsBeforeMutation(string $operation): void
    {
        $processor = new ArrayProcessor(['valid', ['invalid']]);

        $this->assertInvalidOperationLeavesSubject(
            $processor,
            static function () use ($processor, $operation): void {
                $processor->$operation(['valid']);
            },
            ['valid', ['invalid']]
        );
    }

    /** @return iterable<string, array{string}> */
    public static function invalidComparableOperations(): iterable
    {
        yield 'unique' => ['unique'];
        yield 'intersect' => ['intersect'];
        yield 'diff' => ['diff'];
    }

    /** @dataProvider comparableOperationsWithInputs */
    public function testComparableOperationsRejectInvalidInputsBeforeMutation(string $operation): void
    {
        $processor = new ArrayProcessor(['valid']);

        $this->assertInvalidOperationLeavesSubject(
            $processor,
            static function () use ($processor, $operation): void {
                $processor->$operation([['invalid']]);
            },
            ['valid']
        );
    }

    /** @return iterable<string, array{string}> */
    public static function comparableOperationsWithInputs(): iterable
    {
        yield 'intersect' => ['intersect'];
        yield 'diff' => ['diff'];
    }

    /**
     * @param callable(): void $operation
     * @param array<array-key, mixed> $expected
     */
    private function assertInvalidOperationLeavesSubject(
        ArrayProcessor $processor,
        callable $operation,
        array $expected
    ): void {
        try {
            $operation();
            $this->fail('Expected invalid processor input to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->assertSame($expected, $processor->toArray());
    }
}

final class ArrayProcessorFixture
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

final class ArrayProcessorStringableValue
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
