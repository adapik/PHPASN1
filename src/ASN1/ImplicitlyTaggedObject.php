<?php
/**
 * Created by PhpStorm.
 * User: 109
 * Date: 22.10.2015
 * Time: 15:59
 */

namespace FG\ASN1;


class ImplicitlyTaggedObject extends AbstractTaggedObject
{
    protected function getEncodedValue()
    {
        return $this->content->binaryData;
    }

    public function getStringValue()
    {
        return $this->content->binaryData;
    }

    public function __toString(): string
    {
        return '[' . $this->getIdentifier()->getTagNumber() . ']'.$this->getBinaryContent();
    }
}