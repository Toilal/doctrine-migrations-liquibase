<?php

declare(strict_types=1);

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
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMElement;

class LiquibaseDOMDocumentOutput implements LiquibaseOutput
{

    private DOMDocument $document;
    private LiquibaseOutputOptions $options;
    private AbstractPlatform $platform;
    private DOMElement $root;

    /**
     * LiquibaseDOMDocumentOuput constructor.
     */
    public function __construct(?LiquibaseOutputOptions $options = null, ?DOMDocument $document = null)
    {
        if (null === $options) {
            $options = new LiquibaseOutputOptions();
        }

        $this->options = $options;

        if (null === $document) {
            $document                     = new DOMDocument();
            $document->preserveWhiteSpace = false;
            $document->formatOutput       = true;
            $this->document               = $document;
        } else {
            $this->document = $document;
        }

        $this->root     = $this->document->createElement('databaseChangeLog');
        $this->platform = new MySqlPlatform();
    }

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }

    public function getOptions(): LiquibaseOutputOptions
    {
        return $this->options;
    }

    public function getResult()
    {
        return $this->document;
    }

    protected function createChangeSet(string $id): DOMElement
    {
        $changeSet   = $this->document->createElement('changeSet');
        $changeSet->setAttribute('author', $this->options->getChangeSetAuthor());
        $sanitizedId = preg_replace('/[_\.]/', '-', $id);
        assert($sanitizedId !== null);
        $changeSet->setAttribute('id', $this->options->isChangeSetUniqueId() ? $sanitizedId . '-' . uniqid() : $sanitizedId);
        $this->root->appendChild($changeSet);
        return $changeSet;
    }

    public function createSchema(string $newNamespace): void
    {
        $changeSetElt = $this->createChangeSet('create-schema-' . $newNamespace);

        $sql = '';
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

    public function dropForeignKey(ForeignKeyConstraint $orphanedForeignKey, Table $localTable): void
    {
        $changeSetElt = $this->createChangeSet('drop-foreign-key-' . $orphanedForeignKey->getName());

        $tableName      = QualifiedName::fromAsset($localTable);
        $foreignKeyName = QualifiedName::fromAsset($orphanedForeignKey);

        $dropForeignKeyElement = $this->document->createElement('dropForeignKeyConstraint');

        $namespaceName = $tableName->getNamespaceName();
        if (null !== $namespaceName) {
            $dropForeignKeyElement->setAttribute('baseTableSchemaName', $namespaceName);
        }

        $dropForeignKeyElement->setAttribute('baseTableName', $tableName->getName());
        $dropForeignKeyElement->setAttribute('constraintName', $foreignKeyName->getName());

        $changeSetElt->appendChild($dropForeignKeyElement);
        $this->root->appendChild($changeSetElt);
    }

    public function alterSequence(Sequence $sequence): void
    {
        $commentElt = $this->document->createComment(' alterSequence is not supported (sequence: ' . $sequence->getName() . ')');
        $this->root->appendChild($commentElt);
    }

    /**
     * @param Sequence $sequence
     *
     * @return void
     */
    public function dropSequence(Sequence $sequence): void
    {
        $changeSetElt = $this->createChangeSet('drop-sequence-' . $sequence->getName());

        $sequenceName    = QualifiedName::fromAsset($sequence);
        $dropSequenceElt = $this->document->createElement('dropSequence');

        $namespaceName = $sequenceName->getNamespaceName();
        if (null !== $namespaceName) {
            $dropSequenceElt->setAttribute('schemaName', $namespaceName);
        }

        $dropSequenceElt->setAttribute('sequenceName', $sequenceName->getName());

        $changeSetElt->appendChild($dropSequenceElt);
        $this->root->appendChild($changeSetElt);
    }

    public function createSequence(Sequence $sequence): void
    {
        $changeSetElt = $this->createChangeSet('create-sequence-' . $sequence->getName());

        $sequenceName      = QualifiedName::fromAsset($sequence);
        $createSequenceElt = $this->document->createElement('createSequence');

        $namespaceName = $sequenceName->getNamespaceName();
        if (null !== $namespaceName) {
            $createSequenceElt->setAttribute('schemaName', $namespaceName);
        }

        $createSequenceElt->setAttribute('sequenceName', $sequenceName->getName());
        $createSequenceElt->setAttribute('startValue', strval($sequence->getInitialValue()));

        $changeSetElt->appendChild($createSequenceElt);
        $this->root->appendChild($changeSetElt);
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function getColumnType(Column $column)
    {
        if ($column->getColumnDefinition()) {
            $sqlType = $column->getColumnDefinition();
            assert($sqlType !== null);
            return $sqlType;
        } else if ($this->options->isUsePlatformTypes()) {
            $sqlType = $column->getType()->getSQLDeclaration($column->toArray(), $this->platform);
            $sqlType = preg_replace('/\(.*?\)/', '', $sqlType);
            assert($sqlType !== null);
        } else {
            $sqlType = $column->getType()->getName();

            if ($sqlType === 'integer') {
                $sqlType = 'int';
            } elseif ($sqlType === 'float') {
                $sqlType = 'double';
            } elseif ($sqlType === 'string') {
                $sqlType = 'varchar';
            }
        }

        $length = $column->getLength();
        if ($length !== null) {
            $sqlType .= '(' . $length . ')';
        };

        return $sqlType;
    }

    protected function fillColumnAttributes(DOMElement $columnElt, Column $column, IndexColumns $indexColumns): void
    {
        $columnName = QualifiedName::fromAsset($column);
        $columnElt->setAttribute('name', $columnName->getName());
        $columnType = $this->getColumnType($column);
        $columnElt->setAttribute('type', $columnType);
        if ($remarks    = $column->getComment()) {
            $columnElt->setAttribute('remarks', $remarks);
        }
        if ($defaultValue = $column->getDefault()) {
            $columnElt->setAttribute('defaultValue', $defaultValue);
        }

        $primaryKey           = in_array($column->getName(), $indexColumns->getPrimaryKeyColumns());
        $unique               = false;
        $uniqueConstraintName = null;
        if (array_key_exists($column->getName(), $indexColumns->getUniqueColumns())) {
            $unique               = true;
            $uniqueConstraintName = $indexColumns->getUniqueColumns()[$column->getName()]->getName();
        }
        $nullable = !$column->getNotnull();

        if ($primaryKey || !$nullable || $unique) {
            $constraintsElt = $this->document->createElement('constraints');
            if ($primaryKey) {
                $constraintsElt->setAttribute('primaryKey', "true");
            }
            if (!$nullable) {
                $constraintsElt->setAttribute('nullable', "false");
            }
            if ($unique) {
                $constraintsElt->setAttribute('unique', "true");
            }
            if ($uniqueConstraintName) {
                $constraintsElt->setAttribute('uniqueConstraintName', $uniqueConstraintName);
            }

            $columnElt->appendChild($constraintsElt);
        }
    }

    public function createTable(Table $table): void
    {
        $changeSetElt = $this->createChangeSet('create-table-' . $table->getName());

        $createTableElt = $this->document->createElement('createTable');

        $tableName = QualifiedName::fromAsset($table);

        $namespaceName = $tableName->getNamespaceName();
        if (null !== $namespaceName) {
            $createTableElt->setAttribute('schemaName', $namespaceName);
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

            $namespaceName = $tableName->getNamespaceName();
            if (null !== $namespaceName) {
                $createIndexElt->setAttribute('schemaName', $namespaceName);
            }
            $createIndexElt->setAttribute('tableName', $tableName->getName());
            $createIndexElt->setAttribute('indexName', $index->getName());
            if ($index->isUnique()) {
                $createIndexElt->setAttribute('unique', 'true');
            }

            foreach ($index->getColumns() as $column) {
                $columnElt = $this->document->createElement('column');
                $columnElt->setAttribute('name', $column);
                $createIndexElt->appendChild($columnElt);
            }

            $changeSetElt->appendChild($createIndexElt);
        }

        $this->root->appendChild($changeSetElt);
    }

    protected function fillForeignKeyAttributes(DOMElement $addForeignKeyConstraintElt, ForeignKeyConstraint $foreignKey, Table $table): void
    {
        $addForeignKeyConstraintElt->setAttribute('constraintName', $foreignKey->getName());

        $tableName = QualifiedName::fromAsset($table);

        $namespaceName = $tableName->getNamespaceName();
        if (null !== $namespaceName) {
            $addForeignKeyConstraintElt->setAttribute('baseTableSchemaName', $namespaceName);
        }
        $addForeignKeyConstraintElt->setAttribute('baseTableName', $tableName->getName());
        $addForeignKeyConstraintElt->setAttribute('baseColumnNames', implode(',', $foreignKey->getLocalColumns()));

        $referencedTableName = QualifiedName::fromQualifiedName($foreignKey->getForeignTableName());

        $namespaceName = $referencedTableName->getNamespaceName();
        if (null !== $namespaceName) {
            $addForeignKeyConstraintElt->setAttribute('referencedTableSchemaName', $namespaceName);
        }
        $addForeignKeyConstraintElt->setAttribute('referencedTableName', $referencedTableName->getName());
        $addForeignKeyConstraintElt->setAttribute('referencedColumnNames', implode(',', $foreignKey->getForeignColumns()));
    }

    public function createForeignKey(ForeignKeyConstraint $foreignKey, Table $table): void
    {
        $changeSetElt               = $this->createChangeSet('create-foreign-keys-' . $table->getName());
        $addForeignKeyConstraintElt = $this->document->createElement('addForeignKeyConstraint');

        $this->fillForeignKeyAttributes($addForeignKeyConstraintElt, $foreignKey, $table);

        $changeSetElt->appendChild($addForeignKeyConstraintElt);
        $this->root->appendChild($changeSetElt);
    }

    public function dropTable(Table $table): void
    {
        $changeSetElt = $this->createChangeSet('drop-table-' . $table->getName());
        $dropTableElt = $this->document->createElement('dropTable');

        $tableName = QualifiedName::fromAsset($table);

        $namespaceName = $tableName->getNamespaceName();
        if (null !== $namespaceName) {
            $dropTableElt->setAttribute('schemaName', $namespaceName);
        }

        $dropTableElt->setAttribute('tableName', $tableName->getName());
        // Should we add cascadeConstraints attribute ?
        // $dropTableElt->setAttribute('cascadeConstraints', 'false');

        $changeSetElt->appendChild($dropTableElt);
        $this->root->appendChild($changeSetElt);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function alterTable(TableDiff $tableDiff): void
    {
        assert($tableDiff->fromTable !== null);
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

    protected function alterTableRenameTable(TableDiff $tableDiff, QualifiedName $fromTableName, \DOMElement $changeSetElt): QualifiedName
    {
        if (is_string($tableDiff->newName) && $fromTableName->getName() !== $tableDiff->newName) {
            $renameTable = $this->document->createElement('renameTable');

            if ($schemaName = $fromTableName->getNamespaceName()) {
                $renameTable->setAttribute('schemaName', $schemaName);
            }
            $renameTable->setAttribute('oldTableName', $fromTableName->getName());
            $renameTable->setAttribute('newTableName', $tableDiff->newName);

            $changeSetElt->appendChild($renameTable);

            $fromTableName = new QualifiedName($tableDiff->newName, $fromTableName->getNamespaceName());
        }
        return $fromTableName;
    }

    protected function alterTableAddedColumns(TableDiff $tableDiff, QualifiedName $fromTableName, IndexColumns $indexColumns, \DOMElement $changeSetElt): void
    {
        if ($tableDiff->addedColumns && count($tableDiff->addedColumns)) {
            $addColumnElt = $this->document->createElement('addColumn');

            if ($schemaName = $fromTableName->getNamespaceName()) {
                $addColumnElt->setAttribute('schemaName', $schemaName);
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
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterTableAddedIndexes(TableDiff $tableDiff, QualifiedName $fromTableName, IndexColumns $indexColumns, DOMElement $changeSetElt): void
    {
        assert($tableDiff->fromTable !== null);
        foreach ($tableDiff->addedIndexes as $index) {
            $createIndexElt = $this->document->createElement('createIndex');

            if ($schemaName = $fromTableName->getNamespaceName()) {
                $createIndexElt->setAttribute('schemaName', $schemaName);
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

    protected function alterTableAddedForeignKeys(TableDiff $tableDiff, DOMElement $changeSetElt): void
    {
        assert($tableDiff->fromTable !== null);
        foreach ($tableDiff->addedForeignKeys as $foreignKey) {
            $addForeignKeyConstraintElt = $this->document->createElement('addForeignKeyConstraint');

            $this->fillForeignKeyAttributes($addForeignKeyConstraintElt, $foreignKey, $tableDiff->fromTable);

            $changeSetElt->appendChild($addForeignKeyConstraintElt);
        }
    }

    protected function alterTableRenamedColumns(TableDiff $tableDiff, QualifiedName $fromTableName, DOMElement $changeSetElt): void
    {
        foreach ($tableDiff->renamedColumns as $oldName => $column) {
            $renameColumnElt = $this->document->createElement('renameColumn');

            $columnName = QualifiedName::fromAsset($column);

            if ($schemaName = $fromTableName->getNamespaceName()) {
                $renameColumnElt->setAttribute('schemaName', $schemaName);
            }
            $renameColumnElt->setAttribute('tableName', $fromTableName->getName());
            $renameColumnElt->setAttribute('oldColumnName', $oldName);
            $renameColumnElt->setAttribute('newColumnName', $columnName->getName());

            $changeSetElt->appendChild($renameColumnElt);
        }
    }

    protected function alterTableRenamedIndexes(TableDiff $tableDiff, QualifiedName $fromTableName, DOMElement $changeSetElt): void
    {
        foreach ($tableDiff->renamedIndexes as $oldName => $index) {
            $commentElt = $this->document->createComment(' renameIndex is not supported (index: ' . $oldName . ' => ' . $index->getName() . ')');
            $changeSetElt->appendChild($commentElt);
        }
    }

    protected function alterTableRemovedColumns(TableDiff $tableDiff, QualifiedName $fromTableName, DOMElement $changeSetElt): void
    {
        foreach ($tableDiff->removedColumns as $column) {
            $dropColumnElt = $this->document->createElement('dropColumn');

            $columnName = QualifiedName::fromAsset($column);

            if ($schemaName = $fromTableName->getNamespaceName()) {
                $dropColumnElt->setAttribute('schemaName', $schemaName);
            }
            $dropColumnElt->setAttribute('tableName', $fromTableName->getName());
            $dropColumnElt->setAttribute('columnName', $columnName->getName());

            $changeSetElt->appendChild($dropColumnElt);
        }
    }

    protected function alterTableRemovedIndexes(TableDiff $tableDiff, QualifiedName $fromTableName, DOMElement $changeSetElt): void
    {
        foreach ($tableDiff->removedIndexes as $index) {
            $dropIndexElt = $this->document->createElement('dropIndex');

            $indexName = QualifiedName::fromAsset($index);

            if ($schemaName = $fromTableName->getNamespaceName()) {
                $dropIndexElt->setAttribute('schemaName', $schemaName);
            }
            $dropIndexElt->setAttribute('tableName', $fromTableName->getName());
            $dropIndexElt->setAttribute('indexName', $indexName->getName());

            $changeSetElt->appendChild($dropIndexElt);
        }
    }

    protected function alterTableRemovedForeignKeys(TableDiff $tableDiff, QualifiedName $fromTableName, DOMElement $changeSetElt): void
    {
        foreach ($tableDiff->removedForeignKeys as $foreignKey) {
            $dropForeignKeyConstraintElt = $this->document->createElement('dropForeignKeyConstraint');

            if (is_string($foreignKey)) {
                $foreignKeyName = QualifiedName::fromQualifiedName($foreignKey);
            } else {
                $foreignKeyName = QualifiedName::fromAsset($foreignKey);
            }

            if ($baseTableSchemaName = $fromTableName->getNamespaceName()) {
                $dropForeignKeyConstraintElt->setAttribute('baseTableSchemaName', $baseTableSchemaName);
            }
            $dropForeignKeyConstraintElt->setAttribute('baseTableName', $fromTableName->getName());
            $dropForeignKeyConstraintElt->setAttribute('constraintName', $foreignKeyName->getName());

            $changeSetElt->appendChild($dropForeignKeyConstraintElt);
        }
    }

    private function alterTableChangedColumn(ColumnDiff $columnDiff, QualifiedName $fromTableName, DOMElement $changeSetElt): void
    {
        $oldColunmName = QualifiedName::fromAsset($columnDiff->getOldColumnName());
        $columnName    = QualifiedName::fromAsset($columnDiff->column);
        if ($oldColunmName->getName() !== $columnName->getName()) {
            $renameColumnElt = $this->document->createElement('renameColumn');

            if ($schemaName = $fromTableName->getNamespaceName()) {
                $renameColumnElt->setAttribute('schemaName', $schemaName);
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
                $properties = array_splice($properties, 1, intval($typeIndex));

                $modifyDataTypeElt = $this->document->createElement('modifyDataType');

                if ($schemaName = $fromTableName->getNamespaceName()) {
                    $modifyDataTypeElt->setAttribute('schemaName', $schemaName);
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

    protected function alterTableChangedIndex(Index $index, QualifiedName $fromTableName, DOMElement $changeSetElt): void
    {
        $commentElt = $this->document->createComment(' index changes are not supported (index: ' . $index->getName() . ')');
        $changeSetElt->appendChild($commentElt);
    }

    protected function alterTableChangedForeignKey(ForeignKeyConstraint $foreignKey, QualifiedName $fromTableName, DOMElement $changeSetElt): void
    {
        $commentElt = $this->document->createComment(' foreign key changes are not supported (foreignKey: ' . $foreignKey->getName() . ')');
        $changeSetElt->appendChild($commentElt);
    }

    public function started(EntityManagerInterface $em): void
    {
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->root     = $this->document->createElement('databaseChangeLog');

        /*

          $this->root->setAttribute('xmlns', 'http://www.liquibase.org/xml/ns/dbchangelog');
          $this->root->setAttribute('xmlns:ext', 'http://www.liquibase.org/xml/ns/dbchangelog-ext');
          $this->root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
          $this->root->setAttribute('xsi:schemaLocation', 'http://www.liquibase.org/xml/ns/dbchangelog-ext http://www.liquibase.org/xml/ns/dbchangelog/dbchangelog-ext.xsd http://www.liquibase.org/xml/ns/dbchangelog http://www.liquibase.org/xml/ns/dbchangelog/dbchangelog-3.5.xsd');
         */
    }

    public function terminated(): void
    {
        $this->document->appendChild($this->root);
    }

}
