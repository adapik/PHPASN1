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
use FG\ASN1\Object;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;
use FG\ASN1\Content;

class Sequence extends Object
{
    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {
        parent::__construct($identifier, $contentLength, $content, $children);
    }

    protected function getEncodedValue()
    {
        $result = '';
        foreach ($this->children as $component) {
            $result .= $component->getBinary();
        }

        return $result;
    }

    public static function create(array $children = [], $options = [])
    {
        $hasIndefiniteLength = (bool) array_filter($children, function(Object $child) {
            return $child->getContentLength()->getLengthForm() === ContentLength::INDEFINITE_FORM;
        });

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                Identifier::SEQUENCE,
                true,
                null,
                $hasIndefiniteLength ? ContentLength::INDEFINITE_FORM : ContentLength::SHORT_FORM,
                $children
            );
    }
}
