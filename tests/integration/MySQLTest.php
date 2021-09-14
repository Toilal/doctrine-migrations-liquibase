<?php

declare(strict_types=1);

namespace Tests\Toilal\Doctrine\Migrations\Liquibase;

use Tests\Toilal\Doctrine\Migrations\Liquibase\Database\AbstractMySQLTest;
use Toilal\Doctrine\Migrations\Liquibase\LiquibaseOutputOptions;

/**
 * @group docker
 */
class MySQLTest extends Database\AbstractDatabaseTest
{

    public function getConnectionParameters(): array
    {
        return [
            'driver'        => 'pdo_mysql',
            'host'          => getenv('MYSQL_HOSTNAME'),
            'port'          => getenv('MYSQL_PORT'),
            'dbname'        => getenv('MYSQL_DATABASE'),
            'user'          => getenv('MYSQL_USER'),
            'password'      => getenv('MYSQL_PASSWORD'),
            'charset'       => 'utf8',
            'serverVersion' => getenv('MYSQL_VERSION'),
        ];
    }

    protected function getEntitiesPath(): string
    {
        return 'Entity';
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCreateWithDefaultOptions(): void
    {
        $options = new LiquibaseOutputOptions();
        $options->setChangeSetUniqueId(false);
        $output  = $this->changeLog($options);

        $expected = <<<'EOT'
<?xml version="1.0"?>
<databaseChangeLog>
  <changeSet author="doctrine-migrations-liquibase" id="create-schema-testdb">
    <sql>CREATE SCHEMA `testdb`</sql>
  </changeSet>
  <changeSet author="doctrine-migrations-liquibase" id="create-table-Bar">
    <createTable tableName="Bar">
      <column name="id" type="varchar(255)">
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
      <column name="libelle" type="varchar(255)"/>
      <column name="commentaire" type="varchar(500)"/>
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
  <changeSet author="doctrine-migrations-liquibase" id="create-table-ReservedKeywords">
    <createTable tableName="ReservedKeywords">
      <column name="id" type="int">
        <constraints nullable="false" primaryKey="true"/>
      </column>
      <column name="from" type="date">
        <constraints nullable="false"/>
      </column>
      <column name="to" type="datetime">
        <constraints nullable="false"/>
      </column>
    </createTable>
  </changeSet>
</databaseChangeLog>

EOT;

        self::assertXmlStringEqualsXmlString($expected, $output);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function testUpdateFromEmptyDatabaseWithDefaultOptions(): void
    {
        $options = new LiquibaseOutputOptions();
        $options->setChangeSetUniqueId(false);
        $output  = $this->diffChangeLog($options);

        $expected = <<<'EOT'
<?xml version="1.0"?>
<databaseChangeLog>
  <changeSet author="doctrine-migrations-liquibase" id="create-table-Bar">
    <createTable tableName="Bar">
      <column name="id" type="varchar(255)">
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
      <column name="libelle" type="varchar(255)"/>
      <column name="commentaire" type="varchar(500)"/>
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
  <changeSet author="doctrine-migrations-liquibase" id="create-table-ReservedKeywords">
    <createTable tableName="ReservedKeywords">
      <column name="id" type="int">
        <constraints nullable="false" primaryKey="true"/>
      </column>
      <column name="from" type="date">
        <constraints nullable="false"/>
      </column>
      <column name="to" type="datetime">
        <constraints nullable="false"/>
      </column>
    </createTable>
  </changeSet>
</databaseChangeLog>

EOT;

        self::assertXmlStringEqualsXmlString($expected, $output);
    }

}
