<?php

namespace FG\ASN1;

class ImplicitlyTaggedObject extends AbstractTaggedObject
{
    protected function getEncodedValue()
    {
        return $this->content->getBinary();
    }

    public function getStringValue()
    {
        return $this->content->getBinary();
    }

    public function __toString(): string
    {
        return '[' . $this->getIdentifier()->getTagNumber() . ']'.$this->getBinaryContent();
    }
}
