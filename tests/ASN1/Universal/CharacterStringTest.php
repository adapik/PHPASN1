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
use FG\ASN1\Universal\CharacterString;

class CharacterStringTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = CharacterString::createFromString('Hello World');
        $this->assertEquals(Identifier::CHARACTER_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $object = CharacterString::createFromString('Hello World');
        $this->assertEquals('Hello World', $object->getContent());
    }

    public function testGetObjectLength()
    {
        $string = 'Hello World';
        $object = CharacterString::createFromString($string, ['lengthForm' => ContentLength::SHORT_FORM]);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Hello World';
        $expectedType = chr(Identifier::CHARACTER_STRING);
        $expectedLength = chr(strlen($string));

        $object = CharacterString::createFromString($string, ['lengthForm' => ContentLength::SHORT_FORM]);
        $this->assertEquals($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = CharacterString::createFromString('Hello World');
        $binaryData = $originalObject->getBinary();
        $parsedObject = CharacterString::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = CharacterString::createFromString('Hello ', ['lengthForm' => ContentLength::SHORT_FORM]);
        $originalObject2 = CharacterString::createFromString(' World', ['lengthForm' => ContentLength::SHORT_FORM]);

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = CharacterString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = CharacterString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }
}
