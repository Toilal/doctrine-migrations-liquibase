<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Database;

use Toilal\Doctrine\Migrations\Liquibase\LiquibaseOutputOptions;


/**
 * @group docker
 */
abstract class AbstractMySQLTest extends AbstractDockerDatabaseTest
{
    public function getDockerEnvironmentVariables()
    {
        return [
            'MYSQL_RANDOM_ROOT_PASSWORD' => '1',
            'MYSQL_DATABASE' => 'test',
            'MYSQL_USER' => 'test',
            'MYSQL_PASSWORD' => 'test'
        ];
    }

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
}