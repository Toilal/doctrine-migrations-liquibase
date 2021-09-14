<?php

declare(strict_types=1);

namespace Toilal\Doctrine\Migrations\Liquibase;

use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Index;

/**
 * @coversDefaultClass Toilal\Doctrine\Migrations\Liquibase\IndexColumns
 */
final class IndexColumnsTest extends TestCase
{

    /**
     * @test
     * @covers ::__construct
     * @covers ::getPrimaryColumns
     * @covers ::getPrimaryKeyColumns
     * @covers ::getUniqueColumns
     * @covers ::getOtherIndexes
     */
    public function noIndexColumnsPassed(): void
    {
        $table = $this->prophesize(Table::class);
        $table->getIndexes()
            ->shouldBeCalled()
            ->willReturn([]);

        $object = new IndexColumns($table->reveal());

        $this->assertSame([], $object->getPrimaryColumns());
        $this->assertSame([], $object->getPrimaryKeyColumns());
        $this->assertSame([], $object->getUniqueColumns());
        $this->assertSame([], $object->getOtherIndexes());
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::getPrimaryColumns
     * @covers ::getPrimaryKeyColumns
     * @covers ::getUniqueColumns
     * @covers ::getOtherIndexes
     */
    public function indexesArePassed(): void
    {
        $primaryIndex1 = new Index('primary1', ['test1', 'test2'], false, true);

        $uniqueIndex1 = new Index('unique1', ['test3'], true, false);
        $uniqueIndex2 = new Index('unique2', ['test4'], true, false);

        $otherIndex1 = new Index('other1', ['test7'], false, false);
        $otherIndex2 = new Index('other2', ['test8'], false, false);

        $table = $this->prophesize(Table::class);
        $table->getIndexes()
            ->shouldBeCalled()
            ->willReturn([
                $otherIndex1,
                $primaryIndex1,
                $uniqueIndex1,
                $uniqueIndex2,
                $otherIndex2,
        ]);

        $object = new IndexColumns($table->reveal());

        $this->assertSame(['test1', 'test2'], $object->getPrimaryColumns());
        $this->assertSame(['test1', 'test2'], $object->getPrimaryKeyColumns());
        $this->assertSame(
            [
                'test3' => $uniqueIndex1,
                'test4' => $uniqueIndex2
            ],
            $object->getUniqueColumns()
        );

        $this->assertSame([$otherIndex1, $otherIndex2], $object->getOtherIndexes());
    }

}
