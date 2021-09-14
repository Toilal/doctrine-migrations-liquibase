<?php

declare(strict_types=1);

namespace Toilal\Doctrine\Migrations\Liquibase;

use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\AbstractAsset;

/**
 * @coversDefaultClass Toilal\Doctrine\Migrations\Liquibase\QualifiedName
 */
final class QualifiedNameTest extends TestCase
{

    private QualifiedName $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new QualifiedName('name', 'namespace');
    }

    /**
     * @test
     * @covers ::fromQualifiedName
     */
    public function fromQualifiedName(): void
    {
        $object = QualifiedName::fromQualifiedName('namespace.name');
        $this->assertSame('name', $object->getName());
        $this->assertSame('namespace', $object->getNamespaceName());
    }

    /**
     * @test
     * @covers ::fromQualifiedName
     */
    public function fromQualifiedNameWithoutNamespace(): void
    {
        $object = QualifiedName::fromQualifiedName('name');
        $this->assertSame('name', $object->getName());
        $this->assertNull($object->getNamespaceName());
    }

    /**
     * @test
     * @covers ::fromAsset
     */
    public function fromAsset(): void
    {
        $asset = $this->prophesize(AbstractAsset::class);
        $asset->getNamespaceName()->shouldBeCalled()->willReturn('namespace');
        $asset->getShortestName('namespace')->shouldBeCalled()->willReturn('name');

        $object = QualifiedName::fromAsset($asset->reveal());
        $this->assertSame('name', $object->getName());
        $this->assertSame('namespace', $object->getNamespaceName());
    }

    /**
     * @test
     * @covers ::fromAsset
     */
    public function fromAssetEmptyNamespace(): void
    {
        $asset = $this->prophesize(AbstractAsset::class);
        $asset->getNamespaceName()->shouldBeCalled()->willReturn('');
        $asset->getName()->shouldBeCalled()->willReturn('name');

        $object = QualifiedName::fromAsset($asset->reveal());
        $this->assertSame('name', $object->getName());
        $this->assertSame('', $object->getNamespaceName());
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::getNamespaceName
     * @covers ::getName
     */
    public function constructorAndGetters(): void
    {
        $this->assertSame('name', $this->object->getName());
        $this->assertSame('namespace', $this->object->getNamespaceName());
    }

}
