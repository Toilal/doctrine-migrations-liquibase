<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase;


use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Toilal\Doctrine\Migrations\Liquibase\LiquibaseDOMDocumentOuput;
use Toilal\Doctrine\Migrations\Liquibase\LiquibaseOutputOptions;
use Toilal\Doctrine\Migrations\Liquibase\LiquibaseSchemaTool;

abstract class AbstractDatabaseTest extends TestCase
{
    /** @var EntityManager */
    protected $em;

    /**
     * @return array
     */
    abstract protected function getConnectionParameters();

    /**
     * @return string
     */
    abstract protected function getEntitiesPath();

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function setUp()
    {
        $config = new Configuration();

        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setProxyNamespace('Toilal\Doctrine\Migrations\Liquibase\Proxies');

        $config->setQueryCacheImpl(new ArrayCache());
        $config->setMetadataCacheImpl(new ArrayCache());

        $driver = $config->newDefaultAnnotationDriver(join('/', [dirname(__FILE__), $this->getEntitiesPath()]), false);
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
    protected function getCreateChangelog($options = null)
    {
        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();

        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        $out = new LiquibaseDOMDocumentOuput($document, $options);

        $schemaTool = new LiquibaseSchemaTool($this->em);
        $schemaTool->getCreateChangelog($metadatas, $out);

        $output = $document->saveXML();
        return $output;
    }

    protected function tearDown()
    {
        $this->em->close();
    }
}