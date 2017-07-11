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
use FG\ASN1\Universal\NumericString;

class NumericStringTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = NumericString::createFromString('1234');
        $this->assertEquals(Identifier::NUMERIC_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $object = NumericString::createFromString('123 45 67890');
        $this->assertEquals('123 45 67890', (string) $object);

        $object = NumericString::createFromString('             ');
        $this->assertEquals('             ', (string) $object);
    }

    public function testGetObjectLength()
    {
        $string = '123  4 55677 0987';
        $object = NumericString::createFromString($string);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = '123  4 55677 0987';
        $expectedType = chr(Identifier::NUMERIC_STRING);
        $expectedLength = chr(strlen($string));

        $object = NumericString::createFromString($string);
        $this->assertEquals($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = NumericString::createFromString('123 45  5322');
        $binaryData = $originalObject->getBinary();
        $parsedObject = NumericString::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = NumericString::createFromString('1324 0');
        $originalObject2 = NumericString::createFromString('1 2 3 ');

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = NumericString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = NumericString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }

    public function testCreateStringWithValidCharacters()
    {
        $object = NumericString::createFromString('1234');
        $this->assertEquals(pack('H*', '120431323334'), $object->getBinary());
        $object = NumericString::createFromString('321 98 76');
        $this->assertEquals(pack('H*', '1209333231203938203736'), $object->getBinary());
    }

    public function testCreateStringWithInvalidCharacters()
    {
        $invalidString = 'Hello World';
        $this->expectExceptionMessage("Could not create a ASN.1 Numeric String from the character sequence '{$invalidString}'.");
        $object = NumericString::createFromString($invalidString);
        $object->getBinary();

        $invalidString = '123,456';
        $this->expectExceptionMessage("Could not create a ASN.1 Numeric String from the character sequence '{$invalidString}'.");
        $object = NumericString::createFromString($invalidString);
        $object->getBinary();

        $invalidString = '+123456';
        $this->expectExceptionMessage("Could not create a ASN.1 Numeric String from the character sequence '{$invalidString}'.");
        $object = NumericString::createFromString($invalidString);
        $object->getBinary();

        $invalidString = '-123456';
        $this->expectExceptionMessage("Could not create a ASN.1 Numeric String from the character sequence '{$invalidString}'.");
        $object = NumericString::createFromString($invalidString);
        $object->getBinary();
    }
}
