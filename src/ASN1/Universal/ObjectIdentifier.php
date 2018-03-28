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

use Exception;
use FG\ASN1\Base128;
use FG\ASN1\Content;
use FG\ASN1\ElementBuilder;
use FG\ASN1\ASN1Object;
use FG\ASN1\Identifier;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\ContentLength;

class ObjectIdentifier extends ASN1Object
{
    protected $subIdentifiers;
    protected $value;

    public function __construct(
        Identifier $identifier,
        ContentLength $contentLength,
        Content $content,
        array $children = []
    ) {
        parent::__construct($identifier, $contentLength, $content, $children);
    }

    public static function getType()
    {
        return Identifier::OBJECT_IDENTIFIER;
    }

    public function __toString(): string
    {
        return (string) $this->getValue();
    }

    private function getValue()
    {
        if ($this->value === null) {
            $this->setValue($this->content);
        }

        return $this->value;
    }

    /**
     * Parses an object identifier except for the first octet, which is parsed
     * differently. This way relative object identifiers can also be parsed
     * using this.
     *
     * @param $binaryData
     * @param $offsetIndex
     * @param $octetsToRead
     *
     * @throws ParserException
     * @return string
     */
    protected static function parseOid(&$binaryData, &$offsetIndex, $octetsToRead)
    {
        $oid = '';

        while ($octetsToRead > 0) {
            $octets = '';

            do {
                if (0 === $octetsToRead) {
                    throw new ParserException('Malformed ASN.1 Object Identifier', $offsetIndex - 1);
                }

                $octetsToRead--;
                $octet  = $binaryData[$offsetIndex++];
                $octets .= $octet;
            } while (ord($octet) & 0x80);

            $oid .= sprintf('%d.', Base128::decode($octets));
        }

        // Remove trailing '.'
        return substr($oid, 0, -1) ?: '';
    }

    private function setValue(Content $content)
    {
        $binaryData  = $content->getBinary();
        $offsetIndex = 0;
        $firstOctet  = ord($binaryData[$offsetIndex++]);
        $oidString   = floor($firstOctet / 40) . '.' . ($firstOctet % 40);
        $oidString   .= '.' . self::parseOid($binaryData, $offsetIndex, $this->getContentLength()->getLength() - 1);
        $this->value = $value = $oidString;

        $this->subIdentifiers = explode('.', $value);
        $nrOfSubIdentifiers   = count($this->subIdentifiers);

        for ($i = 0; $i < $nrOfSubIdentifiers; $i++) {
            $this->subIdentifiers[$i] = (int)$this->subIdentifiers[$i];
        }

        // Merge the first to arcs of the OID registration tree (per ASN definition!)
        if ($nrOfSubIdentifiers >= 2) {
            $this->subIdentifiers[1] = ($this->subIdentifiers[0] * 40) + $this->subIdentifiers[1];
            unset($this->subIdentifiers[0]);
        }
    }

    public static function encodeValue($oid)
    {
        $parts = explode('.', $oid);
        $value = chr(40 * $parts[0] + $parts[1]);
        $iMax  = count($parts);
        for ($i = 2; $i < $iMax; $i++) {
            $temp = '';
            if (!$parts[$i]) {
                $temp = "\0";
            } else {
                while ($parts[$i]) {
                    $temp      = chr(0x80 | ($parts[$i] & 0x7F)) . $temp;
                    $parts[$i] >>= 7;
                }
                $temp[strlen($temp) - 1] = $temp[strlen($temp) - 1] & chr(0x7F);
            }
            $value .= $temp;
        }

        return $value;
    }

    public static function create(string $oid)
    {
        $subIdentifiers     = explode('.', $oid);
        $nrOfSubIdentifiers = count($subIdentifiers);

        for ($i = 0; $i < $nrOfSubIdentifiers; $i++) {
            if (!is_numeric($subIdentifiers[$i])) {
                throw new Exception(
                    "[{$oid}] is no valid object identifier (sub identifier " .
                    ($i + 1) .
                    ' is not numeric)!'
                );
            }
        }

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                static::getType(),
                false,
                $oid,
                ContentLength::SHORT_FORM
            );
    }
}
