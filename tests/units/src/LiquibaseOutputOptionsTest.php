<?php

declare(strict_types=1);

namespace Toilal\Doctrine\Migrations\Liquibase;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Toilal\Doctrine\Migrations\Liquibase\LiquibaseOutputOptions
 */
final class LiquibaseOutputOptionsTest extends TestCase
{

    private LiquibaseOutputOptions $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new LiquibaseOutputOptions();
    }

    /**
     * @test
     * @covers ::isUsePlatformTypes
     * @covers ::setUsePlatformTypes
     * @covers ::isChangeSetUniqueId
     * @covers ::setChangeSetUniqueId
     * @covers ::getChangeSetAuthor
     * @covers ::setChangeSetAuthor
     */
    public function setterAndGetter(): void
    {
        $this->assertFalse($this->object->isUsePlatformTypes());
        $this->object->setUsePlatformTypes(true);
        $this->assertTrue($this->object->isUsePlatformTypes());

        $this->assertTrue($this->object->isChangeSetUniqueId());
        $this->object->setChangeSetUniqueId(false);
        $this->assertFalse($this->object->isChangeSetUniqueId());

        $this->assertSame('doctrine-migrations-liquibase', $this->object->getChangeSetAuthor());
        $this->object->setChangeSetAuthor('user');
        $this->assertSame('user', $this->object->getChangeSetAuthor());
    }

}
