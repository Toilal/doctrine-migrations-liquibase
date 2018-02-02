<?php

namespace Toilal\Doctrine\Migrations\Liquibase;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\ORM\Tools\SchemaTool;

class LiquibaseSchemaTool extends SchemaTool
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * LiquibaseSchemaTool constructor.
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct($em)
    {
        parent::__construct($em);
        $this->em = $em;
    }

    /**
     * @param array $classes
     * @param LiquibaseOutput $output
     * @throws \Doctrine\ORM\ORMException
     */
    public function getUpdateChangelog(array $classes, LiquibaseOutput $output)
    {
        $sm = $this->em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $this->removeLiquibaseTables($fromSchema);
        $toSchema = $this->getSchemaFromMetadata($classes);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        $this->generateUpdateChangelog($schemaDiff, $output);
    }

    private function removeLiquibaseTables(Schema $fromSchema)
    {
        // TODO: Make this configurable
        $fromSchema->dropTable('liquibase');
        $fromSchema->dropTable('liquibase_lock');
    }

    /**
     * @param array $classes
     * @param LiquibaseOutput $output
     * @throws \Doctrine\ORM\ORMException
     */
    public function getCreateChangelog(array $classes, LiquibaseOutput $output)
    {
        $schema = $this->getSchemaFromMetadata($classes);
        $liquibaseVisitor = new LiquibaseSchemaVisitor($output);
        $output->started($this->em);
        $schema->visit($liquibaseVisitor);
        $output->terminated();
    }

    protected function generateUpdateChangelog(SchemaDiff $schemaDiff, LiquibaseOutput $output)
    {
        $output->started($this->em);

        foreach ($schemaDiff->newNamespaces as $newNamespace) {
            $output->createSchema($newNamespace);
        }

        foreach ($schemaDiff->orphanedForeignKeys as $orphanedForeignKey) {
            $output->dropForeignKey($orphanedForeignKey, $orphanedForeignKey->getLocalTable());
        }

        foreach ($schemaDiff->changedSequences as $sequence) {
            $output->alterSequence($sequence);
        }

        foreach ($schemaDiff->removedSequences as $sequence) {
            $output->dropSequence($sequence);
        }

        foreach ($schemaDiff->newSequences as $sequence) {
            $output->createSequence($sequence);
        }

        foreach ($schemaDiff->newTables as $table) {
            $output->createTable($table);
        }

        foreach ($schemaDiff->removedTables as $table) {
            $output->dropTable($table);
        }

        foreach ($schemaDiff->changedTables as $tableDiff) {
            $output->alterTable($tableDiff);
        }

        $output->terminated();
    }
}