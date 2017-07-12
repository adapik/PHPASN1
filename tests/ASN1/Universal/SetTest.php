<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\Test\ASN1\Universal;

use FG\Test\ASN1TestCase;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\Set;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\PrintableString;
use FG\ASN1\Universal\Boolean;

class SetTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = Set::create();
        $this->assertEquals(Identifier::SET, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $child1 = Integer::create(123);
        $child2 = PrintableString::createFromString('Hello Wold');
        $child3 = Boolean::create(true);
        $object = Set::create([$child1, $child2, $child3]);

        $this->assertEquals([$child1, $child2, $child3], $object->getChildren());
        $this->assertEquals(
            $child1->getBinary() . $child2->getBinary() . $child3->getBinary(),
            $object->getBinaryContent()
        );
    }

    public function testGetObjectLength()
    {
        $child1 = Boolean::create(true);
        $object = Set::create([$child1]);
        $this->assertEquals(5, $object->getObjectLength());

        $child1 = Integer::create(123);
        $child2 = Boolean::create(true);
        $object = Set::create([$child1, $child2]);
        $this->assertEquals(8, $object->getObjectLength());

        $child1 = Integer::create(123);
        $child2 = PrintableString::createFromString('Hello Wold');
        $child3 = Boolean::create(true);
        $object = Set::create([$child1, $child2, $child3]);
        $this->assertEquals(20, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $child1 = Boolean::create(true);
        $object = Set::create([$child1]);

        $expectedType    = chr(Identifier::IS_CONSTRUCTED | Identifier::SET);
        $expectedLength  = chr(0x03);
        $expectedContent = $child1->getBinary();
        $this->assertEquals($expectedType . $expectedLength . $expectedContent, $object->getBinary());

        $child1           = Integer::create(123);
        $child2           = Boolean::create(true);
        $object           = Set::create([$child1, $child2]);
        $expectedLength   = chr(0x06);
        $expectedContent  = $child1->getBinary();
        $expectedContent .= $child2->getBinary();
        $this->assertEquals($expectedType . $expectedLength . $expectedContent, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = Set::create([
            Boolean::create(true),
            Integer::create(1234567)
        ]);
        $binaryData    = $originalObject->getBinary();
        $parsedObject  = Set::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /*
     * @depends testFromBinary
     */

    public function testFromBinaryWithOffset()
    {
        $originalObject1 = Set::create([
            Boolean::create(true),
            Integer::create(123)
        ]);
        $originalObject2 = Set::create([
            Integer::create(64),
            Boolean::create(false)
        ]);

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset       = 0;
        $parsedObject = Set::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = Set::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }
}
