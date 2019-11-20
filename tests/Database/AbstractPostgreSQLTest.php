<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Database;

use Toilal\Doctrine\Migrations\Liquibase\LiquibaseOutputOptions;

/**
 * @group docker
 */
abstract class AbstractPostgreSQLTest extends AbstractDockerDatabaseTest
{
    public function getDockerEnvironmentVariables()
    {
        return [
            'POSTGRES_DB' => 'test',
            'POSTGRES_USER' => 'test',
            'POSTGRES_PASSWORD' => 'test'
        ];
    }

    public function getDockerImage()
    {
        return 'postgres:11';
    }

    public function getDockerPublishedPort()
    {
        return 5432;
    }

    public function getConnectionParameters()
    {
        $connectionParameters = parent::getConnectionParameters();
        $env = $this->getDockerEnvironmentVariables();

        return array_merge($connectionParameters, [
            'driver' => 'pdo_pgsql',
            'dbname' => $env['POSTGRES_DB'],
            'user' => $env['POSTGRES_USER'],
            'password' => $env['POSTGRES_PASSWORD']
        ]);
    }

    public function getDockerRunOpts()
    {
        return '--health-cmd "pg_isready -h localhost -p 5432 -d test -U test" --health-interval 1s --health-timeout 5s --health-retries 3 --health-start-period 1s';
    }
}