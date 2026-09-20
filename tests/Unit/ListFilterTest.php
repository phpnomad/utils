<?php

namespace PHPNomad\Tests\Unit;

use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Processors\ListFilter;

class ListFilterTest extends TestCase
{
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
