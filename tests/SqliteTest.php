<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase;


class SqliteTest extends AbstractDatabaseTest
{
    protected function getConnectionParameters()
    {
        return [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
    }

    protected function getEntitiesPath()
    {
        return 'Entity';
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCreateDefaultOptions()
    {
        $output = $this->getCreateChangelog();

        $expected = <<<'EOT'
<?xml version="1.0"?>
<databaseChangeLog>
  <changeSet author="doctrine-migrations-liquibase" id="create-schema-public">
    <sql>CREATE SCHEMA `public`</sql>
  </changeSet>
  <changeSet author="doctrine-migrations-liquibase" id="create-table-Bar">
    <createTable tableName="Bar">
      <column name="id" type="string(255)">
        <constraints primaryKey="true" nullable="false"/>
      </column>
    </createTable>
  </changeSet>
  <changeSet author="doctrine-migrations-liquibase" id="create-table-Foo">
    <createTable tableName="Foo">
      <column name="id" type="int">
        <constraints primaryKey="true" nullable="false"/>
      </column>
    </createTable>
  </changeSet>
  <changeSet author="doctrine-migrations-liquibase" id="create-table-IndexColumns">
    <createTable tableName="IndexColumns">
      <column name="id" type="int">
        <constraints primaryKey="true" nullable="false"/>
      </column>
      <column name="date" type="date"/>
      <column name="libelle" type="string(255)"/>
      <column name="commentaire" type="string(255)"/>
    </createTable>
    <createIndex tableName="IndexColumns" indexName="IDX_9BEF3AA1AA9E377A">
      <column name="date"/>
    </createIndex>
    <createIndex tableName="IndexColumns" indexName="IDX_9BEF3AA1A4D60759">
      <column name="libelle"/>
    </createIndex>
    <createIndex tableName="IndexColumns" indexName="IDX_9BEF3AA167F068BC">
      <column name="commentaire"/>
    </createIndex>
  </changeSet>
</databaseChangeLog>

EOT;

        self::assertEquals($expected, $output);
    }
}