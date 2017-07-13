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

class UnknownObject extends ASN1Object
{
    protected function calculateContentLength()
    {
        return $this->getContentLength()->getLength();
    }

    protected function getEncodedValue()
    {
        return '';
    }

    public function __toString(): string
    {
        return 'Unknown Object';
    }
}
