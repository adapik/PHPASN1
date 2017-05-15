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
use FG\ASN1\Universal\NullObject;

class NullObjectTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = NullObject::create();
        $this->assertEquals(Identifier::NULL, $object->getIdentifier()->getTagNumber());
    }

    public function testGetStringValue()
    {
        $object = NullObject::create();
        $this->assertEquals('null', $object->getStringValue());
    }

    public function testGetObjectLength()
    {
        $object = NullObject::create();
        $this->assertEquals(2, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $object = NullObject::create();
        $expectedType = chr(Identifier::NULL);
        $expectedLength = chr(0x00);
        $this->assertEquals($expectedType.$expectedLength, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = NullObject::create();
        $binaryData = $originalObject->getBinary();
        $parsedObject = NullObject::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = NullObject::create();
        $originalObject2 = NullObject::create();

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = NullObject::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(2, $offset);
        $parsedObject = NullObject::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(4, $offset);
    }

    /**
     * @expectedException \FG\ASN1\Exception\ParserException
     * @expectedExceptionMessage ASN.1 Parser Exception at offset 3: An ASN.1 Null should not have a length other than zero. Extracted length was 1
     * @depends testFromBinary
     */
    public function testFromBinaryWithInvalidLength01()
    {
        $binaryData  = chr(Identifier::NULL);
        $binaryData .= chr(0x01);
        NullObject::fromBinary($binaryData);
    }
}
