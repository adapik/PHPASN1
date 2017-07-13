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

use FG\ASN1\ContentLength;
use FG\ASN1\ElementBuilder;
use FG\ASN1\Identifier;
use FG\ASN1\ASN1Object;

class Set extends ASN1Object
{
    protected function getEncodedValue()
    {
        $result = '';
        foreach ($this->children as $component) {
            $result .= $component->getBinary();
        }

        return $result;
    }

    public function __toString(): string
    {
        return implode("\n", $this->getChildren());
    }

    public static function create(array $children = [], $options = [])
    {
        $hasIndefiniteLength = (bool)array_filter($children, function (ASN1Object $child) {
            return $child->getContentLength()->getLengthForm() === ContentLength::INDEFINITE_FORM;
        });

        if ($hasIndefiniteLength) {
            $lengthForm = ContentLength::INDEFINITE_FORM;
        } else {
            $lengthForm = ContentLength::SHORT_FORM;
        }

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                Identifier::SET,
                true,
                null,
                $lengthForm,
                $children
            );
    }
}
