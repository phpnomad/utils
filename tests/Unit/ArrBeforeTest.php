<?php

namespace PHPNomad\Tests\Unit;

use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Helpers\Arr;

class ArrBeforeTest extends TestCase
{
    public function testBeforeReturnsMixedValuesBeforeTheRequestedPosition(): void
    {
        $first = ['nested' => true];
        $second = new \stdClass();

        $this->assertSame(
            ['first' => $first, 'second' => $second],
            Arr::before(['first' => $first, 'second' => $second, 'third' => 3], 2)
        );
    }
}
