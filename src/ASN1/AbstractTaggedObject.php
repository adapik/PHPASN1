<?php

namespace FG\ASN1;

/**
 *
 */
abstract class AbstractTaggedObject extends ASN1Object
{
    public function getDecoratedObject($tagNumber, $tagClass = Identifier::CLASS_UNIVERSAL, $isConstructed = false)
    {
        $identifierOctets = IdentifierManager::create($tagClass, $isConstructed, $tagNumber);

        $binary = $identifierOctets.$this->getContentLength()->getBinary().$this->getContent()->getBinary();

        return ASN1Object::fromBinary($binary);
    }
}
