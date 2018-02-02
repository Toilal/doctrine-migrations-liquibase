<?php

namespace Toilal\Doctrine\Migrations\Liquibase;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class LiquibaseOutputOptions
{
    /**
     * @var bool
     */
    private $usePlatformTypes = false;

    /**
     * @var bool
     */
    private $changeSetUniqueId = true;

    /**
     * @var string
     */
    private $changeSetAuthor = 'doctrine-migrations-liquibase';

    /**
     * @return bool
     */
    public function isUsePlatformTypes()
    {
        return $this->usePlatformTypes;
    }

    /**
     * @param bool $usePlatformTypes
     * @return LiquibaseOutputOptions
     */
    public function setUsePlatformTypes($usePlatformTypes)
    {
        $this->usePlatformTypes = $usePlatformTypes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isChangeSetUniqueId()
    {
        return $this->changeSetUniqueId;
    }

    /**
     * @param bool $changeSetUniqueId
     * @return LiquibaseOutputOptions
     */
    public function setChangeSetUniqueId($changeSetUniqueId)
    {
        $this->changeSetUniqueId = $changeSetUniqueId;
        return $this;
    }

    /**
     * @return string
     */
    public function getChangeSetAuthor()
    {
        return $this->changeSetAuthor;
    }

    /**
     * @param string $changeSetAuthor
     * @return LiquibaseOutputOptions
     */
    public function setChangeSetAuthor($changeSetAuthor)
    {
        $this->changeSetAuthor = $changeSetAuthor;
        return $this;
    }

}