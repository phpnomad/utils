<?php

namespace PHPNomad\Tests\Unit;

use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Processors\ArrayProcessor;

class ArrayProcessorTest extends TestCase
{
    public function testReduceKeepsAnArrayAccumulatorInTheFluentProcessor(): void
    {
        $processor = new ArrayProcessor([
            ['id' => 10],
            ['id' => 20],
        ]);

        $returned = $processor->reduce(
            static function (array $ids, $item): array {
                /** @var array{id: int} $item */
                $ids[] = $item['id'];

                return $ids;
            },
            []
        );

        $this->assertSame($processor, $returned);
        $this->assertSame([10, 20], $processor->toArray());
    }
}
