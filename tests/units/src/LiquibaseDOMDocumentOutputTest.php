<?php

declare(strict_types=1);

namespace Toilal\Doctrine\Migrations\Liquibase;

use PHPUnit\Framework\TestCase;
use PHPUnit\Xpath\Assert as XPathAssert;
use Prophecy\Prophecy\ObjectProphecy;
use DOMDocument;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types as DoctrineType;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\ColumnDiff;

/**
 * @coversDefaultClass Toilal\Doctrine\Migrations\Liquibase\LiquibaseDOMDocumentOutput
 */
final class LiquibaseDOMDocumentOutputTest extends TestCase
{

    use XPathAssert;

    private LiquibaseDOMDocumentOutput $output;
    private LiquibaseOutputOptions $options;
    private DOMDocument $document;
    private ObjectProphecy $em;
    private ObjectProphecy $connection;
    private ObjectProphecy $platform;

    protected function setUp(): void
    {
        $this->options = new LiquibaseOutputOptions();
        $this->options->setChangeSetUniqueId(false);
        $this->options->setChangeSetAuthor('phpunit');

        $this->document = new DOMDocument();

        $this->output = new LiquibaseDOMDocumentOutput($this->options, $this->document);

        $this->platform = $this->prophesize(AbstractPlatform::class);

        $this->connection = $this->prophesize(Connection::class);
        $this->connection->getDatabasePlatform()
            ->willReturn($this->platform->reveal());

        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->em->getConnection()->willReturn($this->connection->reveal());

        $this->output->started($this->em->reveal());
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::getOptions
     * @covers ::getDocument
     * @covers ::getResult
     */
    public function defaultConstructorOptions(): void
    {
        $output = new LiquibaseDOMDocumentOutput();
        $this->assertInstanceOf(LiquibaseOutputOptions::class, $output->getOptions());
        $this->assertInstanceOf(DOMDocument::class, $output->getDocument());
        $this->assertInstanceOf(DOMDocument::class, $output->getResult());
    }

    /**
     * @test
     * @covers ::createSchema
     * @covers ::createChangeSet
     */
    public function createSchema(): void
    {
        $this->platform->getCreateSchemaSQL('myns')
            ->willReturn('CREATE MYSCHEMA myns');

        $this->output->createSchema('myns');
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog/changeSet[@id="create-schema-myns"][@author="phpunit"]/sql',
            $this->output->getDocument()
        );
        $this->assertXpathEquals(
            'CREATE MYSCHEMA myns',
            '/databaseChangeLog/changeSet[@id="create-schema-myns"][@author="phpunit"]/sql/text()',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::createSchema
     * @covers ::createChangeSet
     */
    public function createSchemaWithExceptionThrown(): void
    {
        $this->platform->getCreateSchemaSQL('myns')
            ->willThrow(new DBALException('test'));

        $this->output->createSchema('myns');
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog/changeSet[@id="create-schema-myns"][@author="phpunit"]/sql',
            $this->output->getDocument()
        );
        $this->assertXpathEquals(
            'CREATE SCHEMA `myns`',
            '/databaseChangeLog/changeSet[@id="create-schema-myns"][@author="phpunit"]/sql/text()',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::dropForeignKey
     * @covers ::createChangeSet
     */
    public function dropForeignKeyConstraint(): void
    {
        $orphanedForeignKey = $this->prophesize(ForeignKeyConstraint::class);
        $orphanedForeignKey->getName()->willReturn('namespace.test');
        $orphanedForeignKey->getNamespaceName()->willReturn('namespace');
        $orphanedForeignKey->getShortestName('namespace')->willReturn('test');

        $localTable = $this->prophesize(Table::class);
        $localTable->getName()->willReturn('namespace.test2');
        $localTable->getNamespaceName()->willReturn('namespace');
        $localTable->getShortestName('namespace')->willReturn('test2');

        $this->output->dropForeignKey($orphanedForeignKey->reveal(), $localTable->reveal());
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="drop-foreign-key-namespace-test"][@author="phpunit"]'
            . '/dropForeignKeyConstraint[@baseTableSchemaName="namespace"][@baseTableName="test2"][@constraintName="test"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterSequence
     */
    public function alterSequence(): void
    {
        $sequence = $this->prophesize(Sequence::class);
        $sequence->getName()->willReturn('myseq');

        $this->output->alterSequence($sequence->reveal());
        $this->output->terminated();

        $this->assertStringContainsString(
            '<!-- alterSequence is not supported (sequence: myseq)-->',
            $this->output->getDocument()->saveXML()
        );
    }

    /**
     * @test
     * @covers ::dropSequence
     */
    public function dropSequence(): void
    {
        $sequence = $this->prophesize(Sequence::class);
        $sequence->getName()->willReturn('myseq');
        $sequence->getShortestName('namespace')->willReturn('myseq');
        $sequence->getNamespaceName()->willReturn('namespace');

        $this->output->dropSequence($sequence->reveal());
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="drop-sequence-myseq"][@author="phpunit"]'
            . '/dropSequence[@schemaName="namespace"][@sequenceName="myseq"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::createSequence
     */
    public function createSequence(): void
    {
        $sequence = $this->prophesize(Sequence::class);
        $sequence->getName()->willReturn('myseq');
        $sequence->getShortestName('namespace')->willReturn('myseq');
        $sequence->getNamespaceName()->willReturn('namespace');
        $sequence->getInitialValue()->willReturn('test');

        $this->output->createSequence($sequence->reveal());
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="create-sequence-myseq"][@author="phpunit"]'
            . '/createSequence[@schemaName="namespace"][@sequenceName="myseq"][@startValue="test"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::createTable
     * @covers ::fillColumnAttributes
     * @covers ::getColumnType
     */
    public function createTable(): void
    {
        $table = $this->getTestTable();

        $column1 = new Column('column1', new DoctrineType\StringType());
        $column1->setComment('mycomment');
        $column1->setDefault('somedefault');
        $column1->setNotnull(true);
        $column1->setLength(10);

        $column2 = new Column('column2', new DoctrineType\IntegerType());
        $column3 = new Column('column3', new DoctrineType\FloatType());

        $column4 = new Column('column4', new DoctrineType\IntegerType());
        $column4->setColumnDefinition('bigint');

        $table->getColumns()->willReturn([$column1, $column2, $column3, $column4]);
        $table->getIndexes()
            ->shouldBeCalled()
            ->willReturn([]);

        $primaryIndex1 = new Index('primary1', ['column1', 'column3'], false, true);

        $uniqueIndex1 = new Index('unique1', ['column2'], true, false);

        $table->getIndexes()
            ->shouldBeCalled()
            ->willReturn([
                $primaryIndex1,
                $uniqueIndex1
        ]);

        $this->output->createTable($table->reveal());
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="create-table-mytable"][@author="phpunit"]'
            . '/createTable[@tableName="mytable"][@schemaName="namespace"]',
            $this->output->getDocument()
        );

        $this->assertXpathCount(
            4,
            '/databaseChangeLog/changeSet/createTable/column',
            $this->output->getDocument()
        );

        $this->assertXpathMatch(
            '//createTable/column[1]'
            . '[@name="column1"]'
            . '[@type="varchar(10)"]'
            . '[@remarks="mycomment"]'
            . '[@defaultValue="somedefault"]',
            $this->output->getDocument()
        );
        $this->assertXpathMatch(
            '//createTable/column[1]/constraints[@nullable="false"][@primaryKey="true"]',
            $this->output->getDocument()
        );

        $this->assertXpathMatch(
            '//createTable/column[2]'
            . '[@name="column2"]'
            . '[@type="int"]',
            $this->output->getDocument()
        );
        $this->assertXpathMatch(
            '//createTable/column[2]/constraints[@unique="true"][@uniqueConstraintName="unique1"]',
            $this->output->getDocument()
        );

        $this->assertXpathMatch(
            '//createTable/column[3]'
            . '[@name="column3"]'
            . '[@type="double"]',
            $this->output->getDocument()
        );

        $this->assertXpathMatch(
            '//createTable/column[4]'
            . '[@name="column4"]'
            . '[@type="bigint"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::createTable
     * @covers ::fillColumnAttributes
     * @covers ::getColumnType
     */
    public function createTableWithPlatFormTypes(): void
    {
        $table = $this->getTestTable();

        $columnType1 = new DoctrineType\StringType();
        $column1     = new Column('column1', $columnType1);
        $column1->setLength(10);

        $table->getColumns()->willReturn([$column1]);
        $table->getIndexes()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->platform->getVarcharTypeDeclarationSQL([
                'name'             => 'column1',
                'type'             => $columnType1,
                'default'          => null,
                'notnull'          => true,
                'length'           => 10,
                'precision'        => 10,
                'scale'            => 0,
                'fixed'            => false,
                'unsigned'         => false,
                'autoincrement'    => false,
                'columnDefinition' => null,
                'comment'          => null
            ])
            ->shouldBeCalled()
            ->willReturn('MYVARCHAR(10)');

        $this->options->setUsePlatformTypes(true);
        $this->output->createTable($table->reveal());
        $this->output->terminated();

        $this->assertXpathMatch(
            '//createTable/column[1]'
            . '[@name="column1"]'
            . '[@type="MYVARCHAR(10)"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::createTable
     */
    public function createTableWithIndexes(): void
    {
        $table = $this->getTestTable();

        $otherIndex1 = new Index('other1', ['test1'], false, false);
        $otherIndex2 = new Index('other2', ['test2', 'test3'], true, false);

        $table->getColumns()->willReturn([]);
        $table->getIndexes()
            ->willReturn([$otherIndex1, $otherIndex2]);

        $this->output->createTable($table->reveal());
        $this->output->terminated();

        $this->assertXpathCount(
            2,
            '/databaseChangeLog/changeSet/createIndex',
            $this->output->getDocument()
        );

        $this->assertXpathMatch(
            '//createIndex[1][@schemaName="namespace"][@tableName="mytable"][@indexName="other1"]',
            $this->output->getDocument()
        );

        $this->assertXpathMatch(
            '//createIndex[1]/column[@name="test1"]',
            $this->output->getDocument()
        );

        $this->assertXpathMatch(
            '//createIndex[2][@schemaName="namespace"][@tableName="mytable"][@indexName="other2"][@unique="true"]',
            $this->output->getDocument()
        );

        $this->assertXpathMatch(
            '//createIndex[2]/column[@name="test2"]',
            $this->output->getDocument()
        );
        $this->assertXpathMatch(
            '//createIndex[2]/column[@name="test3"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::createForeignKey
     * @covers ::fillForeignKeyAttributes
     */
    public function createForeignKey(): void
    {
        $foreignKey = $this->prophesize(ForeignKeyConstraint::class);
        $foreignKey->getName()->willReturn('namespace.test');
        $foreignKey->getNamespaceName()->willReturn('namespace');
        $foreignKey->getShortestName('namespace')->willReturn('test');
        $foreignKey->getLocalColumns()->willReturn(['test1', 'test2']);
        $foreignKey->getForeignColumns()->willReturn(['test3', 'test4']);
        $foreignKey->getForeignTableName()->willReturn('namespace.othertable');

        $table = $this->getTestTable();

        $this->output->createForeignKey($foreignKey->reveal(), $table->reveal());
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="create-foreign-keys-mytable"][@author="phpunit"]'
            . '/addForeignKeyConstraint[@constraintName="namespace.test"]'
            . '[@baseTableSchemaName="namespace"]'
            . '[@baseTableName="mytable"]'
            . '[@baseColumnNames="test1,test2"]'
            . '[@referencedTableSchemaName="namespace"]'
            . '[@referencedTableName="othertable"]'
            . '[@referencedColumnNames="test3,test4"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::dropTable
     */
    public function dropTable(): void
    {
        $table = $this->getTestTable();

        $this->output->dropTable($table->reveal());
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="drop-table-mytable"][@author="phpunit"]'
            . '/dropTable[@schemaName="namespace"][@tableName="mytable"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableRenameTable
     */
    public function alterTableRenameTable(): void
    {
        $table = $this->getTestTable();

        $tableDiff = new TableDiff('testtable');

        $tableDiff->fromTable = $table->reveal();
        $tableDiff->newName   = 'testtable';

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/renameTable[@schemaName="namespace"]'
            . '[@oldTableName="mytable"]'
            . '[@newTableName="testtable"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableAddedColumns
     */
    public function alterTableAddedColumns(): void
    {
        $table = $this->getTestTable();

        $columnType1 = new DoctrineType\StringType();
        $column1     = new Column('column1', $columnType1);
        $column1->setLength(10);

        $tableDiff               = new TableDiff('testtable');
        $tableDiff->addedColumns = [$column1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/addColumn[@schemaName="namespace"][@tableName="mytable"]'
            . '/column[@name="column1"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableAddedIndexes
     */
    public function alterTableAddedIndex(): void
    {
        $index1 = new Index('myindex1', ['column1', 'column2'], true);
        $index2 = new Index('myindex2', ['column3', 'column4'], true);

        $columnType1 = new DoctrineType\StringType();
        $column1     = new Column('column1', $columnType1);
        $column1->setLength(10);

        $columnType2 = new DoctrineType\IntegerType();
        $column2     = new Column('column2', $columnType2);

        $columnType3 = new DoctrineType\StringType();
        $column3     = new Column('column3', $columnType3);

        $columnType4 = new DoctrineType\StringType();
        $column4     = new Column('column4', $columnType4);

        $table = $this->getTestTable();
        $table->hasColumn('column1')->willReturn(false);
        $table->hasColumn('column2')->willReturn(false);
        $table->hasColumn('column3')->willReturn(true);
        $table->hasColumn('column4')->willReturn(true);

        $table->getColumn('column3')->willReturn($column3);
        $table->getColumn('column4')->willReturn($column4);

        $tableDiff = new TableDiff('testtable');

        $tableDiff->addedColumns = ['column1' => $column1, 'column2' => $column2];
        $tableDiff->addedIndexes = ['myindex1' => $index1, 'index2' => $index2];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/createIndex[@schemaName="namespace"][@tableName="mytable"][@indexName="myindex1"]',
            $this->output->getDocument()
        );

        $this->assertXpathCount(2, '//createIndex[@indexName="myindex1"]/column', $this->output->getDocument());
        $this->assertXpathMatch('//createIndex[@indexName="myindex1"]/column[1][@name="column1"]', $this->output->getDocument());
        $this->assertXpathMatch('//createIndex[@indexName="myindex1"]/column[2][@name="column2"]', $this->output->getDocument());

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/createIndex[@schemaName="namespace"][@tableName="mytable"][@indexName="myindex2"]',
            $this->output->getDocument()
        );

        $this->assertXpathCount(2, '//createIndex[@indexName="myindex2"]/column', $this->output->getDocument());
        $this->assertXpathMatch('//createIndex[@indexName="myindex2"]/column[1][@name="column3"]', $this->output->getDocument());
        $this->assertXpathMatch('//createIndex[@indexName="myindex2"]/column[2][@name="column4"]', $this->output->getDocument());
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableAddedForeignKeys
     */
    public function alterTableAddForeignKeys(): void
    {
        $table = $this->getTestTable();

        $foreignKey = $this->prophesize(ForeignKeyConstraint::class);
        $foreignKey->getName()->willReturn('namespace.test');
        $foreignKey->getNamespaceName()->willReturn('namespace');
        $foreignKey->getShortestName('namespace')->willReturn('test');
        $foreignKey->getLocalColumns()->willReturn(['test1', 'test2']);
        $foreignKey->getForeignColumns()->willReturn(['test3', 'test4']);
        $foreignKey->getForeignTableName()->willReturn('namespace.othertable');

        $tableDiff = new TableDiff('testtable');

        $tableDiff->fromTable = $table->reveal();

        $tableDiff->addedForeignKeys = [$foreignKey->reveal()];

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/addForeignKeyConstraint'
            . '[@baseTableSchemaName="namespace"]'
            . '[@baseTableName="mytable"]'
            . '[@baseColumnNames="test1,test2"]'
            . '[@referencedTableSchemaName="namespace"]'
            . '[@referencedTableName="othertable"]'
            . '[@referencedColumnNames="test3,test4"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableRenamedColumns
     */
    public function alterTableRenameColumns(): void
    {
        $table = $this->getTestTable();

        $columnType1 = new DoctrineType\StringType();
        $column1     = new Column('newcolumn', $columnType1);
        $column1->setLength(10);

        $tableDiff                 = new TableDiff('testtable');
        $tableDiff->renamedColumns = ['oldcolumn' => $column1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/renameColumn[@schemaName="namespace"]'
            . '[@tableName="mytable"]'
            . '[@oldColumnName="oldcolumn"]'
            . '[@newColumnName="newcolumn"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableRenamedIndexes
     */
    public function alterTableRenameIndexes(): void
    {
        $table = $this->getTestTable();

        $index1 = new Index('index1', ['test1', 'test2']);

        $tableDiff                 = new TableDiff('testtable');
        $tableDiff->renamedIndexes = ['oldindex' => $index1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertStringContainsString(
            '<!-- renameIndex is not supported (index: oldindex => index1)-->',
            $this->output->getDocument()->saveXML()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableRemovedColumns
     */
    public function alterTableRemovedColumns(): void
    {
        $table = $this->getTestTable();

        $columnType1 = new DoctrineType\StringType();
        $column1     = new Column('removedcolumn', $columnType1);
        $column1->setLength(10);

        $tableDiff                 = new TableDiff('testtable');
        $tableDiff->removedColumns = ['removedcolumn' => $column1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/dropColumn[@schemaName="namespace"]'
            . '[@tableName="mytable"]'
            . '[@columnName="removedcolumn"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableRemovedIndexes
     */
    public function alterTableRemovedIndexes(): void
    {
        $table = $this->getTestTable();

        $index1 = new Index('removeindex', ['test1', 'test2']);

        $tableDiff                 = new TableDiff('testtable');
        $tableDiff->removedIndexes = ['removeindex' => $index1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/dropIndex[@schemaName="namespace"]'
            . '[@tableName="mytable"]'
            . '[@indexName="removeindex"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableRemovedForeignKeys
     */
    public function alterTableRemovedForeignKeys(): void
    {
        $table = $this->getTestTable();

        $foreignKey = $this->prophesize(ForeignKeyConstraint::class);
        $foreignKey->getName()->willReturn('namespace.test');
        $foreignKey->getNamespaceName()->willReturn('namespace');
        $foreignKey->getShortestName('namespace')->willReturn('test');
        $foreignKey->getLocalColumns()->willReturn(['test1', 'test2']);
        $foreignKey->getForeignColumns()->willReturn(['test3', 'test4']);
        $foreignKey->getForeignTableName()->willReturn('namespace.othertable');

        $tableDiff = new TableDiff('testtable');

        $tableDiff->fromTable = $table->reveal();

        $tableDiff->removedForeignKeys = [$foreignKey->reveal()];

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/dropForeignKeyConstraint'
            . '[@baseTableSchemaName="namespace"]'
            . '[@baseTableName="mytable"]'
            . '[@constraintName="test"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableRemovedForeignKeys
     */
    public function alterTableRemovedForeignKeysWhereForeignKeyIsString(): void
    {
        $table = $this->getTestTable();

        $tableDiff = new TableDiff('testtable');

        $tableDiff->fromTable = $table->reveal();

        $tableDiff->removedForeignKeys = ['test'];

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/dropForeignKeyConstraint'
            . '[@baseTableSchemaName="namespace"]'
            . '[@baseTableName="mytable"]'
            . '[@constraintName="test"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableChangedColumn
     */
    public function alterTableChangedColumnsRenamed(): void
    {
        $table = $this->getTestTable();

        $columnType1 = new DoctrineType\StringType();
        $column1     = new Column('changed', $columnType1);
        $column1->setLength(10);

        $columnDiff1 = new ColumnDiff('oldname', $column1);

        $tableDiff                 = new TableDiff('testtable');
        $tableDiff->changedColumns = ['changed' => $columnDiff1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/renameColumn[@schemaName="namespace"]'
            . '[@tableName="mytable"]'
            . '[@oldColumnName="oldname"]'
            . '[@newColumnName="changed"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableChangedColumn
     */
    public function alterTableChangedColumnsChangedType(): void
    {
        $table = $this->getTestTable();

        $columnType1 = new DoctrineType\StringType();
        $column1     = new Column('notchangedname', $columnType1);
        $column1->setLength(10);

        $columnDiff1 = new ColumnDiff('notchangedname', $column1);

        $columnDiff1->changedProperties = ['type'];

        $tableDiff = new TableDiff('testtable');

        $tableDiff->changedColumns = ['notchangedname' => $columnDiff1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertXpathMatch(
            '/databaseChangeLog'
            . '/changeSet[@id="alter-table-mytable"][@author="phpunit"]'
            . '/modifyDataType[@schemaName="namespace"]'
            . '[@tableName="mytable"]'
            . '[@columnName="notchangedname"]'
            . '[@newDataType="varchar(10)"]',
            $this->output->getDocument()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableChangedColumn
     */
    public function alterTableChangedColumnsOtherProperties(): void
    {
        $table = $this->getTestTable();

        $columnType1 = new DoctrineType\StringType();
        $column1     = new Column('notchangedname', $columnType1);
        $column1->setLength(10);

        $columnDiff1 = new ColumnDiff('notchangedname', $column1);

        $columnDiff1->changedProperties = ['someotherproperty'];

        $tableDiff = new TableDiff('testtable');

        $tableDiff->changedColumns = ['notchangedname' => $columnDiff1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertStringContainsString(
            '<!-- Some column property changes are not supported (column: notchangedname for properties [someotherproperty])-->',
            $this->output->getDocument()->saveXML()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableChangedIndex
     */
    public function alterTableChangedIndexes(): void
    {
        $table = $this->getTestTable();

        $tableDiff = new TableDiff('testtable');

        $index1 = new Index('changeindex', ['test1', 'test2']);

        $tableDiff->changedIndexes = [$index1];

        $tableDiff->fromTable = $table->reveal();

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertStringContainsString(
            '<!-- index changes are not supported (index: changeindex)-->',
            $this->output->getDocument()->saveXML()
        );
    }

    /**
     * @test
     * @covers ::alterTable
     * @covers ::alterTableChangedForeignKey
     */
    public function alterTableChangedForeignKey(): void
    {
        $table = $this->getTestTable();

        $foreignKey = $this->prophesize(ForeignKeyConstraint::class);
        $foreignKey->getName()->willReturn('namespace.test');
        $foreignKey->getNamespaceName()->willReturn('namespace');
        $foreignKey->getShortestName('namespace')->willReturn('test');
        $foreignKey->getLocalColumns()->willReturn(['test1', 'test2']);
        $foreignKey->getForeignColumns()->willReturn(['test3', 'test4']);
        $foreignKey->getForeignTableName()->willReturn('namespace.othertable');

        $tableDiff = new TableDiff('testtable');

        $tableDiff->fromTable          = $table->reveal();
        $tableDiff->changedForeignKeys = [$foreignKey->reveal()];

        $this->output->alterTable($tableDiff);
        $this->output->terminated();

        $this->assertStringContainsString(
            '<!-- foreign key changes are not supported (foreignKey: namespace.test)-->',
            $this->output->getDocument()->saveXML()
        );
    }

    /**
     * @test
     * @covers ::started
     * @covers ::terminated
     */
    public function terminated(): void
    {
        $this->output->terminated();
        $document = $this->output->getDocument();
        $this->assertXpathMatch('/databaseChangeLog', $document);
    }

    private function getTestTable(): ObjectProphecy
    {
        $table = $this->prophesize(Table::class);
        $table->getName()->willReturn('mytable');
        $table->getShortestName('namespace')->willReturn('mytable');
        $table->getNamespaceName()->willReturn('namespace');
        $table->getIndexes()->willReturn([]);
        return $table;
    }

}
