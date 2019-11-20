<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Database;


/**
 * @group docker
 */
abstract class AbstractDockerDatabaseTest extends AbstractDatabaseTest
{
    /** @var array */
    protected $dockerState = [];

    /**
     * @return number
     */
    abstract function getDockerPublishedPort();

    /**
     * @return string
     */
    abstract function getDockerImage();

    /**
     * @return array
     */
    function getDockerEnvironmentVariables()
    {
        return [];
    }

    /**
     * @return string|null
     */
    function getDockerRunOpts()
    {
        return null;
    }


    function getConnectionParameters()
    {
        return [
            'port' => $this->dockerState['port'],
            'host' => $this->dockerState['host'],
        ];
    }

    protected function setUpDatabase()
    {
        $cmd = "docker run -d";
        $cmd .= " -p {$this->getDockerPublishedPort()}";

        foreach ($this->getDockerEnvironmentVariables() as $key => $value) {
            $cmd .= " -e \"$key=$value\"";
        }

        $opts = $this->getDockerRunOpts();
        if ($opts) {
            $cmd .= " $opts";
        }

        $cmd .= " {$this->getDockerImage()}";

        $containerId = exec("$cmd");

        $this->dockerState['containerId'] = $containerId;
        $portMapping = exec("docker port $containerId {$this->getDockerPublishedPort()}");

        preg_match("/^(.*?):(\d+)$/", $portMapping, $portMatches);
        $port = $portMatches[2];

        $dockerHost = getenv('DOCKER_HOST') ?: 'tcp://127.0.0.1:2375';
        preg_match("/^(.*?):\/\/(.*?):(\d+)$/", $dockerHost, $dockerHostMatches);
        $host = $dockerHostMatches[2];

        $this->dockerState['port'] = intval($port);
        $this->dockerState['host'] = $host;

        $this->waitForDockerPublishedPort();
        $this->waitForDockerHealthy();
    }

    private function waitForDockerPublishedPort($timeout = 4)
    {
        $port = $this->dockerState['port'];
        $host = $this->dockerState['host'];

        if ($fp = fsockopen($host, $port, $errCode, $errStr, $timeout)) {
            fclose($fp);
        } else {
            throw new \Exception("Can't reach docker published port after ${timeout}s.");
        }
    }

    protected function waitForDockerHealthy($timeout = 30)
    {
        $containerId = $this->dockerState['containerId'];
        $start = time();

        while (true) {
            $health = exec("docker inspect $containerId --format '{{.State.Health.Status}}'");
            if ($health === 'healthy' || $health === '<nil>') {
                return;
            }

            if (time() - $start > $timeout) {
                throw new \Exception("Timeout has occured while waiting fordocker container Healthcheck.");
            }

            sleep(1);
        }
    }

    protected function tearDownDatabase()
    {
        $containerId = $this->dockerState['containerId'];
        exec("docker rm -f $containerId");
    }

    protected function waitForDatabaseAvailable()
    {
    }

}