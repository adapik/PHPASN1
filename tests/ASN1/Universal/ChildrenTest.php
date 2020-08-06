<?php
/**
 * ChildrenTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Adapik/CMS
 */

namespace FG\Test\ASN1\Universal;

use FG\ASN1\Exception\Exception;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\NullObject;
use FG\ASN1\Universal\Sequence;
use PHPUnit\Framework\TestCase;

class ChildrenTest extends TestCase
{
    public function testRemoveChild()
    {
        $unknownChild = Integer::create(0);

        // Creating same objects
        $object = Sequence::create([
            Sequence::create([NullObject::create()]),
            Integer::create(1),
            Sequence::create([NullObject::create()]),
            Integer::create(2),
            Sequence::create([NullObject::create()]), // to be removed.
            Integer::create(3),
            Sequence::create([NullObject::create()]),
            Integer::create(4),
        ]);

        $object->removeChild($object->getChildren()[4]);

        // Test that removed not first identical Sequence
        self::assertInstanceOf(Sequence::class, $object->getChildren()[0]);

        // Check we really removed
        self::assertCount(7, $object->getChildren());

        // check we have properly defined index
        $i = 0;
        foreach ($object->getChildren() as $index => $child) {

            self::assertEquals($i, $index);
            $i++;
        }

        // Check all children have correct parent
        foreach ($object->getChildren() as $child) {
            self::assertEquals($child->getParent(), $object);
        }

        $this->expectException(Exception::class);
        $object->removeChild($unknownChild);
    }

    public function testReplacedChildParent() {

        $childToReplace = Integer::create(1);
        $replacement = Integer::create(0);

        $object = Sequence::create([
            NullObject::create(),
            $childToReplace,
            NullObject::create(),
        ]);

        $expectedObject = Sequence::create([
            NullObject::create(),
            $replacement,
            NullObject::create(),
        ]);

        $expectedBinary = $expectedObject->getBinary();

        $object->replaceChild($childToReplace, $replacement);

        // Check binary data as expected after tree rebuild
        self::assertEquals($expectedObject->getBinary(), $expectedBinary);

        // Check all children have correct parent
        foreach ($object->getChildren() as $child) {
            self::assertEquals($child->getParent(), $object);
        }
    }

    public function testReplaceChild()
    {
        $unknownChild = Integer::create(0);

        $childToReplace = Sequence::create([NullObject::create()]);

        $object = Sequence::create([
            NullObject::create(),
            $childToReplace,
            NullObject::create(),
        ]);

        $replacement = NullObject::create();

        $object->replaceChild($childToReplace, $replacement);

        // New child now NullObject instead of Sequence
        foreach ($object->getChildren() as $child) {
            self::assertInstanceOf(NullObject::class, $child);
        }

        // We still have 1 child
        self::assertCount(3, $object->getChildren());

        // check we have properly defined index
        $i = 0;
        foreach ($object->getChildren() as $index => $child) {

            self::assertEquals($i, $index);
            $i++;
        }

        // Check all children have correct parent
        foreach ($object->getChildren() as $child) {
            self::assertEquals($child->getParent(), $object);
        }

        // Exception if we trying replace unknown child
        $this->expectException(Exception::class);
        $object->replaceChild($unknownChild, $replacement);
    }

    public function testAppendChild()
    {
        $childToAppend = Integer::create(0);
        $childrenToAppend = [Integer::create(1), Integer::create(2), Integer::create(3)];

        $object = Sequence::create([
            NullObject::create()
        ]);

        $object->appendChild($childToAppend);
        // We have +1 now
        self::assertCount(2, $object->getChildren());
        // First child still NullObject
        self::assertInstanceOf(NullObject::class, $object->getChildren()[0]);
        // Second child is Integer
        self::assertInstanceOf(Integer::class, $object->getChildren()[1]);

        $object->appendChild($childrenToAppend);
        // We have +1 + 3 now
        self::assertCount(5, $object->getChildren());
        /** @var Integer[] $integers */
        $integers = $object->findChildrenByType(Integer::class);

        // We have 4 children
        self::assertCount(4, $integers);
        $i = 0;
        foreach ($integers as $integer) {
            // Check integers have been properly positioned
            self::assertEquals($i, (int) $integer->__toString());
            $i++;
        }

        // check we have properly defined index
        $i = 0;
        foreach ($object->getChildren() as $index => $child) {
            self::assertEquals($i, $index);
            $i++;
        }

        // Check all children have correct parent
        foreach ($object->getChildren() as $child) {
            self::assertEquals($child->getParent(), $object);
        }
    }
}
