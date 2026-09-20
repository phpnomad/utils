<?php

namespace PHPNomad\Tests\Unit;

use Closure;
use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Helpers\ClosureAdapter;
use ReflectionException;
use RuntimeException;

class ClosureAdapterTest extends TestCase
{
    public function testInternalClosureIsRejectedWithTheDeclaredException(): void
    {
        $closure = Closure::fromCallable('strlen');

        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('internal closure');

        ClosureAdapter::getClosureData($closure);
    }

    public function testUnreadableClosureSourceIsWrappedInTheDeclaredException(): void
    {
        $closure = eval('return static function (): void {};');
        if (!$closure instanceof Closure) {
            $this->fail('Expected eval to return a closure.');
        }

        try {
            ClosureAdapter::getClosureData($closure);
            $this->fail('Expected unreadable closure source to be rejected.');
        } catch (ReflectionException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
            $this->assertStringContainsString('source file', $e->getMessage());
        }
    }
}
