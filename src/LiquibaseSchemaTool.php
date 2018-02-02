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
     * Generate a diff changelog from differences between actual database state and doctrine metadata.
     * 
     * @param array $classes
     * @param LiquibaseOutput|null $output
     * @return \DOMDocument|mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function diffChangeLog(array $classes, $output = null)
    {
        if (!$output) {
            $output = new LiquibaseDOMDocumentOuput();
        }

        $sm = $this->em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $this->removeLiquibaseTables($fromSchema);
        $toSchema = $this->getSchemaFromMetadata($classes);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        return $this->diffChangeLogFromSchemaDiff($schemaDiff, $output);
    }

    /**
     * Generate a full changelog from doctrine metadata.
     *
     * @param array $classes
     * @param LiquibaseOutput|null $output
     * @return \DOMDocument|mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function changeLog(array $classes, $output = null)
    {
        if (!$output) {
            $output = new LiquibaseDOMDocumentOuput();
        }

        $schema = $this->getSchemaFromMetadata($classes);
        $liquibaseVisitor = new LiquibaseSchemaVisitor($output);
        $output->started($this->em);
        $schema->visit($liquibaseVisitor);
        $output->terminated();

        return $output->getResult();
    }

    /**
     * Generate a diff changelog from SchemaDiff object.
     *
     * @param SchemaDiff $schemaDiff
     * @param LiquibaseOutput|null $output
     * @return \DOMDocument|mixed
     */
    public function diffChangeLogFromSchemaDiff(SchemaDiff $schemaDiff, $output = null)
    {
        if (!$output) {
            $output = new LiquibaseDOMDocumentOuput();
        }

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

        return $output->getResult();
    }

    private function removeLiquibaseTables(Schema $fromSchema)
    {
        // TODO: Make those table names configurable
        if ($fromSchema->hasTable('liquibase')) {
            $fromSchema->dropTable('liquibase');
        }
        if ($fromSchema->hasTable('liquibase_lock')) {
            $fromSchema->dropTable('liquibase_lock');
        }
    }
}