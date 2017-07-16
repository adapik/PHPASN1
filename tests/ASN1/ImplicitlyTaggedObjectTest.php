<?php

namespace FG\Test\ASN1;

use FG\ASN1\Identifier;
use FG\ASN1\ImplicitlyTaggedObject;
use FG\ASN1\Universal\Integer;
use FG\Test\ASN1TestCase;

class ImplicitlyTaggedObjectTest extends ASN1TestCase
{
    public function testGetDecoratedObject()
    {
        $binaryString = hex2bin('860103');
        /** @var ImplicitlyTaggedObject $object */
        $object = ImplicitlyTaggedObject::fromBinary($binaryString);

        $integer = $object->getDecoratedObject(Identifier::INTEGER);
        $this->assertInstanceOf(Integer::class, $integer);
        $this->assertEquals('3', (string) $integer);
    }
}