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
use FG\ASN1\Universal\ObjectDescriptor;

class ObjectDescriptorTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = ObjectDescriptor::createFromString('NumericString character abstract syntax');
        $this->assertEquals(Identifier::OBJECT_DESCRIPTOR, $object->getIdentifier()->getTagNumber());
    }

    public function testContent()
    {
        $object = ObjectDescriptor::createFromString('PrintableString character abstract syntax');
        $this->assertEquals('PrintableString character abstract syntax', (string) $object);
    }

    public function testGetObjectLength()
    {
        $string = 'Basic Encoding of a single ASN.1 type';
        $object = ObjectDescriptor::createFromString($string);
        $expectedSize = 2 + strlen($string);
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $string = 'Basic Encoding of a single ASN.1 type';
        $expectedType = chr(Identifier::OBJECT_DESCRIPTOR);
        $expectedLength = chr(strlen($string));

        $object = ObjectDescriptor::createFromString($string);
        $this->assertEquals($expectedType.$expectedLength.$string, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = ObjectDescriptor::createFromString('PrintableString character abstract syntax');
        $binaryData = $originalObject->getBinary();
        $parsedObject = ObjectDescriptor::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = ObjectDescriptor::createFromString('NumericString character abstract syntax');
        $originalObject2 = ObjectDescriptor::createFromString('Basic Encoding of a single ASN.1 type');

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = ObjectDescriptor::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(41, $offset);
        $parsedObject = ObjectDescriptor::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(80, $offset);
    }
}
