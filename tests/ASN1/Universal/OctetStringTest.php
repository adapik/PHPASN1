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
use FG\ASN1\Universal\OctetString;

class OctetStringTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = OctetString::createFromString(hex2bin('301406082B0601050507030106082B06010505070302'));
        $this->assertEquals(Identifier::OCTETSTRING, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $object = OctetString::createFromString('A01200C3');
        $this->assertEquals('A01200C3', (string) $object);
    }

    public function testGetObjectLength()
    {
        $object = OctetString::createFromString(hex2bin('00'));
        $this->assertEquals(3, $object->getObjectLength());

        $object = OctetString::createFromString(hex2bin('FF'));
        $this->assertEquals(3, $object->getObjectLength());

        $object = OctetString::createFromString(hex2bin('A000'));
        $this->assertEquals(4, $object->getObjectLength());

        $object = OctetString::createFromString(hex2bin('3F2001'));
        $this->assertEquals(5, $object->getObjectLength());
    }

    public function testGetObjectLengthWithVeryLongOctetString()
    {
        $hexString = str_repeat('FF', 1024);
        $object = OctetString::createFromString(hex2bin($hexString));
        $this->assertEquals(1 + 3 + 1024, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $expectedType = chr(Identifier::OCTETSTRING);
        $expectedLength = chr(0x01);

        $object = OctetString::createFromString(hex2bin('FF'));
        $expectedContent = chr(0xFF);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $object = OctetString::createFromString(hex2bin('FFA034'));
        $expectedLength = chr(0x03);
        $expectedContent  = chr(0xFF);
        $expectedContent .= chr(0xA0);
        $expectedContent .= chr(0x34);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());
    }

    public function testGetBinaryForLargeOctetStrings()
    {
        $nrOfBytes = 1024;
        $hexString = str_repeat('FF', $nrOfBytes);
        $object = OctetString::createFromString(hex2bin($hexString));

        $expectedType = chr(Identifier::OCTETSTRING);
        $expectedLength = chr(0x80 | 0x02);  // long length form: 2 length octets
        $expectedLength .= chr(1024 >> 8);   // first 8 bit of 1025
        $expectedLength .= chr(1024 & 0xFF); // last 8 bit of 1025
        $expectedContent = '';
        for ($i = 0; $i < $nrOfBytes; $i++) {
            $expectedContent .= chr(0xFF);   // content
        }

        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = OctetString::createFromString(hex2bin('12'));
        $binaryData = $originalObject->getBinary();
        $parsedObject = OctetString::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);

        $originalObject = OctetString::createFromString(hex2bin('010203A0'));
        $binaryData = $originalObject->getBinary();
        $parsedObject = OctetString::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = OctetString::createFromString(hex2bin('A0'));
        $originalObject2 = OctetString::createFromString(hex2bin('314510'));

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = OctetString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(3, $offset);
        $parsedObject = OctetString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(8, $offset);
    }
}
