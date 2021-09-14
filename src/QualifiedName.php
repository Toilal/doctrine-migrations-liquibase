<?php

declare(strict_types=1);

namespace Toilal\Doctrine\Migrations\Liquibase;

use Doctrine\DBAL\Schema\AbstractAsset;

class QualifiedName
{

    private ?string $namespaceName = null;
    private string $name          = '';

    public static function fromQualifiedName(string $qualifiedName): self
    {
        $tableNameArray = explode('.', $qualifiedName, 2);

        $namespaceName = null;
        $name          = null;
        if (count($tableNameArray) > 1) {
            $namespaceName = $tableNameArray[0];
            $name          = $tableNameArray[1];
        } else {
            $name = $qualifiedName;
        }

        return new QualifiedName($name, $namespaceName);
    }

    public static function fromAsset(AbstractAsset $asset): self
    {
        $namespaceName = $asset->getNamespaceName();
        if ($namespaceName) {
            $name = $asset->getShortestName($namespaceName);
        } else {
            $name = $asset->getName();
        }
        return new QualifiedName($name, $namespaceName);
    }

    public function __construct(string $name, ?string $namespaceName = null)
    {
        $this->name          = $name;
        $this->namespaceName = $namespaceName;
    }

    public function getNamespaceName(): ?string
    {
        return $this->namespaceName;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
