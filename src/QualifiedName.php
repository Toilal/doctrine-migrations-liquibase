<?php

namespace Toilal\Doctrine\Migrations\Liquibase;

use Doctrine\DBAL\Schema\AbstractAsset;

class QualifiedName
{
    /**
     * @var string|null
     */
    private $namespaceName;
    /**
     * @var string
     */
    private $name;


    static function fromQualifiedName($qualifiedName)
    {
        $tableNameArray = explode('.', $qualifiedName, 2);

        $namespaceName = null;
        $name = null;
        if (count($tableNameArray) > 1) {
            $namespaceName = $tableNameArray[0];
            $name = $tableNameArray[1];
        } else {
            $name = $qualifiedName;
        }

        return new QualifiedName($name, $namespaceName);
    }

    static function fromAsset(AbstractAsset $asset)
    {
        $namespaceName = $asset->getNamespaceName();
        if ($namespaceName) {
            $name = $asset->getShortestName($namespaceName);
        } else {
            $name = $asset->getName();
        }
        return new QualifiedName($name, $namespaceName);
    }

    /**
     * AssetName constructor.
     * @param string|null $name
     * @param $namespaceName
     */
    public function __construct($name, $namespaceName)
    {
        $this->namespaceName = $namespaceName;
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getNamespaceName()
    {
        return $this->namespaceName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}