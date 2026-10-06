<?php

namespace FG\ASN1\Mapper;

use FG\ASN1\AbstractTaggedObject;
use FG\ASN1\ASN1ObjectInterface;
use FG\ASN1\Identifier;

/**
 * Class Mapper
 */
class Mapper
{
    /**
     * Creates structure from ASN1 Objects based on provided mapping
     */
    public function map(ASN1ObjectInterface $object, array $mapping)
    {
        if ($this->isTaggedObject($mapping)) {
            return $this->mapTaggedObject($object, $mapping);
        }

        if ($mapping['type'] === Identifier::ANY) {
            return $object;
        }

        if ($mapping['type'] === Identifier::CHOICE) {
            return $this->mapChoiceObject($object, $mapping);
        }

        $expectedClass = $mapping['class'] ?? Identifier::CLASS_UNIVERSAL;
        if ($mapping['type'] !== $object->getIdentifier()->getTagNumber() ||
            $expectedClass !== $object->getIdentifier()->getTagClass()
        ) {
            return null;
        }

        if ($mapping['type'] === Identifier::SEQUENCE) {
            return $this->mapSequenceObject($object, $mapping);
        }

        if ($mapping['type'] === Identifier::SET) {
            return $this->mapSetObject($object, $mapping);
        }

        return $object;
    }

    /**
     * Whether is tagged object mapping ot not
     */
    private function isTaggedObject(array $mapping): bool
    {
        return array_key_exists('explicit', $mapping) || array_key_exists('implicit', $mapping);
    }

    /**
     * Processing tagged mapping
     *
     * @return array|ASN1ObjectInterface|null
     */
    private function mapTaggedObject(ASN1ObjectInterface $object, array $mapping)
    {
        $tagNumber = $object->getIdentifier()->getTagNumber();

        if (!array_key_exists('constant', $mapping) || $mapping['constant'] !== $tagNumber) {
            return null;
        }

        if (array_key_exists('explicit', $mapping) && \count($object->getChildren()) === 1) {
            $object = $object->getChildren()[0];
        }

        if (array_key_exists('implicit', $mapping) &&
            array_key_exists('type', $mapping) &&
            $object instanceof AbstractTaggedObject
        ) {
            $class         = $mapping['class'] ?? Identifier::CLASS_UNIVERSAL;
            $isConstructed = array_key_exists('children', $mapping) && \count($mapping['children']) > 0;
            $object        = $object->getDecoratedObject($mapping['type'], $class, $isConstructed);
        }

        unset($mapping['explicit'], $mapping['implicit'], $mapping['constant']);

        return $this->map($object, $mapping);
    }

    /**
     * Processing Choice mapping
     */
    private function mapChoiceObject(ASN1ObjectInterface $object, array $mapping)
    {
        foreach ($mapping['children'] as $option) {
            if ($matched = $this->map($object, $option)) {
                return $matched;
            }
        }

        return null;
    }

    /**
     * Processing Sequence mapping
     */
    private function mapSequenceObject(ASN1ObjectInterface $object, $mapping)
    {
        $map = [];

        if (array_key_exists('min', $mapping) && array_key_exists('max', $mapping)) {
            return $this->mapSetOf($object, $mapping);
        }

        $childrenMapping = $mapping['children'];

        foreach ($object->getChildren() as $currentChild) {
            $matched = null;

            while (\count($childrenMapping) > 0) {
                $currentMapping = reset($childrenMapping);
                $currentKey     = key($childrenMapping);
                array_shift($childrenMapping);

                $matched = $this->map($currentChild, $currentMapping);
                if (null !== $matched) {
                    $map[$currentKey] = $matched;
                    break;
                }

                // only optional elements may be skipped
                if (($currentMapping['optional'] ?? false) !== true) {
                    return null;
                }
            }

            // schema is exhausted but there are still children left
            if (null === $matched) {
                return null;
            }
        }

        foreach ($childrenMapping as $remaining) {
            if (!array_key_exists('optional', $remaining)) {
                return null;
            }
        }

        return $map;
    }

    /**
     * Processing Set mapping
     */
    private function mapSetObject(ASN1ObjectInterface $object, array $mapping)
    {
        $map = [];

        if (array_key_exists('min', $mapping) && array_key_exists('max', $mapping)) {
            return $this->mapSetOf($object, $mapping);
        }

        $childrenMapping = $mapping['children'];
        foreach ($object->getChildren() as $child) {
            foreach ($childrenMapping as $key => $childMapping) {
                $matched = $this->map($child, $childMapping);
                if ($matched) {
                    $map[$key] = $matched;
                    unset($childrenMapping[$key]);
                }
            }
        }

        $unprocessedMappings = array_filter($childrenMapping, function ($map) {
            return !array_key_exists('optional', $map);
        });


        if (\count($unprocessedMappings) > 0) {
            return null;
        }

        return $map;
    }

    /**
     * Processing SetOf and SequenceOf mapping
     */
    private function mapSetOf(ASN1ObjectInterface $object, array $mapping)
    {
        $map = [];

        $childMapping = $mapping['children'];
        foreach ($object->getChildren() as $childObject) {
            $matched = $this->map($childObject, $childMapping);
            if ($matched === null) {
                return null;
            }

            $map[] = $matched;
        }

        return $map;
    }
}
