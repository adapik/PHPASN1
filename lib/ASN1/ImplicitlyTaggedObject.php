<?php
/**
 * Created by PhpStorm.
 * User: 109
 * Date: 22.10.2015
 * Time: 15:59
 */

namespace FG\ASN1;


class ImplicitlyTaggedObject extends Object
{
    /** @var \FG\ASN1\Object[] */
    private $decoratedObjects;
    private $tag;

    /**
     * @param int $tag
     * @param \FG\ASN1\Object $objects,...
     */
    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {

        parent::__construct($identifier, $contentLength, $content, $children);
    }

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

    public function getContent()
    {
        return $this->children;
    }
}