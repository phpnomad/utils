<?php

namespace PHPNomad\Tests\Unit;

use InvalidArgumentException;
use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Processors\ListFilter;

class ListFilterTest extends TestCase
{
    public function testMalformedCallbackFilterIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ListFilter::seed(['id__callback' => 42], $this->items(1))->filter();
    }

    public function testMalformedAndFilterIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ListFilter::seed(['id__and' => 42], $this->items(1))->filter();
    }

    /**
     * @dataProvider malformedKeyFilterCases
     * @param array<string, mixed> $filter
     */
    public function testMalformedKeyFilterIsRejected(array $filter): void
    {
        $this->expectException(InvalidArgumentException::class);

        ListFilter::seed($filter, $this->items(1))->filter();
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function malformedKeyFilterCases(): iterable
    {
        yield 'include is not an array' => [['filter_enum_key__in' => 42]];
        yield 'exclude is not an array' => [['filter_enum_key__not_in' => 42]];
        yield 'include has a non-key value' => [['filter_enum_key__in' => [new \stdClass()]]];
        yield 'exclude has a non-key value' => [['filter_enum_key__not_in' => [new \stdClass()]]];
    }

    public function testNormalBuilderFiltersRemainSupported(): void
    {
        $result = (new ListFilter($this->items(1, 2, 3)))
            ->filterFromCallback('id', static fn (int $id): bool => $id >= 2)
            ->and('id', 2)
            ->filter();

        $this->assertSame([2], $this->ids($result));
    }

    public function testUnknownSeedOperatorKeepsExistingPassThroughBehavior(): void
    {
        $result = ListFilter::seed(
            ['id__future_operator' => 'unused'],
            $this->items(1, 2)
        )->filter();

        $this->assertSame([1, 2], $this->ids($result));
    }

    public function testLimitAndOffsetReturnTheRequestedWindow(): void
    {
        $items = $this->items(1, 2, 3, 4, 5);

        $result = (new ListFilter($items))->limit(2)->offset(2)->filter();

        $this->assertSame([3, 4], $this->ids($result));
    }

    public function testOffsetWithoutLimitReturnsEveryRemainingItem(): void
    {
        $items = $this->items(1, 2, 3, 4, 5);

        $result = (new ListFilter($items))->offset(2)->filter();

        $this->assertSame([3, 4, 5], $this->ids($result));
    }

    public function testLimitWithoutOffsetStopsAtTheRequestedCount(): void
    {
        $items = $this->items(1, 2, 3, 4, 5);

        $result = (new ListFilter($items))->limit(2)->filter();

        $this->assertSame([1, 2], $this->ids($result));
    }

    /** @dataProvider invalidLimitCases */
    public function testInvalidLimitIsRejectedWithoutChangingThePreviousLimit(int $invalidLimit): void
    {
        $filter = (new ListFilter($this->items(1, 2, 3)))->limit(2);

        try {
            $filter->limit($invalidLimit);
            $this->fail('Expected an invalid limit to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('positive', $e->getMessage());
        }

        $this->assertSame([1, 2], $this->ids($filter->filter()));
    }

    /** @return iterable<string, array{int}> */
    public static function invalidLimitCases(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    public function testNegativeOffsetIsRejectedWithoutChangingThePreviousOffset(): void
    {
        $filter = (new ListFilter($this->items(1, 2, 3)))->offset(1);

        try {
            $filter->offset(-1);
            $this->fail('Expected a negative offset to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('non-negative', $e->getMessage());
        }

        $this->assertSame([2, 3], $this->ids($filter->filter()));
    }

    public function testComparableFilterValuesRemainSupported(): void
    {
        $stringable = new ListFilterStringableValue('wanted');
        $items = [
            'first' => new ListFilterValueItem(false),
            'second' => new ListFilterValueItem(1.5),
            'third' => new ListFilterValueItem(2),
            'fourth' => new ListFilterValueItem('three'),
            'fifth' => new ListFilterValueItem(null),
            'sixth' => new ListFilterValueItem($stringable),
        ];

        $this->assertSame(
            $items,
            (new ListFilter($items))->in('value', false, 1.5, 2, 'three', null, $stringable)->filter()
        );
    }

    /**
     * @dataProvider invalidComparableFilterCases
     * @param array<string, mixed> $filter
     */
    public function testComparableFiltersRejectInvalidValues(array $filter, object $item): void
    {
        $this->expectException(InvalidArgumentException::class);

        ListFilter::seed($filter, [$item])->filter();
    }

    /** @return iterable<string, array{array<string, mixed>, object}> */
    public static function invalidComparableFilterCases(): iterable
    {
        yield 'invalid filter argument' => [
            ['value__in' => [['invalid']]],
            new ListFilterValueItem('valid'),
        ];
        yield 'invalid item value' => [
            ['value__in' => ['valid']],
            new ListFilterValueItem([['invalid']]),
        ];
    }

    /**
     * @param int ...$ids
     * @return array<array-key, ListFilterItem>
     */
    private function items(int ...$ids): array
    {
        return array_map(
            static fn (int $id): ListFilterItem => new ListFilterItem($id),
            $ids
        );
    }

    /**
     * @param array<array-key, object> $items
     * @return list<int>
     */
    private function ids(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            $this->assertInstanceOf(ListFilterItem::class, $item);
            $ids[] = $item->id;
        }

        return $ids;
    }
}

final class ListFilterItem
{
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}

final class ListFilterValueItem
{
    /** @var mixed */
    public $value;

    /** @param mixed $value */
    public function __construct($value)
    {
        $this->value = $value;
    }
}

final class ListFilterStringableValue
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
