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
use FG\ASN1\Universal\T61String;

class T61StringTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = T61String::createFromString('Hello World');
        $this->assertEquals(Identifier::T61_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $object = T61String::createFromString('Hello World');
        $this->assertEquals('Hello World', (string) $object);
    }

    public function testGetObjectLength()
    {
        $string = 'Hello World';
        $object = T61String::createFromString($string);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Hello World';
        $expectedType = chr(Identifier::T61_STRING);
        $expectedLength = chr(strlen($string));

        $object = $object = T61String::createFromString($string);
        $this->assertEquals($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = $object = T61String::createFromString('Hello World');
        $binaryData = $originalObject->getBinary();
        $parsedObject = T61String::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = T61String::createFromString('Hello ');
        $originalObject2 = T61String::createFromString(' World');

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = T61String::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = T61String::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }
}
