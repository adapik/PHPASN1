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

use FG\ASN1\AbstractCharacterString;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;
use FG\ASN1\Content;

class BMPString extends AbstractCharacterString
{
    /**
     * Creates a new ASN.1 BMP String.
     *
     * BMPString is a subtype of UniversalString that has its own
     * unique tag and contains only the characters in the
     * Basic Multilingual Plane (those corresponding to the first
     * 64K-2 cells, less cells whose encoding is used to address
     * characters outside the Basic Multilingual Plane) of ISO/IEC 10646-1.
     *
     * TODO The encodable characters of this type are not yet checked.
     *
     * @param string $string
     */
    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {
        parent::__construct($identifier, $contentLength, $content, $children);
    }

    public static function getType()
    {
        return Identifier::BMP_STRING;
    }
}
