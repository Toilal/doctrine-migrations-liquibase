<?php

declare(strict_types=1);

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

    private LiquibaseOutput $output;

    public function __construct(LiquibaseOutput $output)
    {
        $this->output = $output;
    }

    public function acceptSchema(Schema $schema): void
    {
        $this->output->createSchema($schema->getName());
    }

    public function acceptTable(Table $table): void
    {
        $this->output->createTable($table);
    }

    public function acceptColumn(Table $table, Column $column): void
    {
        // do nothing. Columns are created with table.
    }

    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint): void
    {
        $this->output->createForeignKey($fkConstraint, $localTable);
    }

    public function acceptIndex(Table $table, Index $index): void
    {
        // do nothing. Indexes are created with table
    }

    public function acceptSequence(Sequence $sequence): void
    {
        $this->output->createSequence($sequence);
    }

}
