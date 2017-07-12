<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1;

class UnknownObject extends Object
{
    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {
        parent::__construct($identifier, $contentLength, $content, $children);
    }

    protected function calculateContentLength()
    {
        return $this->getContentLength()->getLength();
    }

    protected function getEncodedValue()
    {
        return '';
    }
}
