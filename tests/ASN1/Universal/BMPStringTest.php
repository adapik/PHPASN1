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
use FG\ASN1\Universal\BMPString;

class BMPStringTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = BMPString::createFromString('Hello World');
        $this->assertEquals(Identifier::BMP_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testGetStringValue()
    {
        $object = BMPString::createFromString('Hello World');
        $this->assertEquals('Hello World', (string) $object);
    }

    public function testGetObjectLength()
    {
        $string = 'Hello World';
        $object = BMPString::createFromString($string);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Hello World';
        $expectedType = chr(Identifier::BMP_STRING);
        $expectedLength = chr(strlen($string));

        $object = BMPString::createFromString($string, ['lengthForm' => ContentLength::SHORT_FORM]);
        $this->assertEquals($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = BMPString::createFromString('Hello World', ['lengthForm' => ContentLength::SHORT_FORM]);
        $binaryData = $originalObject->getBinary();
        $parsedObject = BMPString::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = BMPString::createFromString('Hello ', ['lengthForm' => ContentLength::SHORT_FORM]);
        $originalObject2 = BMPString::createFromString(' World', ['lengthForm' => ContentLength::SHORT_FORM]);

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = BMPString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = BMPString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }
}
