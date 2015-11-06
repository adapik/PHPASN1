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
use LogicException;

/**
 * @property Object $parent
 * @property Object[] $children
 * @property ContentLength $contentLength
 * @property Content $content
 * @property Identifier $identifier
 *
 * Class Object is the base class for all concrete ASN.1 objects.
 */
abstract class Object
{
    private $nrOfLengthOctets;

    /** @var \FG\ASN1\Object[] */
    protected $children;
    public $parent = null;

    public $identifier;
    public $contentLength;
    public $content;
    public $eoc = null;
    public $modified = false;

    protected function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {
        $this->identifier = $identifier;
        $this->contentLength = $contentLength;
        $this->content = $content;
        if($identifier->isConstructed) {
            $this->addChildren($children);
        }

        if($this->contentLength->form === ContentLength::INDEFINITE_FORM) {
            $this->eoc = new \FG\ASN1\EOC();
        }
    }

    public function addChild(Object $child)
    {
        $this->children[] = $child;
        $child->parent = $this;
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
     * @return mixed
     */
    abstract public function getContent();

    /**
     * Returns all identifier octets. If an inheriting class models a tag with
     * the long form identifier format, it MUST reimplement this method to
     * return all octets of the identifier.
     *
     * @throws LogicException If the identifier format is long form
     *
     * @return string Identifier as a set of octets
     */
    public function getIdentifier()
    {
        $firstOctet = $this->getType();

        if (Identifier::isLongForm($firstOctet)) {
            throw new LogicException(sprintf('Identifier of %s uses the long form and must therefor override "Object::getIdentifier()".', get_class($this)));
        }

        return chr($firstOctet);
    }

    private function createLengthPart()
    {
        $contentLength = $this->content->getNrOfOctets();
        $nrOfLengthOctets = $this->getNumberOfLengthOctets($contentLength);

        if ($nrOfLengthOctets == 1) {
            return chr($contentLength);
        } else {
            // the first length octet determines the number subsequent length octets
            $lengthOctets = chr(0x80 | ($nrOfLengthOctets - 1));
            for ($shiftLength = 8 * ($nrOfLengthOctets - 2); $shiftLength >= 0; $shiftLength -= 8) {
                $lengthOctets .= chr($contentLength >> $shiftLength);
            }

            return $lengthOctets;
        }
    }

    protected function getNumberOfLengthOctets($contentLength = null)
    {
        if (!isset($this->nrOfLengthOctets)) {
            if ($contentLength == null) {
                $contentLength = $this->getContentLength();
            }

            $this->nrOfLengthOctets = 1;
            if ($contentLength > 127) {
                do { // long form
                    $this->nrOfLengthOctets++;
                    $contentLength = $contentLength >> 8;
                } while ($contentLength > 0);
            }
        }

        return $this->nrOfLengthOctets;
    }

    protected function getContentLength()
    {
        return $this->contentLength->length;
    }

    protected function setContentLength($newContentLength)
    {
        $this->contentLength = $newContentLength;
        $this->getNumberOfLengthOctets($newContentLength);
    }

    /**
     * Returns the length of the whole object (including the identifier and length octets).
     */
    public function getObjectLength()
    {
        $nrOfIdentifierOctets = strlen($this->getIdentifier());
        $contentLength = $this->getContentLength();
        $nrOfLengthOctets = $this->getNumberOfLengthOctets($contentLength);

        return $nrOfIdentifierOctets + $nrOfLengthOctets + $contentLength;
    }

    public function __toString()
    {
        return $this->getContent();
    }

    /**
     * Returns the name of the ASN.1 Type of this object.
     *
     * @see Identifier::getName()
     */
    public function getTypeName()
    {
        return Identifier::getName($this->getType());
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
        if (!is_nan($contentLength->length)) {
            $octetsToRead = $contentLength->length;
            while ($octetsToRead > 0) {
                $newChild = Object::fromBinary($binaryData, $offsetIndex);
                $octetsToRead -= ($newChild->contentLength->length + strlen($newChild->identifier->binaryData) + strlen($newChild->contentLength->binaryData));
                $children[] = $newChild;
            }
        } else {
            /*try {*/
            for (; ;) {
                $newChild = Object::fromBinary($binaryData, $offsetIndex);
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
     *
     * @return \FG\ASN1\Object
     */
    public static final function fromBinary(&$binaryData, &$offsetIndex = 0)
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
            //если неопределенная форма - вычитаем 2 октета на EOC
            if ($contentLength->form === ContentLength::INDEFINITE_FORM) {
                $contentLength->length -= 2;
            }
        }

        $contentOctets = substr($binaryData, $startPos, $contentLength->length);
        //если сдвигов не происходило (дети не парсились) прибавим длину контента
        if ($offsetIndex === $startPos) {
            $offsetIndex = $startPos + $contentLength->length;
        }
        $content = new Content($contentOctets);

        if ($identifier->tagClass === Identifier::CLASS_UNIVERSAL) {
            //для простых элементов вызываем конструктор
            switch ($identifier->tagNumber) {
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

    protected static function parseIdentifier($identifierOctet, $expectedIdentifier, $offsetForExceptionHandling)
    {
        if (is_string($identifierOctet) || is_numeric($identifierOctet) == false) {
            $identifierOctet = ord($identifierOctet);
        }

        if ($identifierOctet != $expectedIdentifier) {
            $message = 'Can not create an '.Identifier::getName($expectedIdentifier).' from an '.Identifier::getName($identifierOctet);
            throw new ParserException($message, $offsetForExceptionHandling);
        }
    }

    protected static function parseBinaryIdentifier($binaryData, &$offsetIndex)
    {
        if (strlen($binaryData) <= $offsetIndex) {
            throw new ParserException('Can not parse identifier from data: Offset index larger than input size', $offsetIndex);
        }

        $identifier = $binaryData[$offsetIndex++];

        if (IdentifierManager::isLongForm(ord($identifier)) == false) {
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

    protected static function parseContentLength(&$binaryData, &$offsetIndex, $minimumLength = 0)
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

    public function render()
    {
        if(is_null($this->parent)) {
            echo '<div id="tree">';
        }

        echo '<div class="node">';

        $this->renderHead();
        $this->renderValue();

        if($this->children) {
            $this->renderChildren();
        }
        echo '</div>';

        if(is_null($this->parent)) {
            echo '</div>';
        }
    }

    public function renderHead()
    {
        echo '<div class="head">';
        echo strtoupper($this->identifier->code);

        echo '<span class="preview">';
        if($count = count($this->children)) {
            echo '(' . $count . ' elem)';
        } else {
            if(isset($this->value)) echo $this->getStringValue();
        }
        echo '</span>';
        echo '</div>';
    }

    public function renderValue()
    {

    }

    public function getStringValue()
    {
        return $this->value;
    }

    public function renderChildren() {
        echo '<div class="sub">';
        foreach ($this->children as $child) {
            $child->render();
        }
        echo '</div>';

    }

    public function getBinary()
    {
        return $this->identifier->binaryData . $this->contentLength->binaryData . $this->content->binaryData . (isset($this->eoc) ? $this->eoc->binaryData : '');
    }

    /**
     * @param $oidString
     *
     * @return Object[]
     */
    public function findByOid($oidString)
    {
        $objects = [];
        if($this->identifier->isConstructed) {
            foreach ($this->children as $child) {
                $objectFound = $child->findByOid($oidString);
                if($objectFound) {
                    if($objectFound instanceof self) {
                        $objects[] = $objectFound;
                    } elseif(is_array($objectFound) && !empty($objectFound)) {
                        $objects = array_merge($objects, $objectFound);
                    }
                }
            }
        } else {
            if($this instanceof ObjectIdentifier) {
                if($this->getStringValue() === $oidString)
                    return $this;
            }

            return null;
        }

        return $objects;
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
        }
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
            if($this->contentLength->form === ContentLength::INDEFINITE_FORM) {
                $this->contentLength->length = $this->content->getNrOfOctets();
            } else {
                $lengthOctets = $this->createLengthPart();
                $this->contentLength = new ContentLength($lengthOctets);
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
        if($this->content->getNrOfOctets() === $this->contentLength->length) return true;

        return false;
    }

    public function restoreContentFromParts()
    {
        if($this->identifier->isConstructed) {
            $contentOctets = '';
            foreach ($this->getChildren() as $child) {
                $contentOctets .= $child->getBinary();
            }
            $this->content = new Content($contentOctets);
        }

        return $this->content->binaryData;
    }

    final public function isConstructed()
    {
        if($this->identifier->isConstructed) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \FG\ASN1\Object[]
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

    public function getChildren()
    {
        if($this->isConstructed()) {
            reset($this->children);
            array_values($this->children);
            return $this->children;
        } else {
            return [];
        }
    }

    public function getParent()
    {
        if($this->parent) {

            return $this->parent;
        } else {

            return null;
        }
    }

    public function insertAfter(Object $object)
    {
        if($this->parent) {
            foreach ($this->parent->children as $key => $child) {
                if($child === $this) {
                    array_splice($this->parent->children, $key + 1, 0, [$object]);
                    $this->parent->rebuildTree();
                }
            }
        }
    }

    public function insertBefore(Object $object)
    {
        if($this->parent) {
            foreach ($this->parent->children as $key => $child) {
                if($child === $this) {
                    array_splice($this->parent->children, $key, 0, [$object]);
                    $this->parent->rebuildTree();
                }
            }
        }
    }

    /**
     * @param string $className
     * @return \FG\ASN1\Object[]
     * @throws \Exception
     */
    public function findChildrenByType($className)
    {
        if(!class_exists($className)) throw new \Exception('Class not defined');

        $children = array_filter($this->children, function($value) use ($className) {
            if (is_a($value, $className)) return true;

            return false;
        });

        return array_values($children);
    }

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
}
