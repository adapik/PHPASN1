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

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;

abstract class Construct extends ASN1Object implements Countable, ArrayAccess, Iterator, Parsable
{
    private $iteratorPosition;

    public function rewind()
    {
        $this->iteratorPosition = 0;
    }

    public function current()
    {
        return $this->children[$this->iteratorPosition];
    }

    public function key()
    {
        return $this->iteratorPosition;
    }

    public function next()
    {
        $this->iteratorPosition++;
    }

    public function valid()
    {
        return isset($this->children[$this->iteratorPosition]);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->children);
    }

    public function offsetGet($offset)
    {
        return $this->children[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $offset = count($this->children);
        }

        $this->children[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->children[$offset]);
    }

    protected function calculateContentLength()
    {
        $length = 0;
        foreach ($this->children as $component) {
            $length += $component->getObjectLength();
        }

        return $length;
    }

    protected function getEncodedValue()
    {
        $result = '';
        foreach ($this->children as $component) {
            $result .= $component->getBinary();
        }

        return $result;
    }

    public function addChild(ASN1Object $child)
    {
        $this->children[] = $child;
    }

    public function __toString(): string
    {
        $nrOfChildren = $this->getNumberOfChildren();
        $childString  = $nrOfChildren === 1 ? 'child' : 'children';

        return "[{$nrOfChildren} {$childString}]";
    }

    public function getNumberOfChildren()
    {
        return count($this->children);
    }

    /**
     * @return \FG\ASN1\ASN1Object[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return \FG\ASN1\ASN1Object
     */
    public function getFirstChild()
    {
        return $this->children[0];
    }

    public function count($mode = COUNT_NORMAL)
    {
        return count($this->children, $mode);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->children);
    }
}
