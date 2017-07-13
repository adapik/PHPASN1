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
use FG\ASN1\ASN1Object;
use FG\ASN1\Identifier;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\ContentLength;

class NullObject extends ASN1Object
{
    protected function getEncodedValue()
    {
        return null;
    }

    public function __toString(): string
    {
        return 'null';
    }

    public function getStringValue()
    {
        return 'null';
    }

    public static function encodeValue($value)
    {
        return '';
    }

    /**
     * @return self
     */
    public static function create()
    {
        $isConstructed = false;
        $lengthForm    = ContentLength::SHORT_FORM;

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                Identifier::NULL,
                $isConstructed,
                null,
                $lengthForm
            );
    }

    /**
     * {@inheritdoc}
     * @return self
     */
    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        $nullObject = parent::fromBinary($binaryData, $offsetIndex);

        $contentLength = $nullObject->getContentLength()->getLength();

        if ($contentLength !== 0) {
            throw new ParserException(
                sprintf(
                    'An ASN.1 Null should not have a length other than zero. Extracted length was %d',
                    $contentLength
                ),
                $offsetIndex
            );
        }

        return $nullObject;
    }
}
