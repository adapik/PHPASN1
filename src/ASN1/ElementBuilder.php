<?php

namespace FG\ASN1;

use FG\ASN1\Universal;

class ElementBuilder
{
    public static function createObject($tagClass, $tagName, $isConstructed, $value, $lengthForm, $children = [])
    {
        $identifier = self::createIdentifier($tagClass, $tagName, $isConstructed);

        if ($identifier->getTagClass() === Identifier::CLASS_UNIVERSAL && $identifier->isConstructed() === false) {
            //для простых элементов вызываем конструктор
            switch ($identifier->getTagNumber()) {
                case Identifier::BITSTRING:
                    $value = Universal\BitString::encodeValue($value);
                    break;
                case Identifier::BOOLEAN:
                    $value = Universal\Boolean::encodeValue($value);
                    break;
                case Identifier::ENUMERATED:
                    $value = Universal\Enumerated::encodeValue($value);
                    break;
                case Identifier::INTEGER:
                    $value = Universal\Integer::encodeValue($value);
                    break;
                case Identifier::NULL:
                    $value = Universal\NullObject::encodeValue($value);
                    break;
                case Identifier::OBJECT_IDENTIFIER:
                    $value = Universal\ObjectIdentifier::encodeValue($value);
                    break;
                case Identifier::RELATIVE_OID:
                    $value = Universal\RelativeObjectIdentifier::encodeValue($value);
                    break;
                case Identifier::OCTETSTRING:
                    $value = Universal\OctetString::encodeValue($value);
                    break;
                case Identifier::UTC_TIME:
                    $value = Universal\UTCTime::encodeValue($value);
                    break;
                case Identifier::GENERALIZED_TIME:
                    $value = Universal\GeneralizedTime::encodeValue($value);
                    break;
                case Identifier::IA5_STRING:
                    $value = Universal\IA5String::encodeValue($value);
                    break;
                case Identifier::PRINTABLE_STRING:
                    $value = Universal\PrintableString::encodeValue($value);
                    break;
                case Identifier::NUMERIC_STRING:
                    $value = Universal\NumericString::encodeValue($value);
                    break;
                case Identifier::UTF8_STRING:
                    $value = Universal\UTF8String::encodeValue($value);
                    break;
                case Identifier::UNIVERSAL_STRING:
                    $value = Universal\UniversalString::encodeValue($value);
                    break;
                case Identifier::CHARACTER_STRING:
                    $value = Universal\CharacterString::encodeValue($value);
                    break;
                case Identifier::GENERAL_STRING:
                    $value = Universal\GeneralString::encodeValue($value);
                    break;
                case Identifier::VISIBLE_STRING:
                    $value = Universal\VisibleString::encodeValue($value);
                    break;
                case Identifier::GRAPHIC_STRING:
                    $value = Universal\GraphicString::encodeValue($value);
                    break;
                case Identifier::BMP_STRING:
                    $value = Universal\BMPString::encodeValue($value);
                    break;
                case Identifier::T61_STRING:
                    $value = Universal\T61String::encodeValue($value);
                    break;
                case Identifier::OBJECT_DESCRIPTOR:
                    $value = Universal\ObjectDescriptor::encodeValue($value);
                    break;
                default:
                    // At this point the identifier may be >1 byte.
                    if ($identifier->isConstructed()) {
                        $value = UnknownConstructedObject::encodeValue($value);
                    } else {
                        $value = UnknownObject::encodeValue($value);
                    }
            }
        }

        if ($children) {
            $contentOctets = '';
            foreach ($children as $child) {
                $contentOctets .= $child->getBinary();
            }
        } else {
            $contentOctets = $value;
        }

        $contentLength = self::createContentLength($contentOctets, $lengthForm);

        $content = new Content($contentOctets);

        if ($identifier->getTagClass() === Identifier::CLASS_UNIVERSAL) {
            //для простых элементов вызываем конструктор
            switch ($identifier->getTagNumber()) {
                case Identifier::BITSTRING:
                    return new Universal\BitString($identifier, $contentLength, $content, $children);
                case Identifier::BOOLEAN:
                    return new Universal\Boolean($identifier, $contentLength, $content, $children);
                case Identifier::ENUMERATED:
                    return new Universal\Enumerated($identifier, $contentLength, $content, $children);
                case Identifier::INTEGER:
                    return new Universal\Integer($identifier, $contentLength, $content, $children);
                case Identifier::NULL:
                    return new Universal\NullObject($identifier, $contentLength, $content, $children);
                case Identifier::OBJECT_IDENTIFIER:
                    return new Universal\ObjectIdentifier($identifier, $contentLength, $content, $children);
                case Identifier::RELATIVE_OID:
                    return new Universal\RelativeObjectIdentifier($identifier, $contentLength, $content, $children);
                case Identifier::OCTETSTRING:
                    return new Universal\OctetString($identifier, $contentLength, $content, $children);
                case Identifier::SEQUENCE:
                    return new Universal\Sequence($identifier, $contentLength, $content, $children);
                case Identifier::SET:
                    return new Universal\Set($identifier, $contentLength, $content, $children);
                case Identifier::UTC_TIME:
                    return new Universal\UTCTime($identifier, $contentLength, $content, $children);
                case Identifier::GENERALIZED_TIME:
                    return new Universal\GeneralizedTime($identifier, $contentLength, $content, $children);
                case Identifier::IA5_STRING:
                    return new Universal\IA5String($identifier, $contentLength, $content, $children);
                case Identifier::PRINTABLE_STRING:
                    return new Universal\PrintableString($identifier, $contentLength, $content, $children);
                case Identifier::NUMERIC_STRING:
                    return new Universal\NumericString($identifier, $contentLength, $content, $children);
                case Identifier::UTF8_STRING:
                    return new Universal\UTF8String($identifier, $contentLength, $content, $children);
                case Identifier::UNIVERSAL_STRING:
                    return new Universal\UniversalString($identifier, $contentLength, $content, $children);
                case Identifier::CHARACTER_STRING:
                    return new Universal\CharacterString($identifier, $contentLength, $content, $children);
                case Identifier::GENERAL_STRING:
                    return new Universal\GeneralString($identifier, $contentLength, $content, $children);
                case Identifier::VISIBLE_STRING:
                    return new Universal\VisibleString($identifier, $contentLength, $content, $children);
                case Identifier::GRAPHIC_STRING:
                    return new Universal\GraphicString($identifier, $contentLength, $content, $children);
                case Identifier::BMP_STRING:
                    return new Universal\BMPString($identifier, $contentLength, $content, $children);
                case Identifier::T61_STRING:
                    return new Universal\T61String($identifier, $contentLength, $content, $children);
                case Identifier::OBJECT_DESCRIPTOR:
                    return new Universal\ObjectDescriptor($identifier, $contentLength, $content, $children);
                default:
                    // At this point the identifier may be >1 byte.
                    if ($identifier->isConstructed()) {
                        return new UnknownConstructedObject($identifier, $contentLength, $content, $children);
                    } else {
                        return new UnknownObject($identifier, $contentLength, $content, $children);
                    }
            }
        }

        if ($identifier->getTagClass() === Identifier::CLASS_CONTEXT_SPECIFIC) {
            if ($identifier->isConstructed()) {
                return new ExplicitlyTaggedObject($identifier, $contentLength, $content, $children);
            } else {
                return new ImplicitlyTaggedObject($identifier, $contentLength, $content, $children);
            }
        }
    }

    protected static function createIdentifier($tagClass, $isConstructed, $tagNumber)
    {
        $identifierOctets = IdentifierManager::create($tagClass, $tagNumber, $isConstructed);
        return new Identifier($identifierOctets);
    }

    public static function createContentLength($content, $lengthForm)
    {
        $length = strlen($content);
        if ($lengthForm === ContentLength::INDEFINITE_FORM) {
            $lengthOctets = chr(128);
        } else {
            if ($length <= 127) {
                $lengthOctets = chr($length);
            } else {
                $temp         = ltrim(pack('N', $length), chr(0));
                $lengthOctets = pack('Ca*', 0x80 | strlen($temp), $temp);
            }
        }

        return new ContentLength($lengthOctets, $length);
    }
}
