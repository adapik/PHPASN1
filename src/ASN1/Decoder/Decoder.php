<?php

namespace FG\ASN1\Decoder;

use FG\ASN1\ASN1Object;
use FG\ASN1\Content;
use FG\ASN1\ContentLength;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\ExplicitlyTaggedObject;
use FG\ASN1\Identifier;
use FG\ASN1\IdentifierManager;
use FG\ASN1\ImplicitlyTaggedObject;
use FG\ASN1\Universal\BitString;
use FG\ASN1\Universal\BMPString;
use FG\ASN1\Universal\Boolean;
use FG\ASN1\Universal\CharacterString;
use FG\ASN1\Universal\Enumerated;
use FG\ASN1\Universal\EOC;
use FG\ASN1\Universal\GeneralizedTime;
use FG\ASN1\Universal\GeneralString;
use FG\ASN1\Universal\GraphicString;
use FG\ASN1\Universal\IA5String;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\NullObject;
use FG\ASN1\Universal\NumericString;
use FG\ASN1\Universal\ObjectDescriptor;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\OctetString;
use FG\ASN1\Universal\PrintableString;
use FG\ASN1\Universal\RelativeObjectIdentifier;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\Set;
use FG\ASN1\Universal\T61String;
use FG\ASN1\Universal\UniversalString;
use FG\ASN1\Universal\UTCTime;
use FG\ASN1\Universal\UTF8String;
use FG\ASN1\Universal\VisibleString;
use FG\ASN1\UnknownConstructedObject;
use FG\ASN1\UnknownObject;

class Decoder
{
    /**
     * @param string $binaryData
     * @param int    $offsetIndex
     *
     * @throws ParserException
     * @return ASN1Object
     */
    public function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        if (\strlen($binaryData) <= $offsetIndex) {
            throw new ParserException(
                'Can not parse binary from data: Offset index larger than input size',
                $offsetIndex
            );
        }

        $identifierOctets = $this->parseBinaryIdentifier($binaryData, $offsetIndex);
        $identifier       = new Identifier($identifierOctets);
        $lengthOctets     = $this->parseContentLength($binaryData, $offsetIndex);
        $contentLength    = new ContentLength($lengthOctets);

        $children = [];
        //запоминаем начало элемента
        $startPos = $offsetIndex;
        $nrOfContentOctets = $contentLength->getLength();
        //для составных элементов ищем детей
        if ($identifier->isConstructed()) {
            $children = $this->parseChildren($binaryData, $offsetIndex, $contentLength);
            //разница между текущем положением сдвига и стартовым - длина детей, блина контента составного элемента
            $nrOfContentOctets = abs($startPos - $offsetIndex);
        } else {
            if ($contentLength->getLengthForm() === ContentLength::INDEFINITE_FORM) {
                for (;;) {
                    if (\strlen($binaryData) <= $offsetIndex) {
                        throw new ParserException(
                            'Can not parse binary from data: Offset index larger than input size',
                            $offsetIndex
                        );
                    }

                    $firstOctet  = $binaryData[$offsetIndex];
                    $secondOctet = $binaryData[$offsetIndex++];
                    if ($firstOctet . $secondOctet === \chr(0) . \chr(0)) {
                        $nrOfContentOctets = abs($startPos - $offsetIndex) + 1;
                        break;
                    }
                }
            }
        }

        //если неопределенная форма - вычитаем 2 октета на EOC
        if ($contentLength->getLengthForm() === ContentLength::INDEFINITE_FORM) {
            $nrOfContentOctets -= 2;
        }

        $contentOctets = substr($binaryData, $startPos, $nrOfContentOctets);
        //если сдвигов не происходило (дети не парсились) прибавим длину контента
        if ($offsetIndex === $startPos) {
            $offsetIndex = $startPos + $nrOfContentOctets;
        }
        $content       = new Content($contentOctets);
        $contentLength = new ContentLength($lengthOctets, $nrOfContentOctets);

        if ($identifier->getTagClass() === Identifier::CLASS_UNIVERSAL) {
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
            }
        }

        if ($identifier->getTagClass() === Identifier::CLASS_CONTEXT_SPECIFIC) {
            if ($identifier->isConstructed()) {
                return new ExplicitlyTaggedObject($identifier, $contentLength, $content, $children);
            } else {
                return new ImplicitlyTaggedObject($identifier, $contentLength, $content, $children);
            }
        }

        if ($identifier->isConstructed()) {
            return new UnknownConstructedObject($identifier, $contentLength, $content, $children);
        } else {
            return new UnknownObject($identifier, $contentLength, $content, $children);
        }
    }

    protected function parseBinaryIdentifier($binaryData, &$offsetIndex)
    {
        if (\strlen($binaryData) <= $offsetIndex) {
            throw new ParserException(
                'Can not parse identifier from data: Offset index larger than input size',
                $offsetIndex
            );
        }

        $identifier = $binaryData[$offsetIndex++];

        if (IdentifierManager::isLongForm(\ord($identifier)) === false) {
            return $identifier;
        }

        while (true) {
            if (\strlen($binaryData) <= $offsetIndex) {
                throw new ParserException(
                    'Can not parse identifier (long form) from data: Offset index larger than input size',
                    $offsetIndex
                );
            }
            $nextOctet  = $binaryData[$offsetIndex++];
            $identifier .= $nextOctet;

            if ((\ord($nextOctet) & 0x80) === 0) {
                // the most significant bit is 0 to we have reached the end of the identifier
                break;
            }
        }

        return $identifier;
    }

    protected function parseContentLength(&$binaryData, &$offsetIndex)
    {
        if (\strlen($binaryData) <= $offsetIndex) {
            throw new ParserException(
                'Can not parse content length from data: Offset index larger than input size',
                $offsetIndex
            );
        }

        $contentLengthOctets = $binaryData[$offsetIndex++];
        $firstOctet          = \ord($contentLengthOctets);

        if (($firstOctet & 0x80) != 0) {
            // bit 8 is set -> this is the long form
            $nrOfLengthOctets = $firstOctet & 0x7F;
            for ($i = 0; $i < $nrOfLengthOctets; $i++) {
                if (\strlen($binaryData) <= $offsetIndex) {
                    throw new ParserException(
                        'Can not parse content length (long form) from data: Offset index larger than input size',
                        $offsetIndex
                    );
                }
                $contentLengthOctets .= $binaryData[$offsetIndex++];
            }
        }

        return $contentLengthOctets;
    }

    /**
     * @param               $binaryData
     * @param int           $offsetIndex
     * @param Identifier    $identifier
     * @param ContentLength $contentLength
     *
     * @return Object[]
     * @throws ParserException
     */
    protected function parseChildren(&$binaryData, &$offsetIndex, ContentLength $contentLength)
    {
        $children = [];
        if (!is_nan($contentLength->getLength())) {
            $octetsToRead = $contentLength->getLength();
            while ($octetsToRead > 0) {
                $newChild = $this->fromBinary($binaryData, $offsetIndex);
                if (\is_null($newChild)) {
                    throw new ParserException('Children not found', $offsetIndex);
                }
                $octetsToRead -= (
                    $newChild->getContentLength()->getLength() +
                    $newChild->getIdentifier()->getNrOfOctets() +
                    $newChild->getContentLength()->getNrOfOctets()
                );
                $children[] = $newChild;
            }
        } else {
            /*try {*/
            for (;;) {
                $newChild = $this->fromBinary($binaryData, $offsetIndex);
                if ($newChild instanceof EOC) {
                    break;
                }
                $children[] = $newChild;
            }
        }

        return $children;
    }
}