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
