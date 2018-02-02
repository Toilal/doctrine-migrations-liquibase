<?php

namespace Toilal\Doctrine\Migrations\Liquibase;


use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;

class LiquibaseDOMDocumentOuput implements LiquibaseOutput
{
    /**
     * @var \DOMDocument
     */
    private $document;

    /** @var LiquibaseOutputOptions */
    private $options;

    /** @var AbstractPlatform */
    private $platform;

    /**
     * @var \DOMElement
     */
    private $root;


    /**
     * LiquibaseDOMDocumentOuput constructor.
     *
     * @param LiquibaseOutputOptions|null $options
     * @param \DOMDocument|null $document
     */
    public function __construct($options = null, $document = null)
    {
        if (!$options) {
            $options = new LiquibaseOutputOptions();
        }
        $this->options = $options;

        if (!$document) {
            $document = new \DOMDocument();
            $document->preserveWhiteSpace = false;
            $document->formatOutput = true;
            $this->document = $document;
        } else {
            $this->document = $document;
        }
    }

    /**
     * @return \DOMDocument
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return \DOMDocument
     */
    public function getResult()
    {
        return $this->document;
    }

    /**
     * @param string $id
     * @return \DOMElement
     */
    protected function createChangeSet($id)
    {
        $changeSet = $this->document->createElement('changeSet');
        $changeSet->setAttribute('author', $this->options->getChangeSetAuthor());
        $sanitizedId = preg_replace('/[_\.]/', '-', $id);
        $changeSet->setAttribute('id', $this->options->isChangeSetUniqueId() ? $sanitizedId : $sanitizedId . '-' . uniqid());
        $this->root->appendChild($changeSet);
        return $changeSet;
    }


    /**
     * @param string $newNamespace
     *
     * @return void
     */
    public function createSchema($newNamespace)
    {
        $changeSetElt = $this->createChangeSet('create-schema-' . $newNamespace);

        $sql = NULL;
        try {
            $sql = $this->platform->getCreateSchemaSQL($newNamespace);
        } catch (DBALException $e) {
            $sql = "CREATE SCHEMA `$newNamespace`";
        }

        $sqlElement = $this->document->createElement('sql');

        $sqlTextNode = $this->document->createTextNode($sql);
        $sqlElement->appendChild($sqlTextNode);

        $changeSetElt->appendChild($sqlElement);
        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param ForeignKeyConstraint $orphanedForeignKey
     * @param Table $localTable
     *
     * @return void
     */
    public function dropForeignKey($orphanedForeignKey, $localTable)
    {
        $changeSetElt = $this->createChangeSet('drop-foreign-key-' . $orphanedForeignKey->getName());

        $tableName = QualifiedName::fromAsset($localTable);
        $foreignKeyName = QualifiedName::fromAsset($orphanedForeignKey);

        $dropForeignKeyElement = $this->document->createElement('dropForeignKeyConstraint');

        if ($tableName->getNamespaceName()) {
            $dropForeignKeyElement->setAttribute('baseTableSchemaName', $tableName->getNamespaceName());
        }
        $dropForeignKeyElement->setAttribute('baseTableName', $tableName->getName());

        $dropForeignKeyElement->setAttribute('constraintName', $foreignKeyName->getName());

        $changeSetElt->appendChild($dropForeignKeyElement);
        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param Sequence $sequence
     *
     * @return void
     */
    public function alterSequence($sequence)
    {
        $commentElt = $this->document->createComment(' alterSequence is not supported (sequence: ' . $sequence->getName() . ')');
        $this->root->appendChild($commentElt);
    }

    /**
     * @param Sequence $sequence
     *
     * @return void
     */
    public function dropSequence($sequence)
    {
        $changeSetElt = $this->createChangeSet('drop-sequence-' . $sequence->getName());

        $sequenceName = QualifiedName::fromAsset($sequence);
        $dropSequenceElt = $this->document->createElement('dropSequence');

        if ($sequenceName->getNamespaceName()) {
            $dropSequenceElt->setAttribute('schemaName', $sequenceName->getNamespaceName());
        }

        $dropSequenceElt->setAttribute('sequenceName', $sequenceName->getName());

        $changeSetElt->appendChild($dropSequenceElt);
        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param Sequence $sequence
     *
     * @return void
     */
    public function createSequence($sequence)
    {
        $changeSetElt = $this->createChangeSet('create-sequence-' . $sequence->getName());

        $sequenceName = QualifiedName::fromAsset($sequence);
        $createSequenceElt = $this->document->createElement('createSequence');

        if ($sequenceName->getNamespaceName()) {
            $createSequenceElt->setAttribute('schemaName', $sequenceName->getNamespaceName());
        }
        $createSequenceElt->setAttribute('sequenceName', $sequenceName->getName());
        $createSequenceElt->setAttribute('startValue', $sequence->getInitialValue());

        $changeSetElt->appendChild($createSequenceElt);
        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param Column $column
     * @return null|string|string[]
     */
    protected function getColumnType(Column $column)
    {
        if ($column->getColumnDefinition()) {
            $sqlType = $column->getColumnDefinition();
            return $sqlType;
        } else if ($this->options->isUsePlatformTypes()) {
            $sqlType = $column->getType()->getSQLDeclaration($column->toArray(), $this->platform);
            $sqlType = preg_replace('/\(.*?\)/', '', $sqlType);
            return $sqlType;
        } else {
            $sqlType = $column->getType()->getName();

            if ($sqlType === 'integer') {
                $sqlType = 'int';
            }
            if ($sqlType === 'float') {
                $sqlType = 'double';
            }

            if ($column->getLength() !== null) {
                $sqlType .= '(' . $column->getLength() . ')';
            };

            return $sqlType;
        }
    }

    /**
     * @param \DOMElement $columnElt
     * @param Column $column
     * @param IndexColumns $indexColumns
     */
    protected function fillColumnAttributes(\DOMElement $columnElt, Column $column, IndexColumns $indexColumns)
    {
        $columnName = $column->getQuotedName($this->platform);
        $columnElt->setAttribute('name', $columnName);
        $columnElt->setAttribute('type', $this->getColumnType($column));
        if ($column->getComment()) {
            $columnElt->setAttribute('remarks', $column->getComment());
        }
        if ($column->getDefault() !== null) {
            $columnElt->setAttribute('defaultValue', $column->getDefault());
        }

        $primaryKey = in_array($column->getName(), $indexColumns->getPrimaryKeyColumns());
        $unique = false;
        $uniqueConstraintName = null;
        if (array_key_exists($column->getName(), $indexColumns->getUniqueColumns())) {
            $unique = true;
            $uniqueConstraintName = $indexColumns->getUniqueColumns()[$column->getName()]->getName();
        }
        $nullable = !$column->getNotnull();
        if (in_array($column, $indexColumns->getPrimaryKeyColumns())) {
            $primaryKey = true;
        }

        if ($primaryKey || !$nullable || $unique) {
            $constraintsElt = $this->document->createElement('constraints');
            if ($primaryKey) {
                $constraintsElt->setAttribute('primaryKey', $primaryKey ? "true" : "false");
            }
            if (!$nullable) {
                $constraintsElt->setAttribute('nullable', "false");
            }
            if ($unique) {
                $constraintsElt->setAttribute('unique', $unique ? "true" : "false");
            }
            if ($uniqueConstraintName) {
                $constraintsElt->setAttribute('uniqueConstraintName', $uniqueConstraintName);
            }

            $columnElt->appendChild($constraintsElt);
        }
    }

    /**
     * @param Table $table
     *
     * @return void
     */
    public function createTable($table)
    {
        $changeSetElt = $this->createChangeSet('create-table-' . $table->getName());

        $createTableElt = $this->document->createElement('createTable');

        $tableName = QualifiedName::fromAsset($table);
        if ($tableName->getNamespaceName()) {
            $createTableElt->setAttribute('schemaName', $tableName->getNamespaceName());
        }
        $createTableElt->setAttribute('tableName', $tableName->getName());

        $indexColumns = new IndexColumns($table);

        foreach ($table->getColumns() as $column) {
            $columnElt = $this->document->createElement('column');

            $this->fillColumnAttributes($columnElt, $column, $indexColumns);

            $createTableElt->appendChild($columnElt);
        }

        $changeSetElt->appendChild($createTableElt);

        foreach ($indexColumns->getOtherIndexes() as $index) {
            $createIndexElt = $this->document->createElement('createIndex');

            if ($tableName->getNamespaceName()) {
                $createIndexElt->setAttribute('schemaName', $tableName->getNamespaceName());
            }
            $createIndexElt->setAttribute('tableName', $tableName->getName());
            $createIndexElt->setAttribute('indexName', $index->getName());
            if ($index->isUnique()) {
                $createIndexElt->setAttribute('unique', $index->isUnique() ? "true" : "false");
            }

            foreach ($index->getColumns() as $column) {
                $columnElt = $this->document->createElement('column');
                $columnElt->setAttribute('name', $column);
                $createIndexElt->appendChild($columnElt);
            }

            $changeSetElt->appendChild($createIndexElt);
        }

        foreach ($table->getForeignKeys() as $foreignKey) {
            $addForeignKeyConstraintElt = $this->document->createElement('addForeignKeyConstraint');

            $this->fillForeignKeyAttributes($addForeignKeyConstraintElt, $foreignKey, $table);

            $changeSetElt->appendChild($addForeignKeyConstraintElt);
        }

        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param \DOMElement $addForeignKeyConstraintElt
     * @param ForeignKeyConstraint $foreignKey
     * @param Table $table
     */
    protected function fillForeignKeyAttributes(\DOMElement $addForeignKeyConstraintElt, ForeignKeyConstraint $foreignKey, Table $table)
    {
        $addForeignKeyConstraintElt->setAttribute('constraintName', $foreignKey->getName());

        $tableName = QualifiedName::fromAsset($table);

        if ($tableName->getNamespaceName()) {
            $addForeignKeyConstraintElt->setAttribute('baseTableSchemaName', $tableName->getNamespaceName());
        }
        $addForeignKeyConstraintElt->setAttribute('baseTableName', $tableName->getName());
        $addForeignKeyConstraintElt->setAttribute('baseColumnNames', implode(',', $foreignKey->getLocalColumns()));

        $referencedTableName = QualifiedName::fromQualifiedName($foreignKey->getForeignTableName());

        if ($referencedTableName->getNamespaceName()) {
            $addForeignKeyConstraintElt->setAttribute('referencedTableSchemaName', $referencedTableName->getNamespaceName());
        }
        $addForeignKeyConstraintElt->setAttribute('referencedTableName', $referencedTableName->getName());
        $addForeignKeyConstraintElt->setAttribute('referencedColumnNames', implode(',', $foreignKey->getForeignColumns()));
    }

    /**
     * @param ForeignKeyConstraint $foreignKey
     * @param Table $table
     *
     * @return void
     */
    public function createForeignKey($foreignKey, $table)
    {
        $changeSetElt = $this->createChangeSet('create-foreign-keys-' . $table->getName());
        $addForeignKeyConstraintElt = $this->document->createElement('addForeignKeyConstraint');

        $this->fillForeignKeyAttributes($addForeignKeyConstraintElt, $foreignKey, $table);

        $changeSetElt->appendChild($addForeignKeyConstraintElt);
        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param Table $table
     *
     * @return void
     */
    public function dropTable($table)
    {
        $changeSetElt = $this->createChangeSet('drop-table-' . $table->getName());
        $dropTableElt = $this->document->createElement('dropTable');

        $tableName = QualifiedName::fromAsset($table);

        if ($tableName->getNamespaceName()) {
            $dropTableElt->setAttribute('schemaName', $tableName->getNamespaceName());
        }
        $dropTableElt->setAttribute('tableName', $tableName->getName());
        // Should we add cascadeConstraints attribute ?
        // $dropTableElt->setAttribute('cascadeConstraints', 'false');

        $changeSetElt->appendChild($dropTableElt);
        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param TableDiff $tableDiff
     *
     * @return void
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function alterTable($tableDiff)
    {
        $changeSetElt = $this->createChangeSet('alter-table-' . $tableDiff->fromTable->getName());

        $fromTableName = QualifiedName::fromAsset($tableDiff->fromTable);

        $fromTableName = $this->alterTableRenameTable($tableDiff, $fromTableName, $changeSetElt);

        $indexColumns = new IndexColumns($tableDiff->fromTable);

        $this->alterTableAddedColumns($tableDiff, $fromTableName, $indexColumns, $changeSetElt);
        $this->alterTableAddedIndexes($tableDiff, $fromTableName, $indexColumns, $changeSetElt);
        $this->alterTableAddedForeignKeys($tableDiff, $changeSetElt);

        $this->alterTableRenamedColumns($tableDiff, $fromTableName, $changeSetElt);
        $this->alterTableRenamedIndexes($tableDiff, $fromTableName, $changeSetElt);

        foreach ($tableDiff->changedColumns as $column) {
            $this->alterTableChangedColumn($column, $fromTableName, $changeSetElt);
        }

        foreach ($tableDiff->changedIndexes as $index) {
            $this->alterTableChangedIndex($index, $fromTableName, $changeSetElt);
        }

        foreach ($tableDiff->changedForeignKeys as $foreignKey) {
            $this->alterTableChangedForeignKey($foreignKey, $fromTableName, $changeSetElt);
        }

        $this->alterTableRemovedColumns($tableDiff, $fromTableName, $changeSetElt);
        $this->alterTableRemovedIndexes($tableDiff, $fromTableName, $changeSetElt);
        $this->alterTableRemovedForeignKeys($tableDiff, $fromTableName, $changeSetElt);

        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param TableDiff $tableDiff
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     * @return QualifiedName
     */
    protected function alterTableRenameTable(TableDiff $tableDiff, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {
        if (is_string($tableDiff->newName) && $fromTableName->getName() !== $tableDiff->newName) {
            $renameTable = $this->document->createElement('renameTable');

            if ($fromTableName->getNamespaceName()) {
                $renameTable->setAttribute('schemaName', $fromTableName->getNamespaceName());
            }
            $renameTable->setAttribute('oldTableName', $fromTableName->getName());
            $renameTable->setAttribute('newTableName', $tableDiff->newName);

            $changeSetElt->appendChild($renameTable);

            $fromTableName = new QualifiedName($tableDiff->newName, $fromTableName->getNamespaceName());
        }
        return $fromTableName;
    }

    /**
     * @param TableDiff $tableDiff
     *
     * @param QualifiedName $fromTableName
     * @param IndexColumns $indexColumns
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableAddedColumns(TableDiff $tableDiff, QualifiedName $fromTableName, IndexColumns $indexColumns, \DOMElement $changeSetElt)
    {
        if ($tableDiff->addedColumns && count($tableDiff->addedColumns)) {
            $addColumnElt = $this->document->createElement('addColumn');

            if ($fromTableName->getNamespaceName()) {
                $addColumnElt->setAttribute('schemaName', $fromTableName->getNamespaceName());
            }
            $addColumnElt->setAttribute('tableName', $fromTableName->getName());

            foreach ($tableDiff->addedColumns as $column) {
                $columnElt = $this->document->createElement('column');

                $this->fillColumnAttributes($columnElt, $column, $indexColumns);

                $addColumnElt->appendChild($columnElt);
            }

            $changeSetElt->appendChild($addColumnElt);
        }
    }

    /**
     * @param TableDiff $tableDiff
     * @param QualifiedName $fromTableName
     * @param IndexColumns $indexColumns
     * @param \DOMElement $changeSetElt
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterTableAddedIndexes(TableDiff $tableDiff, QualifiedName $fromTableName, IndexColumns $indexColumns, \DOMElement $changeSetElt)
    {
        foreach ($tableDiff->addedIndexes as $index) {
            $createIndexElt = $this->document->createElement('createIndex');

            if ($fromTableName->getNamespaceName()) {
                $createIndexElt->setAttribute('schemaName', $fromTableName->getNamespaceName());
            }
            $createIndexElt->setAttribute('tableName', $fromTableName->getName());
            $createIndexElt->setAttribute('indexName', $index->getName());
            $createIndexElt->setAttribute('unique', $index->isUnique() ? 'true' : 'false');

            foreach ($index->getColumns() as $columnName) {
                $columnElt = $this->document->createElement('column');

                if ($tableDiff->fromTable->hasColumn($columnName)) {
                    $column = $tableDiff->fromTable->getColumn($columnName);
                } else {
                    $column = $tableDiff->addedColumns[$columnName];
                }

                $this->fillColumnAttributes($columnElt, $column, $indexColumns);

                $createIndexElt->appendChild($columnElt);
            }

            $changeSetElt->appendChild($createIndexElt);
        }
    }

    /**
     * @param TableDiff $tableDiff
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableAddedForeignKeys(TableDiff $tableDiff, \DOMElement $changeSetElt)
    {
        foreach ($tableDiff->addedForeignKeys as $foreignKey) {
            $addForeignKeyConstraintElt = $this->document->createElement('addForeignKeyConstraint');

            $this->fillForeignKeyAttributes($addForeignKeyConstraintElt, $foreignKey, $tableDiff->fromTable);

            $changeSetElt->appendChild($addForeignKeyConstraintElt);
        }
    }

    /**
     * @param TableDiff $tableDiff
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableRenamedColumns(TableDiff $tableDiff, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {
        foreach ($tableDiff->renamedColumns as $oldName => $column) {
            $renameColumnElt = $this->document->createElement('renameColumn');

            $columnName = QualifiedName::fromAsset($column);

            if ($fromTableName->getNamespaceName()) {
                $renameColumnElt->setAttribute('schemaName', $fromTableName->getNamespaceName());
            }
            $renameColumnElt->setAttribute('tableName', $fromTableName->getName());
            $renameColumnElt->setAttribute('oldColumnName', $oldName);
            $renameColumnElt->setAttribute('newColumnName', $columnName->getName());

            $changeSetElt->appendChild($renameColumnElt);
        }
    }

    /**
     * @param TableDiff $tableDiff
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableRenamedIndexes(TableDiff $tableDiff, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {
        foreach ($tableDiff->renamedIndexes as $oldName => $index) {
            $commentElt = $this->document->createComment(' renameIndex is not supported (index: ' . $oldName . ' => ' . $index->getName() . ')');
            $changeSetElt->appendChild($commentElt);
        }
    }

    /**
     * @param TableDiff $tableDiff
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableRemovedColumns(TableDiff $tableDiff, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {
        foreach ($tableDiff->removedColumns as $column) {
            $dropColumnElt = $this->document->createElement('dropColumn');

            $columnName = QualifiedName::fromAsset($column);

            if ($fromTableName->getNamespaceName()) {
                $dropColumnElt->setAttribute('schemaName', $fromTableName->getNamespaceName());
            }
            $dropColumnElt->setAttribute('tableName', $fromTableName->getName());
            $dropColumnElt->setAttribute('columnName', $columnName->getName());

            $changeSetElt->appendChild($dropColumnElt);
        }
    }

    /**
     * @param TableDiff $tableDiff
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableRemovedIndexes(TableDiff $tableDiff, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {
        foreach ($tableDiff->removedIndexes as $index) {
            $dropIndexElt = $this->document->createElement('dropIndex');

            $indexName = QualifiedName::fromAsset($index);

            if ($fromTableName->getNamespaceName()) {
                $dropIndexElt->setAttribute('schemaName', $fromTableName->getNamespaceName());
            }
            $dropIndexElt->setAttribute('tableName', $fromTableName->getName());
            $dropIndexElt->setAttribute('indexName', $indexName->getName());

            $changeSetElt->appendChild($dropIndexElt);
        }
    }

    /**
     * @param TableDiff $tableDiff
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableRemovedForeignKeys(TableDiff $tableDiff, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {
        foreach ($tableDiff->removedForeignKeys as $foreignKey) {
            $dropForeignKeyConstraintElt = $this->document->createElement('dropForeignKeyConstraint');

            $foreignKeyName = QualifiedName::fromAsset($foreignKey);

            if ($fromTableName->getNamespaceName()) {
                $dropForeignKeyConstraintElt->setAttribute('baseTableSchemaName', $fromTableName->getNamespaceName());
            }
            $dropForeignKeyConstraintElt->setAttribute('baseTableName', $fromTableName->getName());
            $dropForeignKeyConstraintElt->setAttribute('constraintName', $foreignKeyName->getName());

            $changeSetElt->appendChild($dropForeignKeyConstraintElt);
        }
    }

    /**
     * @param ColumnDiff $columnDiff
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     */
    private function alterTableChangedColumn(ColumnDiff $columnDiff, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {

        $oldColunmName = QualifiedName::fromAsset($columnDiff->getOldColumnName());
        $columnName = QualifiedName::fromAsset($columnDiff->column);
        if ($oldColunmName->getName() !== $columnName->getName()) {
            $renameColumnElt = $this->document->createElement('renameColumn');

            if ($fromTableName->getNamespaceName()) {
                $renameColumnElt->setAttribute('schemaName', $fromTableName->getNamespaceName());
            }
            $renameColumnElt->setAttribute('tableName', $fromTableName->getName());
            $renameColumnElt->setAttribute('oldColumnName', $oldColunmName->getName());
            $renameColumnElt->setAttribute('newColumnName', $columnName->getName());

            $changeSetElt->appendChild($renameColumnElt);
        }

        $properties = $columnDiff->changedProperties;

        if ($properties && count($properties)) {
            $typeIndex = array_search('type', $properties);
            if ($typeIndex !== FALSE) {
                $properties = array_splice($properties, 1, $typeIndex);

                $modifyDataTypeElt = $this->document->createElement('modifyDataType');

                if ($fromTableName->getNamespaceName()) {
                    $modifyDataTypeElt->setAttribute('schemaName', $fromTableName->getNamespaceName());
                }
                $modifyDataTypeElt->setAttribute('tableName', $fromTableName->getName());
                $modifyDataTypeElt->setAttribute('columnName', $columnName->getName());
                $modifyDataTypeElt->setAttribute('newDataType', $this->getColumnType($columnDiff->column));

                $changeSetElt->appendChild($modifyDataTypeElt);
            }
        }

        if ($properties && count($properties)) {
            $commentElt = $this->document->createComment(' Some column property changes are not supported (column: ' . $columnDiff->getOldColumnName()->getName() . ' for properties [' . implode(', ', $properties) . '])');
            $changeSetElt->appendChild($commentElt);
        }
    }

    /**
     * @param Index $index
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableChangedIndex(Index $index, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {
        $commentElt = $this->document->createComment(' index changes are not supported (index: ' . $index->getName() . ')');
        $changeSetElt->appendChild($commentElt);
    }


    /**
     * @param ForeignKeyConstraint $foreignKey
     * @param QualifiedName $fromTableName
     * @param \DOMElement $changeSetElt
     */
    protected function alterTableChangedForeignKey(ForeignKeyConstraint $foreignKey, QualifiedName $fromTableName, \DOMElement $changeSetElt)
    {
        $commentElt = $this->document->createComment(' foreign key changes are not supported (foreignKey: ' . $foreignKey->getName() . ')');
        $changeSetElt->appendChild($commentElt);
    }

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function started($em)
    {
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->root = $this->document->createElement('databaseChangeLog');

        /*

        $this->root->setAttribute('xmlns', 'http://www.liquibase.org/xml/ns/dbchangelog');
        $this->root->setAttribute('xmlns:ext', 'http://www.liquibase.org/xml/ns/dbchangelog-ext');
        $this->root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->root->setAttribute('xsi:schemaLocation', 'http://www.liquibase.org/xml/ns/dbchangelog-ext http://www.liquibase.org/xml/ns/dbchangelog/dbchangelog-ext.xsd http://www.liquibase.org/xml/ns/dbchangelog http://www.liquibase.org/xml/ns/dbchangelog/dbchangelog-3.5.xsd');
    */
    }

    /**
     * @return void
     */
    public function terminated()
    {
        $this->document->appendChild($this->root);
    }
}