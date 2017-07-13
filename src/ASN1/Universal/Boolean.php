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

use FG\ASN1\Content;
use FG\ASN1\ElementBuilder;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\ASN1Object;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;

class Boolean extends ASN1Object
{
    private $value;

    const FALSE = 0x00;

    const TRUE = 0xFF;

    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {

        parent::__construct($identifier, $contentLength, $content,$children);

        if(!$this->identifier->isConstructed) {
            $this->setValue($content);
        }
    }

    public function __toString(): string
    {
        return $this->value ? 'true' : 'false';
    }

    public function getType()
    {
        return Identifier::BOOLEAN;
    }

    protected function calculateContentLength()
    {
        return 1;
    }

    protected function getEncodedValue()
    {
        if ($this->value === false) {
            return chr(self::FALSE);
        } else {
            return chr(self::TRUE);
        }
    }

    public function getStringValue()
    {
        return $this->value ? 'true' : 'false';
    }

    public function setValue(Content $content)
    {
        $valueOctet = isset($content->binaryData[0]) ? ord($content->binaryData[0]) : null;

        switch ($valueOctet) {
            case self::FALSE:
                $this->value = false;
                break;
            case self::TRUE:
                $this->value = true;
                break;
        }
    }

    public static function encodeValue($value)
    {
        return $value ? chr(self::TRUE) : chr(self::FALSE);
    }

    /**
     * @param bool  $value
     *
     * @return self
     */
    public static function create(bool $value)
    {
        $isConstructed = false;
        $lengthForm    = ContentLength::SHORT_FORM;

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                Identifier::BOOLEAN,
                $isConstructed,
                $value,
                $lengthForm
            );
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        $boolean = parent::fromBinary($binaryData, $offsetIndex);

        $contentLength = $boolean->getContentLength()->getLength();

        if ($contentLength !== 1) {
            throw new ParserException(
                sprintf(
                    'An ASN.1 Boolean should not have a length other than one. Extracted length was %d',
                    $contentLength
                ),
                $offsetIndex
            );
        }

        return $boolean;
    }
}
