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

use Exception;

abstract class AbstractCharacterString extends ASN1Object implements CharacterStringInterface
{
    /** @var string */
    protected $value;
    private $checkStringForIllegalChars = true;
    private $allowedCharacters = [];

    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children)
    {

        parent::__construct($identifier, $contentLength, $content, $children);

        $this->value = $this->getBinaryContent();
        if(count($this->allowedCharacters) > 0) {
            $this->checkString();
        }
    }

    protected function allowCharacter($character)
    {
        $this->allowedCharacters[] = $character;
    }

    protected function allowCharacters(...$characters)
    {
        foreach ($characters as $character) {
            $this->allowedCharacters[] = $character;
        }
    }

    protected function allowNumbers()
    {
        foreach (range('0', '9') as $char) {
            $this->allowedCharacters[] = (string) $char;
        }
    }

    protected function allowAllLetters()
    {
        $this->allowSmallLetters();
        $this->allowCapitalLetters();
    }

    protected function allowSmallLetters()
    {
        foreach (range('a', 'z') as $char) {
            $this->allowedCharacters[] = $char;
        }
    }

    protected function allowCapitalLetters()
    {
        foreach (range('A', 'Z') as $char) {
            $this->allowedCharacters[] = $char;
        }
    }

    protected function allowSpaces()
    {
        $this->allowedCharacters[] = ' ';
    }

    protected function allowAll()
    {
        $this->checkStringForIllegalChars = false;
    }

    protected function calculateContentLength()
    {
        return strlen($this->value);
    }

    protected function getEncodedValue()
    {
        if ($this->checkStringForIllegalChars) {
            $this->checkString();
        }

        return $this->value;
    }

    public static function encodeValue($value)
    {
        return $value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    protected function checkString()
    {
        $stringLength = $this->getContentLength()->getLength();
        for ($i = 0; $i < $stringLength; $i++) {
            if (in_array($this->value[$i], $this->allowedCharacters, true) === false) {
                $typeName = IdentifierManager::getName($this->identifier->getTagNumber());
                throw new Exception("Could not create a {$typeName} from the character sequence '{$this->value}'. Symbol {$this->value[$i]}");
            }
        }
    }

    public static function createFromString(string $string, $options = [])
    {
        $isConstructed = $options['isConstructed'] ?? false;
        $lengthForm    = strlen($string) > 127 ? ContentLength::LONG_FORM : ContentLength::SHORT_FORM;
        $lengthForm    = $options['lengthForm'] ?? $lengthForm;

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                static::getType(),
                $isConstructed,
                $string,
                $lengthForm
            );
    }

    public static function isValid($string)
    {
        try {
            static::createFromString($string);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
