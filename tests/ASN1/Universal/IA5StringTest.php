<?php

namespace FG\Test\ASN1\Universal;

use FG\Test\ASN1TestCase;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\IA5String;

class IA5StringTest extends ASN1TestCase
{

    public function testGetIdentifier()
    {
        $object = IA5String::createFromString('Hello World');
        $this->assertEquals(Identifier::IA5_STRING, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $object = IA5String::createFromString('Hello World');
        $this->assertEquals('Hello World', (string) $object);

        $object = IA5String::createFromString('');
        $this->assertEquals('', (string) $object);

        $object = IA5String::createFromString('             ');
        $this->assertEquals('             ', (string) $object);
    }

    public function testGetObjectLength()
    {
        $string = 'Hello World';
        $object = IA5String::createFromString($string);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Hello World';
        $expectedType = chr(Identifier::IA5_STRING);
        $expectedLength = chr(strlen($string));

        $object = IA5String::createFromString($string);
        $this->assertEquals($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = IA5String::createFromString('Hello World');
        $binaryData = $originalObject->getBinary();
        $parsedObject = IA5String::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = IA5String::createFromString('Hello ');
        $originalObject2 = IA5String::createFromString(' World');

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = IA5String::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(8, $offset);
        $parsedObject = IA5String::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(16, $offset);
    }
}
