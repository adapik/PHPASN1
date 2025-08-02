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
use FG\ASN1\Universal\Boolean;

class BooleanTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = Boolean::create(true);
        $this->assertEquals(Identifier::BOOLEAN, $object->getIdentifier()->getTagNumber());
    }

    public function testGetIdentifierCode()
    {
        $object = Boolean::create(true);
        $this->assertEquals('Boolean', $object->getIdentifier()->getCode());
    }

    public function testGetStringValue()
    {
        $object = Boolean::create(true);
        $this->assertEquals('true', (string) $object);

        $object = Boolean::create(false);
        $this->assertEquals('false', (string) $object);
    }

    public function testGetObjectLength()
    {
        $object = Boolean::create(true);
        $this->assertEquals(3, $object->getObjectLength());

        $object = Boolean::create(false);
        $this->assertEquals(3, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $expectedType = chr(Identifier::BOOLEAN);
        $expectedLength = chr(0x01);

        $object = Boolean::create(true);
        $expectedContent = chr(0xFF);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $object = Boolean::create(false);
        $expectedContent = chr(0x00);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = Boolean::create(true);
        $binaryData = $originalObject->getBinary();
        $parsedObject = Boolean::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);

        $originalObject = Boolean::create(false);
        $binaryData = $originalObject->getBinary();
        $parsedObject = Boolean::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = Boolean::create(true);
        $originalObject2 = Boolean::create(false);

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = Boolean::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(3, $offset);
        $parsedObject = Boolean::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(6, $offset);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithInvalidLength01()
    {
        $this->expectException(\FG\ASN1\Exception\ParserException::class);
        $this->expectExceptionMessage('ASN.1 Parser Exception at offset 4: An ASN.1 Boolean should not have a length other than one. Extracted length was 2');
        
        $binaryData  = chr(Identifier::BOOLEAN);
        $binaryData .= chr(0x02);
        $binaryData .= chr(0xFF);
        Boolean::fromBinary($binaryData);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithInvalidLength02()
    {
        $this->expectException(\FG\ASN1\Exception\ParserException::class);
        $this->expectExceptionMessage('ASN.1 Parser Exception at offset 2: An ASN.1 Boolean should not have a length other than one. Extracted length was 0');
        
        $binaryData  = chr(Identifier::BOOLEAN);
        $binaryData .= chr(0x00);
        $binaryData .= chr(0xFF);
        Boolean::fromBinary($binaryData);
    }
}
