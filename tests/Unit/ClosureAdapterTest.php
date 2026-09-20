<?php

namespace PHPNomad\Tests\Unit;

use Closure;
use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Helpers\ClosureAdapter;
use ReflectionException;

class ClosureAdapterTest extends TestCase
{
    public function testInternalClosureIsRejectedWithTheDeclaredException(): void
    {
        $closure = Closure::fromCallable('strlen');

        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('internal closure');

        ClosureAdapter::getClosureData($closure);
    }
}
