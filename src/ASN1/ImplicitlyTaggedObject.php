<?php

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