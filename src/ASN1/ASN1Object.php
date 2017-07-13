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
use FG\ASN1\Universal\BitString;
use FG\ASN1\Universal\Boolean;
use FG\ASN1\Universal\Enumerated;
use FG\ASN1\Universal\EOC;
use FG\ASN1\Universal\GeneralizedTime;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\NullObject;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\RelativeObjectIdentifier;
use FG\ASN1\Universal\OctetString;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\Set;
use FG\ASN1\Universal\UTCTime;
use FG\ASN1\Universal\IA5String;
use FG\ASN1\Universal\PrintableString;
use FG\ASN1\Universal\NumericString;
use FG\ASN1\Universal\UTF8String;
use FG\ASN1\Universal\UniversalString;
use FG\ASN1\Universal\CharacterString;
use FG\ASN1\Universal\GeneralString;
use FG\ASN1\Universal\VisibleString;
use FG\ASN1\Universal\GraphicString;
use FG\ASN1\Universal\BMPString;
use FG\ASN1\Universal\T61String;
use FG\ASN1\Universal\ObjectDescriptor;

/**
 * @property ASN1Object $parent
 * @property ASN1Object[] $children
 * @property ContentLength $contentLength
 * @property Content $content
 * @property Identifier $identifier
 *
 * Class Object is the base class for all concrete ASN.1 objects.
 */
abstract class ASN1Object
{
    private $nrOfLengthOctets;

    /** @var \FG\ASN1\ASN1Object[] */
    protected $children = [];
    private $parent;

    public $identifier;
    public $contentLength;
    public $content;
    public $eoc;
    public $modified = false;

    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {
        $this->identifier = $identifier;
        $this->contentLength = $contentLength;
        $this->content = $content;
        if($identifier->isConstructed()) {
            $this->addChildren($children);
        }

        if($this->contentLength->form === ContentLength::INDEFINITE_FORM) {
            $this->eoc = new \FG\ASN1\EOC();
        }
    }

    public function addChild(ASN1Object $child)
    {
        $this->children[] = $child;
        $child->parent    = $this;
    }

    public function addChildren(array $children)
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    /**
     * Encode the object using DER encoding.
     *
     * @see http://en.wikipedia.org/wiki/X.690#DER_encoding
     *
     * @return string the binary representation of an objects value
     */
    abstract protected function getEncodedValue();

    /**
     * Return the content of this object in a non encoded form.
     * This can be used to print the value in human readable form.
     *
     * @return Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * @return Identifier
     */
    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    /**
     * @return ContentLength
     */
    protected function getContentLength(): ContentLength
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
     * Returns the name of the ASN.1 Type of this object.
     *
     * @see Identifier::getName()
     */
    public function getTypeName()
    {
        return $this->getIdentifier()->getCode();
    }

    /**
     * @param $binaryData
     * @param int $offsetIndex
     * @param Identifier $identifier
     * @param ContentLength $contentLength
     * @return Object[]
     * @throws ParserException
     */
    public static function parseChildren(&$binaryData, &$offsetIndex = 0, ContentLength $contentLength)
    {
        $children = [];
        if (!is_nan($contentLength->getLength())) {
            $octetsToRead = $contentLength->getLength();
            while ($octetsToRead > 0) {
                $newChild = ASN1Object::fromBinary($binaryData, $offsetIndex);
                if(is_null($newChild)) throw new ParserException('Children not found', $offsetIndex);
                $octetsToRead -= ($newChild->contentLength->getLength() + $newChild->identifier->getNrOfOctets() + $newChild->contentLength->getNrOfOctets());
                $children[] = $newChild;
            }
        } else {
            /*try {*/
            for (; ;) {
                $newChild = ASN1Object::fromBinary($binaryData, $offsetIndex);
                if ($newChild instanceof EOC) {
                    break;
                }
                $children[] = $newChild;
            }
        }

        return $children;
    }

    /**
     * @param string $binaryData
     * @param int $offsetIndex
     *
     * @throws ParserException
     * @return ASN1Object
     */
    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        if (strlen($binaryData) <= $offsetIndex) {
            throw new ParserException('Can not parse binary from data: Offset index larger than input size',
                $offsetIndex);
        }

        $identifierOctets = self::parseBinaryIdentifier($binaryData, $offsetIndex);
        $identifier       = new Identifier($identifierOctets);
        $lengthOctets     = self::parseContentLength($binaryData, $offsetIndex);
        $contentLength    = new ContentLength($lengthOctets);

        $children = [];
        //запоминаем начало элемента
        $startPos = $offsetIndex;
        //для составных элементов ищем детей
        if ($identifier->isConstructed) {
            $children = self::parseChildren($binaryData, $offsetIndex, $contentLength);
            //разница между текущем положением сдвига и стартовым - длина детей, блина контента составного элемента
            $contentLength->length = abs($startPos - $offsetIndex);
        } else {
            if($contentLength->form === ContentLength::INDEFINITE_FORM) {
                for (; ;) {
                    $firstOctet = $binaryData[$offsetIndex];
                    $secondOctet = $binaryData[$offsetIndex++];
                    if($firstOctet.$secondOctet === chr(0) . chr(0)) {
                        $contentLength->length = abs($startPos - $offsetIndex) + 1;
                        break;
                    }
                }
            }
        }

        //если неопределенная форма - вычитаем 2 октета на EOC
        if ($contentLength->form === ContentLength::INDEFINITE_FORM) {
            $contentLength->length -= 2;
        }

        //todo exception raises when object not constructed and length form is indefinite - its wrong
        if(!is_int($contentLength->length)) {
            throw new ParserException('Length of Object not determined', $offsetIndex);
        }

        $contentOctets = substr($binaryData, $startPos, $contentLength->length);
        //если сдвигов не происходило (дети не парсились) прибавим длину контента
        if ($offsetIndex === $startPos) {
            $offsetIndex = $startPos + $contentLength->length;
        }
        $content = new Content($contentOctets);

        if ($identifier->tagClass === Identifier::CLASS_UNIVERSAL) {
            //для простых элементов вызываем конструктор
            switch ($identifier->getTagNumber()) {
                case Identifier::EOC:
                    return new EOC($identifier, $contentLength, $content, $children);
                case Identifier::BITSTRING:
                    return new BitString($identifier, $contentLength, $content, $children);
                case Identifier::BOOLEAN:
                    return new Boolean($identifier, $contentLength, $content, $children);
                case Identifier::ENUMERATED:
                    return new Enumerated($identifier, $contentLength, $content, $children);
                case Identifier::INTEGER:
                    return new Integer($identifier, $contentLength, $content, $children);
                case Identifier::NULL:
                    return new NullObject($identifier, $contentLength, $content, $children);
                case Identifier::OBJECT_IDENTIFIER:
                    return new ObjectIdentifier($identifier, $contentLength, $content, $children);
                case Identifier::RELATIVE_OID:
                    return new RelativeObjectIdentifier($identifier, $contentLength, $content, $children);
                case Identifier::OCTETSTRING:
                    return new OctetString($identifier, $contentLength, $content, $children);
                case Identifier::SEQUENCE:
                    return new Sequence($identifier, $contentLength, $content, $children);
                case Identifier::SET:
                    return new Set($identifier, $contentLength, $content, $children);
                case Identifier::UTC_TIME:
                    return new UTCTime($identifier, $contentLength, $content, $children);
                case Identifier::GENERALIZED_TIME:
                    return new GeneralizedTime($identifier, $contentLength, $content, $children);
                case Identifier::IA5_STRING:
                    return new IA5String($identifier, $contentLength, $content, $children);
                case Identifier::PRINTABLE_STRING:
                    return new PrintableString($identifier, $contentLength, $content, $children);
                case Identifier::NUMERIC_STRING:
                    return new NumericString($identifier, $contentLength, $content, $children);
                case Identifier::UTF8_STRING:
                    return new UTF8String($identifier, $contentLength, $content, $children);
                case Identifier::UNIVERSAL_STRING:
                    return new UniversalString($identifier, $contentLength, $content, $children);
                case Identifier::CHARACTER_STRING:
                    return new CharacterString($identifier, $contentLength, $content, $children);
                case Identifier::GENERAL_STRING:
                    return new GeneralString($identifier, $contentLength, $content, $children);
                case Identifier::VISIBLE_STRING:
                    return new VisibleString($identifier, $contentLength, $content, $children);
                case Identifier::GRAPHIC_STRING:
                    return new GraphicString($identifier, $contentLength, $content, $children);
                case Identifier::BMP_STRING:
                    return new BMPString($identifier, $contentLength, $content, $children);
                case Identifier::T61_STRING:
                    return new T61String($identifier, $contentLength, $content, $children);
                case Identifier::OBJECT_DESCRIPTOR:
                    return new ObjectDescriptor($identifier, $contentLength, $content, $children);
                default:
                    // At this point the identifier may be >1 byte.
                    if ($identifier->isConstructed) {
                        return new UnknownConstructedObject($identifier, $contentLength, $content, $children);
                    } else {
                        return new UnknownObject($identifier, $contentLength, $content, $children);
                    }
            }
        }

        if ($identifier->tagClass === Identifier::CLASS_CONTEXT_SPECIFIC) {
            if ($identifier->isConstructed) {
                return new ExplicitlyTaggedObject($identifier, $contentLength, $content, $children);
            } else {
                return new ImplicitlyTaggedObject($identifier, $contentLength, $content, $children);
            }
        }
    }

    protected static function parseBinaryIdentifier($binaryData, &$offsetIndex)
    {
        if (strlen($binaryData) <= $offsetIndex) {
            throw new ParserException('Can not parse identifier from data: Offset index larger than input size', $offsetIndex);
        }

        $identifier = $binaryData[$offsetIndex++];

        if (IdentifierManager::isLongForm(ord($identifier)) === false) {
            return $identifier;
        }

        while (true) {
            if (strlen($binaryData) <= $offsetIndex) {
                throw new ParserException('Can not parse identifier (long form) from data: Offset index larger than input size', $offsetIndex);
            }
            $nextOctet = $binaryData[$offsetIndex++];
            $identifier .= $nextOctet;

            if ((ord($nextOctet) & 0x80) === 0) {
                // the most significant bit is 0 to we have reached the end of the identifier
                break;
            }
        }

        return $identifier;
    }

    protected static function parseContentLength(&$binaryData, &$offsetIndex)
    {
        if (strlen($binaryData) <= $offsetIndex) {
            throw new ParserException('Can not parse content length from data: Offset index larger than input size', $offsetIndex);
        }

        $contentLengthOctets = $binaryData[$offsetIndex++];
        $firstOctet = ord($contentLengthOctets);

        if (($firstOctet & 0x80) != 0) {
            // bit 8 is set -> this is the long form
            $nrOfLengthOctets = $firstOctet & 0x7F;
            for ($i = 0; $i < $nrOfLengthOctets; $i++) {
                if (strlen($binaryData) <= $offsetIndex) {
                    throw new ParserException('Can not parse content length (long form) from data: Offset index larger than input size', $offsetIndex);
                }
                $contentLengthOctets .= $binaryData[$offsetIndex++];
            }
        }

        return $contentLengthOctets;
    }

    public function getBinary()
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

        if ($this instanceof ObjectIdentifier && (string) $this === $oidString) {
            return [$this];
        }

        if($this->isConstructed()) {
            foreach ($this->children as $child) {
                $objectsFound = $child->findByOid($oidString);
                if(count($objectsFound) > 0) {
                    array_push($objects, ...$objectsFound);
                }
            }

            return $objects;
        }

        return [];
    }

    public function remove()
    {
        if($this->parent) {
            foreach ($this->parent->children as $key => $child) {
                if($child === $this) {
                        unset($this->parent->children[$key]);
                        $this->parent->children = $this->parent->getChildren();
                        $this->parent->rebuildTree();
                }
            }

            return $this->getParent();
        }

        return null;
    }

    public function getNrOfOctets()
    {
        return $this->identifier->getNrOfOctets() + $this->contentLength->getNrOfOctets() + $this->content->getNrOfOctets() + ($this->eoc ? $this->eoc->getNrOfOctets() : 0);
    }

    /**
     * Если у элемента поменяли (изъяли или добавили) контент, нужно заново энкодить всех его предков
     */
    public function rebuildTree()
    {
        $this->restoreContentFromParts();
        if($this->validateLengthContent()) {
            //nothing to rebuild
            return true;
        } else {
            //если форма неопределенная, то и у родителя она тоже неопределенная
            // и энкодировать длину ни у одного родителя не нужно - просто изменить контент у всех предков
            if($this->contentLength->getLengthForm() === ContentLength::INDEFINITE_FORM) {
                $this->contentLength->length = $this->content->getNrOfOctets();
            } else {
                $this->contentLength = ElementBuilder::createContentLength(
                    $this->content->getBinary(),
                    $this->contentLength->getLengthForm()
                );
            }

            if($this->parent) {
                $this->parent->rebuildTree();
            }
        }

        if($this->validateLengthContent()) {
            return true;
        } else {
            throw new \Exception('Дерево не восстановлено');
        }
    }

    public function validateLengthContent()
    {
        return $this->content->getNrOfOctets() === $this->contentLength->getLength();
    }

    public function restoreContentFromParts()
    {
        if($this->identifier->isConstructed()) {
            $contentOctets = '';
            foreach ($this->getChildren() as $child) {
                $contentOctets .= $child->getBinary();
            }
            $this->content = new Content($contentOctets);
        }

        return $this->content->getBinary();
    }

    final public function isConstructed()
    {
        return $this->identifier->isConstructed();
    }

    /**
     * @return \FG\ASN1\ASN1Object[]
     */
    public function getSiblings()
    {
        if($this->parent->isConstructed()) {
            $siblings = array_filter($this->parent->getChildren(), function($value) {
                if($value === $this) return false;

                return true;
            });

            return array_values($siblings);
        } else {
            return [];
        }
    }

    /**
     * @return \FG\ASN1\ASN1Object[]
     */
    public function getChildren()
    {
        reset($this->children);
        array_values($this->children);

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
        if($this->parent) {
            foreach ($this->parent->children as $key => $child) {
                if($child === $this) {
                    if($key + 1 === count($this->parent->children)) {
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
        if($this->parent) {
            foreach ($this->parent->children as $key => $child) {
                if($child === $this) {
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
     * @return \FG\ASN1\ASN1Object[]
     * @throws \Exception
     */
    public function findChildrenByType($className)
    {
        if(!class_exists($className)) {
            throw new Exception('Unknown class type object');
        }

        $children = array_filter($this->children, function($value) use ($className) {
            return is_a($value, $className);
        });

        return array_values($children);
    }

    /**
     * @param $fileContent
     * @return \FG\ASN1\ASN1Object
     *
     * @throws \Exception
     */
    public final static function fromFile($fileContent)
    {
        $temp = trim($fileContent);
        if(substr($fileContent, 0, 1) === '-') {
            $temp = preg_replace('#.*?^-+[^-]+-+#ms', '', $fileContent, 1);
            if(is_null($temp)) throw new \Exception('Preg_error:' . preg_last_error());
            $temp = preg_replace('#--+[^-]+--+#', '', $temp);
            if(is_null($temp)) throw new \Exception('Preg_error:' . preg_last_error());
        }

        $temp = str_replace(array("\r", "\n", ' '), '', $temp);
        $temp = preg_match('#^[a-zA-Z\d/+]*={0,2}$#', $temp) ? base64_decode($temp) : false;
        $file = $temp != false ? $temp : $fileContent;

        return self::fromBinary($file);
    }

    public function detach()
    {
        $object = clone $this;
        $this->parent = null;

        return $object;
    }

    /**
     * @return ASN1Object|Sequence
     */
    public function getRoot()
    {
        if(is_null($this->parent)) return $this;

        return $this->parent->getRoot();
    }

    public function getBinaryContent()
    {
        return $this->content->getBinary();
    }
}
