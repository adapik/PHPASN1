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
use FG\ASN1\Universal\GraphicString;

class GraphicStringTest extends ASN1TestCase
{
    public function testGetType()
    {
        $object = GraphicString::createFromString('Hello World');
        $this->assertEquals(Identifier::GRAPHIC_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testGetIdentifier()
    {
        $object = GraphicString::createFromString('Hello World');
        $this->assertEquals(Identifier::GRAPHIC_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $object = GraphicString::createFromString('Hello World');
        $this->assertEquals('Hello World', (string) $object);

        $object = GraphicString::createFromString('');
        $this->assertEquals('', (string) $object);

        $object = GraphicString::createFromString('             ');
        $this->assertEquals('             ', (string) $object);
    }

    public function testGetObjectLength()
    {
        $string = 'Hello World';
        $object = GraphicString::createFromString($string, ['lengthForm' => ContentLength::SHORT_FORM]);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Hello World';
        $expectedType = chr(Identifier::GRAPHIC_STRING);
        $expectedLength = chr(strlen($string));

        $object = GraphicString::createFromString($string, ['lengthForm' => ContentLength::SHORT_FORM]);
        $this->assertEquals($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = GraphicString::createFromString('Hello world', ['lengthForm' => ContentLength::SHORT_FORM]);
        $binaryData = $originalObject->getBinary();
        $parsedObject = GraphicString::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = GraphicString::createFromString('Hello ', ['lengthForm' => ContentLength::SHORT_FORM]);
        $originalObject2 = GraphicString::createFromString(' World', ['lengthForm' => ContentLength::SHORT_FORM]);

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = GraphicString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = GraphicString::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }
}
