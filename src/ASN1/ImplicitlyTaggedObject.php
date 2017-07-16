<?php
/**
 * Created by PhpStorm.
 * User: 109
 * Date: 22.10.2015
 * Time: 15:59
 */

namespace FG\ASN1;


class ImplicitlyTaggedObject extends ASN1Object
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
        return '[' . $this->getIdentifier()->getTagNumber() . ']' . $this->getBinaryContent();
    }

    public function getDecoratedObject($tagNumber, $tagClass = Identifier::CLASS_UNIVERSAL,  $isConstructed = false)
    {
        $identifierOctets = IdentifierManager::create($tagClass, $isConstructed, $tagNumber);

        $binary = $identifierOctets.$this->getContentLength()->getBinary().$this->getContent()->getBinary();

        return ASN1Object::fromBinary($binary);
    }
}