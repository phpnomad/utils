<?php

namespace PHPNomad\Tests\Unit;

use PHPNomad\Core\Exceptions\ItemNotFound;
use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Helpers\Obj;
use RuntimeException;

class ObjTest extends TestCase
{
    public function testPublicNullValueIsReturned(): void
    {
        $item = new class {
            public ?string $name = null;
        };

        $this->assertNull(Obj::pluck($item, 'name'));
    }

    public function testMagicGetterIsPreserved(): void
    {
        $item = new class {
            /** @return mixed */
            public function __get(string $name)
            {
                return $name === 'virtual' ? 'value' : null;
            }
        };

        $this->assertSame('value', Obj::pluck($item, 'virtual'));
    }

    public function testMissingPropertyThrowsItemNotFound(): void
    {
        $this->expectException(ItemNotFound::class);

        Obj::pluck((object) [], 'missing');
    }

    public function testPrivatePropertyThrowsItemNotFound(): void
    {
        $item = new class {
            private string $secret = 'hidden';

            public function reveal(): string
            {
                return $this->secret;
            }
        };

        $this->expectException(ItemNotFound::class);

        Obj::pluck($item, 'secret');
    }

    public function testMagicGetterCanExposeAnOtherwisePrivateProperty(): void
    {
        $item = new class {
            private string $secret = 'hidden';

            /** @return mixed */
            public function __get(string $name)
            {
                return $name === 'secret' ? $this->secret : null;
            }
        };

        $this->assertSame('hidden', Obj::pluck($item, 'secret'));
    }

    public function testScalarIntermediateThrowsItemNotFound(): void
    {
        $item = (object) ['nested' => 42];

        $this->expectException(ItemNotFound::class);

        Obj::pluck($item, 'nested.value');
    }

    public function testGetterExceptionIsNotWrapped(): void
    {
        $item = new class {
            public function getName(): string
            {
                throw new RuntimeException('getter failed');
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('getter failed');

        Obj::pluck($item, 'name');
    }

    public function testMagicGetterExceptionIsNotWrapped(): void
    {
        $item = new class {
            /** @return mixed */
            public function __get(string $name)
            {
                throw new RuntimeException("magic getter failed for $name");
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('magic getter failed');

        Obj::pluck($item, 'name');
    }
}
