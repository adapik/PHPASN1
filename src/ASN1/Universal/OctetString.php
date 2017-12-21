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
use FG\ASN1\ASN1Object;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;

class OctetString extends ASN1Object
{
    protected $value;

    public function __construct(
        Identifier $identifier,
        ContentLength $contentLength,
        Content $content,
        array $children = []
    ) {
        parent::__construct($identifier, $contentLength, $content, $children);

        if (!$this->identifier->isConstructed()) {
            $this->setValue($content);
        }
    }

    public function setValue(Content $content)
    {
        $this->value = $content->getBinary();
    }

    public static function encodeValue($value)
    {
        //данные в бинарном виде as is
        return $value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    public static function createFromString(string $string, $options = [])
    {
        $isConstructed = $options['isConstructed'] ?? false;
        $lengthForm    = \strlen($string) > 127 ? ContentLength::LONG_FORM : ContentLength::SHORT_FORM;
        $lengthForm    = $options['lengthForm'] ?? $lengthForm;

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                Identifier::OCTETSTRING,
                $isConstructed,
                $string,
                $lengthForm
            );
    }
}
