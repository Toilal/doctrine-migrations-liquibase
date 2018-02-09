<?php

namespace Toilal\Doctrine\Migrations\Liquibase;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

class LiquibaseSchemaTool extends SchemaTool
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * LiquibaseSchemaTool constructor.
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct($em)
    {
        parent::__construct($em);
        $this->em = $em;
    }

    /**
     * @param LiquibaseOutput|LiquibaseOutputOptions|null $output $output
     *
     * @return LiquibaseOutput
     */
    private function sanitizeOutputParameter($output = null)
    {
        if ($output instanceof LiquibaseOutputOptions) {
            return new LiquibaseDOMDocumentOuput($output);
        } else if ($output instanceof LiquibaseOutput) {
            return $output;
        }
        return new LiquibaseDOMDocumentOuput();
    }

    /**
     * @param array|null $metadata $metadata
     *
     * @return array
     */
    private function sanitizeMetadatas($metadata = null)
    {
        if (!$metadata) {
            $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        }
        usort($metadata, function ($a, $b) {
            /** @var ClassMetadata $a */
            /** @var ClassMetadata $b */
            return strcmp($a->getName(), $b->getName());
        });
        return $metadata;
    }

    /**
     * Generate a diff changelog from differences between actual database state and doctrine metadata.
     *
     * @param LiquibaseOutput|LiquibaseOutputOptions|null $output
     * @param array|null $metadata
     * @return \DOMDocument|mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function diffChangeLog($output = null, $metadata = null)
    {
        $output = $this->sanitizeOutputParameter($output);
        $metadata = $this->sanitizeMetadatas($metadata);

        $sm = $this->em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $this->removeLiquibaseTables($fromSchema);
        $toSchema = $this->getSchemaFromMetadata($metadata);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        return $this->diffChangeLogFromSchemaDiff($schemaDiff, $output);
    }

    /**
     * Generate a full changelog from doctrine metadata.
     *
     * @param LiquibaseOutput|LiquibaseOutputOptions|null $output
     * @param array|null $metadata
     * @return \DOMDocument|mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function changeLog($output = null, $metadata = null)
    {
        $output = $this->sanitizeOutputParameter($output);
        $metadata = $this->sanitizeMetadatas($metadata);

        $schema = $this->getSchemaFromMetadata($metadata);
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
     * @param LiquibaseOutput|LiquibaseOutputOptions|null $output
     * @return \DOMDocument|mixed
     */
    public function diffChangeLogFromSchemaDiff(SchemaDiff $schemaDiff, $output = null)
    {
        $output = $this->sanitizeOutputParameter($output);

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
            
            foreach ($table->getForeignKeys() as $foreignKey) {
                $output->createForeignKey($foreignKey, $table);
            }
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