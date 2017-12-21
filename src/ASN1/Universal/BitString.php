<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1\Universal;

use FG\ASN1\ElementBuilder;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\Parsable;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;
use FG\ASN1\Content;

class BitString extends OctetString implements Parsable
{
    const IDENTIFIER = 0x1E;

    /**
     * @var int
     */
    private $nrOfUnusedBits;

    public function __construct(
        Identifier $identifier,
        ContentLength $contentLength,
        Content $content,
        array $children = []
    ) {
        parent::__construct($identifier, $contentLength, $content, $children);

        $this->nrOfUnusedBits = \ord($content->getBinary()[0]);
    }

    public function getNumberOfUnusedBits(): int
    {
        return $this->nrOfUnusedBits;
    }

    public function setValue(Content $content)
    {
        $this->value = bin2hex(substr($content->getBinary(), 1));
    }

    /**
     * @return string
     */
    public function getStringValue()
    {
        return strtoupper(bin2hex(substr($this->getBinaryContent(), 1)));
    }

    public static function createFromBitString(string $bitString, $options = []): self
    {
        $isConstructed = $options['isConstructed'] ?? false;
        $lengthForm    = $options['lengthForm'] ?? ContentLength::LONG_FORM;

        $bitsCount = \strlen($bitString);

        $nrOfUnusedBits = $bitsCount % 8;
        $bitString      .= str_repeat('0', $nrOfUnusedBits);

        $value = \chr($nrOfUnusedBits) . hex2bin(gmp_strval(gmp_init($bitString, 2), 16));

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                Identifier::BITSTRING,
                $isConstructed,
                $value,
                $lengthForm
            );
    }

    public static function createFromHexString(string $hexString, $options = []): self
    {
        $isConstructed = $options['isConstructed'] ?? false;
        $value         = \chr(0x00) . hex2bin($hexString);
        $lengthForm    = \strlen($value) > 127 ? ContentLength::LONG_FORM : ContentLength::SHORT_FORM;
        $lengthForm    = $options['lengthForm'] ?? $lengthForm;

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                Identifier::BITSTRING,
                $isConstructed,
                $value,
                $lengthForm
            );
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        $bitString = parent::fromBinary($binaryData, $offsetIndex);

        if ($bitString->getContent()->getNrOfOctets() < 2) {
            throw new ParserException('Malformed bit string', $offsetIndex);
        }

        return $bitString;
    }
}
