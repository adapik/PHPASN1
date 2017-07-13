<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\Test\ASN1;

use FG\ASN1\Identifier;
use FG\ASN1\IdentifierManager;
use FG\ASN1\Universal\Boolean;
use FG\ASN1\Universal\Integer;
use FG\Test\ASN1TestCase;
use FG\ASN1\ExplicitlyTaggedObject;
use FG\ASN1\Universal\PrintableString;

class ExplicitlyTaggedObjectTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $asn                = ExplicitlyTaggedObject::create(0x1E, PrintableString::createFromString('test'));
        $expectedIdentifier = IdentifierManager::create(Identifier::CLASS_CONTEXT_SPECIFIC, true, 0x1E);
        $this->assertEquals($expectedIdentifier, $asn->getIdentifier()->getBinary());
    }

    public function testGetTag()
    {
        $object = ExplicitlyTaggedObject::create(0, PrintableString::createFromString('test'));
        $this->assertEquals(0, $object->getIdentifier()->getTagNumber());

        $object = ExplicitlyTaggedObject::create(1, PrintableString::createFromString('test'));
        $this->assertEquals(1, $object->getIdentifier()->getTagNumber());
    }

    public function testGetLength()
    {
        $string = PrintableString::createFromString('test');
        $object = ExplicitlyTaggedObject::create(0, $string);
        $this->assertEquals($string->getObjectLength() + 2, $object->getObjectLength());
    }

    public function testGetContent()
    {
        $string = PrintableString::createFromString('test');
        $object = ExplicitlyTaggedObject::create(0, $string);
        $this->assertEquals([$string], $object->getChildren());
    }

    public function testGetBinary()
    {
        $tag = 0x01;
        $string = PrintableString::createFromString('test');
        $expectedType = IdentifierManager::create(Identifier::CLASS_CONTEXT_SPECIFIC, true, $tag);
        $expectedLength = chr($string->getObjectLength());

        $encodedStringObject = $string->getBinary();
        $object = ExplicitlyTaggedObject::create($tag, $string);
        $this->assertBinaryEquals($expectedType.$expectedLength.$encodedStringObject, $object->getBinary());
    }

    /**
     * @dataProvider getTags
     * @depends testGetBinary
     */
    public function testFromBinary($originalTag)
    {
        $originalStringObject = PrintableString::createFromString('test');
        $originalObject = ExplicitlyTaggedObject::create($originalTag, $originalStringObject);
        $binaryData = $originalObject->getBinary();

        $parsedObject = ExplicitlyTaggedObject::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    public function getTags()
    {
        return [
            [0x02],
            [0x00004002],
        ];
    }

    public function testFromBinaryWithZeroContent()
    {
        $data   = hex2bin('A000');
        $object = ExplicitlyTaggedObject::fromBinary($data);
        $this->assertEquals(2, $object->getObjectLength());
        $this->assertEquals([], $object->getChildren());
        $this->assertEquals('[0]', (string) $object->__toString());
        $this->assertEquals($data, $object->getBinary());
    }

    public function testFromBinaryWithMultipleObjects()
    {
        $object1 = Boolean::create(true);
        $object2 = Integer::create(42);

        $identifier = 0xA0;
        $length = $object1->getObjectLength() + $object2->getObjectLength();
        $data = chr($identifier).chr($length).$object1->getBinary().$object2->getBinary();

        $object = ExplicitlyTaggedObject::fromBinary($data);
        $this->assertEquals(2+$length, $object->getObjectLength());
        $this->assertEquals($data, $object->getBinary());
    }
}
