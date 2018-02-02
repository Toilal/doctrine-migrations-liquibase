<?php

namespace Toilal\Doctrine\Migrations\Liquibase;


use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\ORM\EntityManagerInterface;

interface LiquibaseOutput
{

    /**
     * @param string $newNamespace
     *
     * @return void
     */
    public function createSchema($newNamespace);

    /**
     * @param ForeignKeyConstraint $orphanedForeignKey
     * @param Table $localTable
     *
     * @return void
     */
    public function dropForeignKey($orphanedForeignKey, $localTable);

    /**
     * @param Sequence $sequence
     *
     * @return void
     */
    public function alterSequence($sequence);

    /**
     * @param Sequence $sequence
     *
     * @return void
     */
    public function dropSequence($sequence);

    /**
     * @param Sequence $sequence
     *
     * @return void
     */
    public function createSequence($sequence);

    /**
     * @param Table $table
     *
     * @return void
     */
    public function createTable($table);

    /**
     * @param ForeignKeyConstraint $foreignKey
     * @param Table $table
     *
     * @return void
     */
    public function createForeignKey($foreignKey, $table);

    /**
     * @param Table $table
     *
     * @return void
     */
    public function dropTable($table);

    /**
     * @param TableDiff $tableDiff
     *
     * @return void
     */
    public function alterTable($tableDiff);

    /**
     * @param EntityManagerInterface $em
     * @return void
     */
    public function started($em);

    /**
     * @return void
     */
    public function terminated();

    /**
     * @return mixed
     */
    public function getResult();
}