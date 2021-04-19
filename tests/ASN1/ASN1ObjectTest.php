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

use FG\ASN1\Exception\Exception;
use FG\ASN1\ExplicitlyTaggedObject;
use FG\ASN1\Universal\GeneralizedTime;
use FG\ASN1\Universal\Set;
use FG\Test\ASN1TestCase;
use FG\ASN1\ASN1Object;
use FG\ASN1\UnknownConstructedObject;
use FG\ASN1\UnknownObject;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\BitString;
use FG\ASN1\Universal\Boolean;
use FG\ASN1\Universal\Enumerated;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\NullObject;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\OctetString;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\IA5String;
use FG\ASN1\Universal\PrintableString;

class ASN1ObjectTest extends ASN1TestCase
{
    /**
     * @var Object
     */
    private $object;

    public function setUp(): void
    {
        $this->object = $this
            ->getMockBuilder(ASN1Object::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * For the real parsing tests look in the test cases of each single ASn object.
     */
    public function testFromBinary()
    {
        /* @var BitString $parsedObject */
        $binaryData  = chr(Identifier::BITSTRING);
        $binaryData .= chr(0x03);
        $binaryData .= chr(0x00);
        $binaryData .= chr(0xFF);
        $binaryData .= chr(0xA0);

        $expectedObject = BitString::createFromHexString('FFA0');
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof BitString);
        $this->assertEquals($expectedObject, $parsedObject);
        $this->assertEquals($expectedObject->getNumberOfUnusedBits(), $parsedObject->getNumberOfUnusedBits());

        /* @var OctetString $parsedObject */
        $binaryData  = chr(Identifier::OCTETSTRING);
        $binaryData .= chr(0x02);
        $binaryData .= chr(0xFF);
        $binaryData .= chr(0xA0);

        $expectedObject = OctetString::createFromString(hex2bin('FFA0'));
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof OctetString);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var \FG\ASN1\Universal\Boolean $parsedObject */
        $binaryData  = chr(Identifier::BOOLEAN);
        $binaryData .= chr(0x01);
        $binaryData .= chr(0xFF);

        $expectedObject = Boolean::create(true);
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof Boolean);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var Enumerated $parsedObject */
        $binaryData  = chr(Identifier::ENUMERATED);
        $binaryData .= chr(0x01);
        $binaryData .= chr(0x03);

        $expectedObject = Enumerated::create(3);
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof Enumerated);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var IA5String $parsedObject */
        $string      = 'Hello Foo World!!!11EinsEins!1';
        $binaryData  = chr(Identifier::IA5_STRING);
        $binaryData .= chr(strlen($string));
        $binaryData .= $string;

        $expectedObject = IA5String::createFromString($string);
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof IA5String);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var \FG\ASN1\Universal\Integer $parsedObject */
        $binaryData  = chr(Identifier::INTEGER);
        $binaryData .= chr(0x01);
        $binaryData .= chr(123);

        $expectedObject = Integer::create(123);
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof Integer);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var \FG\ASN1\Universal\NullObject $parsedObject */
        $binaryData  = chr(Identifier::NULL);
        $binaryData .= chr(0x00);

        $expectedObject = NullObject::create();
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof NullObject);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var ObjectIdentifier $parsedObject */
        $binaryData  = chr(Identifier::OBJECT_IDENTIFIER);
        $binaryData .= chr(0x02);
        $binaryData .= chr(1 * 40 + 2);
        $binaryData .= chr(3);

        $expectedObject = ObjectIdentifier::create('1.2.3');
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof ObjectIdentifier);
        $this->assertEquals($expectedObject->getContent(), $parsedObject->getContent());

        /* @var PrintableString $parsedObject */
        $string      = 'This is a test string. ?()+,/';
        $binaryData  = chr(Identifier::PRINTABLE_STRING);
        $binaryData .= chr(strlen($string));
        $binaryData .= $string;

        $expectedObject = PrintableString::createFromString($string);
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof PrintableString);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var GeneralizedTime $parsedObject */
        $binaryData  = chr(Identifier::GENERALIZED_TIME);
        $binaryData .= chr(15);
        $binaryData .= '20120923202316Z';

        $expectedObject = GeneralizedTime::createFormDateTime(new \DateTime('2012-09-23 20:23:16'));
        $parsedObject   = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof GeneralizedTime);
        $this->assertEquals($expectedObject->getContent(), $parsedObject->getContent());

        /* @var Sequence $parsedObject */
        $binaryData  = chr(Identifier::IS_CONSTRUCTED | Identifier::SEQUENCE);
        $binaryData .= chr(0x06);
        $binaryData .= chr(Identifier::BOOLEAN);
        $binaryData .= chr(0x01);
        $binaryData .= chr(0x00);
        $binaryData .= chr(Identifier::INTEGER);
        $binaryData .= chr(0x01);
        $binaryData .= chr(0x03);

        $expectedChild1 = Boolean::create(false);
        $expectedChild2 = Integer::create(0x03);

        $parsedObject = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof Sequence);
        $this->assertCount(2, $parsedObject->getChildren());

        /* @var ExplicitlyTaggedObject $parsedObject */
        $taggedObject = ExplicitlyTaggedObject::create(0x01, PrintableString::createFromString('Hello tagged world'));
        $binaryData   = $taggedObject->getBinary();
        $parsedObject = ASN1Object::fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof ExplicitlyTaggedObject);

        // An unknown constructed object containing 2 integer children,
        // first 3 bytes are the identifier.
        $binaryData   = "\x3F\x81\x7F\x06".chr(Identifier::INTEGER)."\x01\x42".chr(Identifier::INTEGER)."\x01\x69";
        $offsetIndex  = 0;
        $parsedObject = ASN1Object::fromBinary($binaryData, $offsetIndex);
        $this->assertTrue($parsedObject instanceof UnknownConstructedObject);
        $this->assertEquals(substr($binaryData, 0, 3), $parsedObject->getIdentifier()->getBinary());
        $this->assertCount(2, $parsedObject->getChildren());
        $this->assertEquals(strlen($binaryData), $offsetIndex);
        $this->assertEquals(10, $parsedObject->getObjectLength());

        // First 3 bytes are the identifier
        $binaryData   = "\x1F\x81\x7F\x01\xFF";
        $offsetIndex  = 0;
        $parsedObject = ASN1Object::fromBinary($binaryData, $offsetIndex);
        $this->assertTrue($parsedObject instanceof UnknownObject);
        $this->assertEquals(substr($binaryData, 0, 3), $parsedObject->getIdentifier()->getBinary());
        $this->assertEquals(strlen($binaryData), $offsetIndex);
        $this->assertEquals(5, $parsedObject->getObjectLength());
    }

    /**
     * @expectedException \FG\ASN1\Exception\ParserException
     * @expectedExceptionMessage ASN.1 Parser Exception at offset 10: Can not parse binary from data: Offset index larger than input size
     * @depends testFromBinary
     */
    public function testFromBinaryThrowsException()
    {
        $binaryData = 0x0;
        $offset     = 10;
        ASN1Object::fromBinary($binaryData, $offset);
    }

    /**
     * @expectedException \FG\ASN1\Exception\ParserException
     * @expectedExceptionMessage ASN.1 Parser Exception at offset 0: Can not parse binary from data: Offset index larger than input size
     * @depends testFromBinary
     */
    public function testFromBinaryWithEmptyStringThrowsException()
    {
        $data = '';
        ASN1Object::fromBinary($data);
    }

    /**
     * @expectedException \FG\ASN1\Exception\ParserException
     * @expectedExceptionMessage ASN.1 Parser Exception at offset 2: Can not parse binary from data: Offset index larger than input size
     * @depends testFromBinary
     */
    public function testFromBinaryWithSpacyStringThrowsException()
    {
        $data = '  ';
        ASN1Object::fromBinary($data);
    }

    /**
     * @expectedException \FG\ASN1\Exception\ParserException
     * @expectedExceptionMessage ASN.1 Parser Exception at offset 1: Can not parse content length from data: Offset index larger than input size
     * @depends testFromBinary
     */
    public function testFromBinaryWithNumberStringThrowsException()
    {
        $data = '1';
        ASN1Object::fromBinary($data);
    }

    /**
     * @expectedException \FG\ASN1\Exception\ParserException
     * @expectedExceptionMessage ASN.1 Parser Exception at offset 25: Can not parse content length from data: Offset index larger than input size
     * @depends testFromBinary
     */
    public function testFromBinaryWithGarbageStringThrowsException()
    {
        $data = 'certainly no asn.1 object';
        ASN1Object::fromBinary($data);
    }

    /**
     * @expectedException \FG\ASN1\Exception\ParserException
     * @expectedExceptionMessage ASN.1 Parser Exception at offset 1: Can not parse identifier (long form) from data: Offset index larger than input size
     * @depends testFromBinary
     */
    public function testFromBinaryUnknownObjectMissingLength()
    {
        $data = hex2bin('1f');
        ASN1Object::fromBinary($data);
    }

    /**
     * @expectedException \FG\ASN1\Exception\ParserException
     * @expectedExceptionMessage ASN.1 Parser Exception at offset 4: Can not parse content length (long form) from data: Offset index larger than input size
     * @depends testFromBinary
     */
    public function testFromBinaryInalidLongFormContentLength()
    {
        $binaryData  = chr(Identifier::INTEGER);
        $binaryData .= chr(0x8f); //denotes a long-form content length with 15 length-octets
        $binaryData .= chr(0x1);  //only give one content-length-octet
        $binaryData .= chr(0x1);  //this is needed to reach the code to be tested

        ASN1Object::fromBinary($binaryData);
    }

    public function testFindByOid()
    {
        $oidString = '1.5.45.1';
        $object = ObjectIdentifier::create('1.5.45.1');
        $oidObjects = $object->findByOid($oidString);
        $this->assertSame([$object], $oidObjects);

        $object1 = ObjectIdentifier::create('1.5.45.1');
        $object2 = ObjectIdentifier::create('1.5.45.2');
        $object3 = ObjectIdentifier::create('1.5.45.3');
        $object4 = ObjectIdentifier::create('1.5.45.4');

        $set = Set::create([$object1, $object2, $object1, $object4, $object3]);
        $oidObjects = $set->findByOid('1.5.45.1');
        $this->assertSame([$object1, $object1], $oidObjects);
        $oidObjects = $set->findByOid('1.5.45.2');
        $this->assertSame([$object2], $oidObjects);

        $oidObject = ObjectIdentifier::create('1.2.250.1.16.9');
        $sequence = Sequence::create([
            Set::create([
                Integer::create(42),
                Sequence::create([
                    $oidObject,
                    BitString::createFromHexString('A0120043'),
                ])
            ])
        ]);
        $oidObjects = $sequence->findByOid('1.2.250.1.16.9');
        $this->assertSame([$oidObject], $oidObjects);
    }

    public function testFindChildrenByType()
    {
        $integerObject   = Integer::create(42);
        $oidObject1      = ObjectIdentifier::create('1.2.250.1.16.9');
        $oidObject2      = ObjectIdentifier::create('1.3.7');
        $bitStringObject = BitString::createFromHexString('A0120043');
        $booleanObject   = Boolean::create(true);
        $sequence        = Sequence::create([
            $integerObject,
            $oidObject1,
            $bitStringObject,
            $oidObject2,
            $booleanObject
        ]);

        $children = $sequence->findChildrenByType(\FG\ASN1\Universal\Boolean::class);
        $this->assertSame([$booleanObject], $children);

        $children = $sequence->findChildrenByType(\FG\ASN1\Universal\ObjectIdentifier::class);
        $this->assertSame([$oidObject1, $oidObject2], $children);

        $children = $sequence->findChildrenByType(\FG\ASN1\Universal\PrintableString::class);
        $this->assertSame([], $children);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown class type object');
        $sequence->findChildrenByType('Unknown class type');
    }

    public function testGetSiblings()
    {
        $integerObject   = Integer::create(42);
        $oidObject       = ObjectIdentifier::create('1.2.250.1.16.9');
        $bitStringObject = BitString::createFromHexString('A0120043');
        $booleanObject   = Boolean::create(true);
        Sequence::create([
            $integerObject,
            $oidObject,
            $bitStringObject,
            $booleanObject
        ]);

        $this->assertSame([$integerObject, $oidObject, $booleanObject], $bitStringObject->getSiblings());

        $integerObject   = Integer::create(42);
        $this->assertSame([], $integerObject->getSiblings());
    }
}
