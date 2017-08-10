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

use FG\ASN1\Exception\Exception;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\Sequence;

/**
 * @property ASN1Object    $parent
 * @property ASN1Object[]  $children
 * @property ContentLength $contentLength
 * @property Content       $content
 * @property Identifier    $identifier
 * Class Object is the base class for all concrete ASN.1 objects.
 */
abstract class ASN1Object implements ASN1ObjectInterface
{
    /** @var \FG\ASN1\ASN1Object[] */
    protected $children = [];
    private $parent;

    protected $identifier;
    protected $contentLength;
    protected $content;
    protected $eoc;

    public function __construct(
        Identifier $identifier,
        ContentLength $contentLength,
        Content $content,
        array $children = []
    ) {
        $this->identifier    = $identifier;
        $this->contentLength = $contentLength;
        $this->content       = $content;
        if ($identifier->isConstructed()) {
            $this->addChildren($children);
        }

        if ($this->contentLength->getLengthForm() === ContentLength::INDEFINITE_FORM) {
            $this->eoc = new EOC();
        }
    }

    private function addChild(ASN1Object $child)
    {
        $this->children[] = $child;
        $child->parent    = $this;
    }

    private function addChildren(array $children)
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    /**
     * @return Content
     */
    public function getContent(): ContentInterface
    {
        return $this->content;
    }

    /**
     * @return Identifier
     */
    public function getIdentifier(): IdentifierInterface
    {
        return $this->identifier;
    }

    /**
     * @return ContentLength
     */
    public function getContentLength(): ContentLengthInterface
    {
        return $this->contentLength;
    }

    /**
     * Returns the length of the whole object (including the identifier and length octets).
     */
    public function getObjectLength()
    {
        return $this->identifier->getNrOfOctets() +
            $this->contentLength->getNrOfOctets() +
            $this->content->getNrOfOctets() +
            ($this->eoc ? $this->eoc->getNrOfOctets() : 0);
    }

    /**
     * @return string
     */
    abstract public function __toString(): string;

    /**
     * @param string $binaryData
     * @param int    $offsetIndex
     *
     * @throws ParserException
     * @return ASN1Object
     */
    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        return (new Decoder\Decoder())->fromBinary($binaryData, $offsetIndex);
    }

    /**
     * @return string
     */
    public function getBinary(): string
    {
        return $this->identifier->getBinary()
            . $this->contentLength->getBinary()
            . $this->content->getBinary()
            . ($this->eoc ? $this->eoc->getBinary() : '');
    }

    /**
     * @param $oidString
     *
     * @return \FG\ASN1\Universal\ObjectIdentifier[]
     */
    public function findByOid(string $oidString): array
    {
        $objects = [];

        if ($this instanceof ObjectIdentifier && (string)$this === $oidString) {
            return [$this];
        }

        if ($this->isConstructed()) {
            foreach ($this->children as $child) {
                $objectsFound = $child->findByOid($oidString);
                if (count($objectsFound) > 0) {
                    array_push($objects, ...$objectsFound);
                }
            }

            return $objects;
        }

        return [];
    }

    public function remove()
    {
        if ($this->parent) {
            foreach ($this->parent->children as $key => $child) {
                if ($child === $this) {
                    unset($this->parent->children[$key]);
                    $this->parent->children = $this->parent->getChildren();
                    $this->parent->rebuildTree();
                }
            }

            return $this->getParent();
        }

        return null;
    }

    /**
     * @return int
     */
    public function getNrOfOctets(): int
    {
        return $this->identifier->getNrOfOctets() +
            $this->contentLength->getNrOfOctets() +
            $this->content->getNrOfOctets() +
            ($this->eoc ? $this->eoc->getNrOfOctets() : 0);
    }

    /**
     * Если у элемента поменяли (изъяли или добавили) контент, нужно заново энкодить всех его предков
     */
    public function rebuildTree()
    {
        $this->restoreContentFromParts();
        if ($this->validateLengthContent()) {
            //nothing to rebuild
            return true;
        } else {
            //если форма неопределенная, то и у родителя она тоже неопределенная
            // и энкодировать длину ни у одного родителя не нужно - просто изменить контент у всех предков
            if ($this->contentLength->getLengthForm() === ContentLength::INDEFINITE_FORM) {
                $this->contentLength = new ContentLength(
                    $this->contentLength->getBinary(),
                    $this->content->getNrOfOctets()
                );
            } else {
                $this->contentLength = ElementBuilder::createContentLength(
                    $this->content->getBinary(),
                    $this->contentLength->getLengthForm()
                );
            }

            if ($this->parent) {
                $this->parent->rebuildTree();
            }
        }

        if ($this->validateLengthContent()) {
            return true;
        } else {
            throw new \Exception('Дерево не восстановлено');
        }
    }

    private function validateLengthContent()
    {
        return $this->content->getNrOfOctets() === $this->contentLength->getLength();
    }

    private function restoreContentFromParts()
    {
        if ($this->identifier->isConstructed()) {
            $contentOctets = '';
            foreach ($this->getChildren() as $child) {
                $contentOctets .= $child->getBinary();
            }
            $this->content = new Content($contentOctets);
        }

        return $this->content->getBinary();
    }

    /**
     * @return bool
     */
    final public function isConstructed(): bool
    {
        return $this->identifier->isConstructed();
    }

    /**
     * @return ASN1Object[]
     */
    public function getSiblings(): array
    {
        if ($this->parent && $this->parent->isConstructed()) {
            $siblings = array_filter($this->parent->getChildren(), function ($value) {
                return $value !== $this;
            });

            return array_values($siblings);
        }

        return [];
    }

    /**
     * @return \FG\ASN1\ASN1Object[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return null|\FG\ASN1\ASN1Object
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function insertAfter(ASN1Object $object)
    {
        if ($this->parent) {
            foreach ($this->parent->children as $key => $child) {
                if ($child === $this) {
                    if ($key + 1 === count($this->parent->children)) {
                        array_push($this->parent->children, $object);
                    } else {
                        array_splice($this->parent->children, $key + 1, 0, [$object]);
                    }
                    $object->parent = $this->parent;
                    $this->parent->rebuildTree();
                }
            }
        }

        return $object;
    }

    public function insertBefore(ASN1Object $object)
    {
        if ($this->parent) {
            foreach ($this->parent->children as $key => $child) {
                if ($child === $this) {
                    array_splice($this->parent->children, $key, 0, [$object]);
                    $object->parent = $this->parent;
                    $this->parent->rebuildTree();
                }
            }
        }

        return $object;
    }

    /**
     * @param string $className
     *
     * @return \FG\ASN1\ASN1Object[]
     * @throws \Exception
     */
    public function findChildrenByType($className)
    {
        if (!class_exists($className)) {
            throw new Exception('Unknown class type object');
        }

        $children = array_filter($this->children, function ($value) use ($className) {
            return is_a($value, $className);
        });

        return array_values($children);
    }

    /**
     * @param $fileContent
     *
     * @return \FG\ASN1\ASN1Object
     * @throws \Exception
     */
    final public static function fromFile($fileContent)
    {
        $temp = trim($fileContent);
        if (substr($fileContent, 0, 1) === '-') {
            $temp = preg_replace('#.*?^-+[^-]+-+#ms', '', $fileContent, 1);
            if (is_null($temp)) throw new \Exception('Preg_error:' . preg_last_error());
            $temp = preg_replace('#--+[^-]+--+#', '', $temp);
            if (is_null($temp)) throw new \Exception('Preg_error:' . preg_last_error());
        }

        $temp = str_replace(array("\r", "\n", ' '), '', $temp);
        $temp = preg_match('#^[a-zA-Z\d/+]*={0,2}$#', $temp) ? base64_decode($temp) : false;
        $file = $temp != false ? $temp : $fileContent;

        return self::fromBinary($file);
    }

    public function detach()
    {
        $object       = clone $this;
        $this->parent = null;

        return $object;
    }

    /**
     * @return ASN1Object|Sequence
     */
    public function getRoot()
    {
        if (is_null($this->parent)) return $this;

        return $this->parent->getRoot();
    }

    public function getBinaryContent()
    {
        return $this->content->getBinary();
    }
}
