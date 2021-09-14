<?php

declare(strict_types=1);

namespace Toilal\Doctrine\Migrations\Liquibase;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Toilal\Doctrine\Migrations\Liquibase\LiquibaseOutput;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Sequence;

/**
 * @coversDefaultClass Toilal\Doctrine\Migrations\Liquibase\LiquibaseSchemaVisitor
 */
final class LiquibaseSchemaVisitorTest extends TestCase
{

    private LiquibaseSchemaVisitor $object;
    private ObjectProphecy $output;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->output = $this->prophesize(LiquibaseOutput::class);
        $this->object = new LiquibaseSchemaVisitor($this->output->reveal());
    }

    /**
     * @test
     * @covers ::acceptSchema
     * @covers ::__construct
     */
    public function acceptSchema(): void
    {
        $schema = new Schema();
        $this->output->createSchema('public')->shouldBeCalled();
        $this->object->acceptSchema($schema);
    }

    /**
     * @test
     * @covers ::acceptTable
     */
    public function acceptTable(): void
    {
        $table = new Table('test');
        $this->output->createTable($table)->shouldBeCalled();
        $this->object->acceptTable($table);
    }

    /**
     * @covers ::acceptForeignKey
     */
    public function testAcceptForeignKey()
    {
        $localTable   = new Table('test');
        $fkConstraint = new ForeignKeyConstraint(['localField'], 'foreignTable', ['foreignField']);
        $this->output->createForeignKey($fkConstraint, $localTable)->shouldBeCalled();
        $this->object->acceptForeignKey($localTable, $fkConstraint);
    }

    /**
     * @covers ::acceptSequence
     */
    public function testAcceptSequence()
    {
        $sequence = new Sequence('test');
        $this->output->createSequence($sequence)->shouldBeCalled();
        $this->object->acceptSequence($sequence);
    }

}
