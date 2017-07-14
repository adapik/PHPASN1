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
use FG\ASN1\Universal\UTF8String;

class UTF8StringTest extends ASN1TestCase
{
    public function testGetType()
    {
        $object = UTF8String::createFromString('Hello World');
        $this->assertEquals(Identifier::UTF8_STRING, $object->getType());
    }

    public function testGetIdentifier()
    {
        $object = UTF8String::createFromString('Hello World');
        $this->assertEquals(Identifier::UTF8_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $object = UTF8String::createFromString('Москва');
        $this->assertEquals('Москва', (string) $object);
    }

    public function testGetObjectLength()
    {
        $string = 'Hello World';
        $object = UTF8String::createFromString($string);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Hello World';
        $expectedType = chr(Identifier::UTF8_STRING);
        $expectedLength = chr(strlen($string));

        $object = UTF8String::createFromString($string);
        $this->assertEquals($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = UTF8String::createFromString('Hello World');
        $binaryData = $originalObject->getBinary();
        $parsedObject = UTF8String::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = UTF8String::createFromString('Hello ');
        $originalObject2 = UTF8String::createFromString(' World');

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = UTF8String::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = UTF8String::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }
}
