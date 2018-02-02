<?php

namespace Toilal\Doctrine\Migrations\Liquibase;


use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Visitor\Visitor;

class LiquibaseSchemaVisitor implements Visitor
{
    /**
     * @var LiquibaseOutput
     */
    private $output;

    /**
     * LiquibaseSchemaVisitor constructor.
     *
     * @param LiquibaseOutput $output
     */
    public function __construct(LiquibaseOutput $output)
    {
        $this->output = $output;
    }


    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     *
     * @return void
     */
    public function acceptSchema(Schema $schema)
    {
        $this->output->createSchema($schema->getName());
    }

    /**
     * @param \Doctrine\DBAL\Schema\Table $table
     *
     * @return void
     */
    public function acceptTable(Table $table)
    {
        $this->output->createTable($table);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Table $table
     * @param \Doctrine\DBAL\Schema\Column $column
     *
     * @return void
     */
    public function acceptColumn(Table $table, Column $column)
    {
        // do nothing. Columns are created with table.
    }

    /**
     * @param \Doctrine\DBAL\Schema\Table $localTable
     * @param \Doctrine\DBAL\Schema\ForeignKeyConstraint $fkConstraint
     *
     * @return void
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        $this->output->createForeignKey($fkConstraint, $localTable);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Table $table
     * @param \Doctrine\DBAL\Schema\Index $index
     *
     * @return void
     */
    public function acceptIndex(Table $table, Index $index)
    {
        // do nothing. Indexes are created with table
    }

    /**
     * @param \Doctrine\DBAL\Schema\Sequence $sequence
     *
     * @return void
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->output->createSequence($sequence);
    }
}