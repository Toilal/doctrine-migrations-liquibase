<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Database;


use Doctrine\Common\Cache\ArrayCache;
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

    abstract protected function getConnectionParameters();

    /**
     * @return string
     */
    abstract protected function getEntitiesPath();

    /**
     * Setup database;
     */
    protected function setUpDatabase()
    {
    }

    /**
     * Teardown database
     */
    protected function tearDownDatabase()
    {
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function setUp()
    {
        $this->setUpDatabase();
        $config = new Configuration();

        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setProxyNamespace('Toilal\Doctrine\Migrations\Liquibase\Proxies');

        $config->setQueryCacheImpl(new ArrayCache());
        $config->setMetadataCacheImpl(new ArrayCache());

        $driver = $config->newDefaultAnnotationDriver([join('/', [dirname(__FILE__), '..', $this->getEntitiesPath()])], false);
        $config->setMetadataDriverImpl($driver);

        $params = $this->getConnectionParameters();
        $this->em = EntityManager::create($params, $config);
    }

    /**
     * @param LiquibaseOutputOptions|null $options
     * @return string
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function changeLog($options = null)
    {
        $schemaTool = new LiquibaseSchemaTool($this->em);
        $output = $schemaTool->changeLog($options)->saveXML();
        return $output;
    }

    /**
     * @param LiquibaseOutputOptions|null $options
     * @return string
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function diffChangeLog($options = null)
    {
        $schemaTool = new LiquibaseSchemaTool($this->em);
        $output = $schemaTool->diffChangeLog($options)->saveXML();
        return $output;
    }

    protected function tearDown()
    {
        $this->tearDownDatabase();
        $this->em->close();
    }
}