<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase;

use Tests\Toilal\Doctrine\Migrations\Liquibase\Database\AbstractMySQLTest;
use Toilal\Doctrine\Migrations\Liquibase\LiquibaseOutputOptions;


/**
 * @group docker
 */
class MySQL5Test extends AbstractMySQLTest
{
    public function getDockerImage()
    {
        return 'mysql:5';
    }

    public function getDockerPublishedPort()
    {
        return 3306;
    }

    public function getDockerRunOpts()
    {
        return '--health-cmd "mysqladmin ping -h 127.0.0.1 -u test --password=test" --health-interval 1s --health-timeout 5s --health-retries 3 --health-start-period 1s';
    }

    public function getConnectionParameters()
    {
        $connectionParameters = parent::getConnectionParameters();
        $env = $this->getDockerEnvironmentVariables();

        return array_merge($connectionParameters, [
            'driver' => 'pdo_mysql',
            'dbname' => $env['MYSQL_DATABASE'],
            'user' => $env['MYSQL_USER'],
            'password' => $env['MYSQL_PASSWORD']]);
    }

    protected function getEntitiesPath()
    {
        return 'Entity';
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCreateWithDefaultOptions()
    {
        $options = new LiquibaseOutputOptions();
        $options->setChangeSetUniqueId(false);
        $output = $this->changeLog($options);

        $expected = <<<'EOT'
<?xml version="1.0"?>
<databaseChangeLog>
  <changeSet author="doctrine-migrations-liquibase" id="create-schema-test">
    <sql>CREATE SCHEMA `test`</sql>
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
    public function testUpdateFromEmptyDatabaseWithDefaultOptions()
    {
        $options = new LiquibaseOutputOptions();
        $options->setChangeSetUniqueId(false);
        $output = $this->diffChangeLog($options);

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