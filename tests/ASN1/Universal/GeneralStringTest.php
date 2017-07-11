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

use FG\ASN1\ContentLength;
use FG\Test\ASN1TestCase;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\GeneralString;

class GeneralStringTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = GeneralString::createFromString('Hello World');
        $this->assertEquals(Identifier::GENERAL_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testGetStringValue()
    {
        $object = GeneralString::createFromString('Hello World');
        $this->assertEquals('Hello World', $object->getStringValue());

        $object = GeneralString::createFromString('');
        $this->assertEquals('', $object->getStringValue());

        $object = GeneralString::createFromString('             ');
        $this->assertEquals('             ', $object->getStringValue());
    }

    public function testGetObjectLength()
    {
        $string = 'Hello World';
        $object = GeneralString::createFromString($string);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Hello World';
        $expectedType = chr(Identifier::GENERAL_STRING);
        $expectedLength = chr(strlen($string));

        $object = GeneralString::createFromString($string, ['lengthForm' => ContentLength::LONG_FORM]);
        $this->assertSame($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = GeneralString::createFromString('Hello World');
        $binaryData = $originalObject->getBinary();
        $parsedObject = GeneralString::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = GeneralString::createFromString('Hello ', ['lengthForm' => ContentLength::SHORT_FORM]);
        $originalObject2 = GeneralString::createFromString(' World', ['lengthForm' => ContentLength::SHORT_FORM]);

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = GeneralString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = GeneralString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }
}
