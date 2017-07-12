<?php

namespace FG\Test\ASN1;

use FG\ASN1\Identifier;
use FG\ASN1\TemplateParser;
use FG\ASN1\Universal\BitString;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\Set;
use PHPUnit_Framework_TestCase;

class TemplateParserTest extends PHPUnit_Framework_TestCase
{
    public function testParseBase64()
    {
        $sequence = Sequence::create([
            Set::create([
                ObjectIdentifier::create('1.2.250.1.16.9'),
                Sequence::create([
                    Integer::create(42),
                    BitString::createFromHexString('A0120043'),
                ])
            ])
        ]);

        $data = base64_encode($sequence->getBinary());

        $template = [
            Identifier::SEQUENCE => [
                Identifier::SET => [
                    Identifier::OBJECT_IDENTIFIER,
                    Identifier::SEQUENCE => [
                        Identifier::INTEGER,
                        Identifier::BITSTRING,
                    ],
                ],
            ],
        ];

        $parser = new TemplateParser();
        $object = $parser->parseBase64($data, $template);
        $this->assertInstanceOf(Set::class, $object->getChildren()[0]);
        $this->assertInstanceOf(ObjectIdentifier::class, $object->getChildren()[0]->getChildren()[0]);
        $this->assertInstanceOf(Sequence::class, $object->getChildren()[0]->getChildren()[1]);
        $this->assertInstanceOf(Integer::class, $object->getChildren()[0]->getChildren()[1]->getChildren()[0]);
        $this->assertInstanceOf(BitString::class, $object->getChildren()[0]->getChildren()[1]->getChildren()[1]);
    }
}
