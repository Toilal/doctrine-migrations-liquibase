<?php

declare(strict_types=1);

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Database;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Toilal\Doctrine\Migrations\Liquibase\LiquibaseOutputOptions;
use Toilal\Doctrine\Migrations\Liquibase\LiquibaseSchemaTool;

abstract class AbstractDatabaseTest extends TestCase
{

    /** @var EntityManager */
    protected $em;

    /**
     * @var array
     */
    protected $databaseState = [];

    abstract protected function getConnectionParameters(): array;

    /**
     * @return string
     */
    abstract protected function getEntitiesPath(): string;

    /**
     * Setup database;
     */
    protected function setUpDatabase(): void
    {

    }

    /**
     * Teardown database
     */
    protected function tearDownDatabase(): void
    {

    }

    private function driverIsAvailable(): bool
    {
        $params = $this->getConnectionParameters();
        if (!empty($params['driver'])) {
            $driverName = $params['driver'];

            if (!extension_loaded($driverName)) {
                $this->markTestSkipped(sprintf(
                        'Driver "%s" is not available',
                        $driverName
                ));

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function setUp(): void
    {
        $this->setUpDatabase();
        $config = new Configuration();

        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setProxyNamespace('Toilal\Doctrine\Migrations\Liquibase\Proxies');

        //$config->setQueryCacheImpl(new ArrayCache());
        //$config->setMetadataCacheImpl(new ArrayCache());

        $driver = $config->newDefaultAnnotationDriver([join('/', [dirname(__FILE__), '..', $this->getEntitiesPath()])], false);
        $config->setMetadataDriverImpl($driver);

        $params = $this->getConnectionParameters();

        if (false === $this->driverIsAvailable()) {
            return;
        }

        $this->em = EntityManager::create($params, $config);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function changeLog(LiquibaseOutputOptions $options = null): string
    {
        $schemaTool = new LiquibaseSchemaTool($this->em);
        $output     = $schemaTool->changeLog($options)->saveXML();
        return $output;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function diffChangeLog(LiquibaseOutputOptions $options = null): string
    {
        $schemaTool = new LiquibaseSchemaTool($this->em);
        $output     = $schemaTool->diffChangeLog($options)->saveXML();
        return $output;
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        $this->em->close();
    }

}
