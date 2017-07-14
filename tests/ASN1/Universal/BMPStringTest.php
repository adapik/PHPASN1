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

use FG\ASN1\ASN1Object;
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
        $expectedSize = 2 + strlen($string) * 2;
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Hello World';
        $expectedType = chr(Identifier::BMP_STRING);
        $expectedLength = chr(strlen($string) * 2);

        $object = BMPString::createFromString($string);
        $string = "\x00H\x00e\x00l\x00l\x00o\x00 \x00W\x00o\x00r\x00l\x00d";
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
        $originalObject1 = BMPString::createFromString('Hello ');
        $originalObject2 = BMPString::createFromString(' World');

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = BMPString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(14, $offset);
        $parsedObject = BMPString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(28, $offset);
    }

    public function testGetValue()
    {
        $string = hex2bin('1E12003700370020041C043E0441043A04320430');
        $bmpString = ASN1Object::fromBinary($string);
        $this->assertInstanceOf(BMPString::class, $bmpString);
        $this->assertSame('77 Москва', (string) $bmpString);
    }
}
