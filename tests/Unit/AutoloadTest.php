<?php

namespace PHPNomad\Tests\Unit;

use PHPNomad\Core\Exceptions\ItemNotFound;
use PHPNomad\Core\Tests\TestCase;

class AutoloadTest extends TestCase
{
    public function testLegacyItemNotFoundClassLoadsThroughComposer(): void
    {
        $this->assertTrue(class_exists(ItemNotFound::class));
        $this->assertInstanceOf(\Exception::class, new ItemNotFound());
    }
}
