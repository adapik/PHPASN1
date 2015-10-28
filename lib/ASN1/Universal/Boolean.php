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
use FG\ASN1\Object;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;

class Boolean extends Object
{
    private $value;

    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {

        parent::__construct($identifier, $contentLength, $content,$children);

        if(!$this->identifier->isConstructed) {
            $this->setValue($content);
        }
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
        if ($this->value == false) {
            return chr(0x00);
        } else {
            return chr(0xFF);
        }
    }

    public function getContent()
    {
        if ($this->value == true) {
            return 'TRUE';
        } else {
            return 'FALSE';
        }
    }

    public function setValue(Content $content)
    {
        $value = ord($content->binaryData[0]);
        $this->value = $value == 0xFF ? true : false;
    }
}
