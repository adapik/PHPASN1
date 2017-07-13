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
    /** @var \FG\ASN1\ASN1Object[] */
    private $decoratedObjects;

    protected function calculateContentLength()
    {
        $length = 0;
        foreach ($this->decoratedObjects as $object) {
            $length += $object->getObjectLength();
        }

        return $length;
    }

    protected function getEncodedValue()
    {
        return $this->content->binaryData;
    }

    public function getStringValue()
    {
        return $this->content->binaryData;
    }
}