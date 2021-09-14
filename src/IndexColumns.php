<?php

declare(strict_types=1);

namespace Toilal\Doctrine\Migrations\Liquibase;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;

class IndexColumns
{

    /**
     * @var string[]
     */
    private array $primaryColumns = [];

    /**
     * @var Index[]
     */
    private array $otherIndexes = [];

    /**
     * @var Index[]
     */
    private array $uniqueColumns = [];

    /**
     * IndexColumns constructor.
     */
    public function __construct(Table $table)
    {
        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                foreach ($index->getColumns() as $primaryColumn) {
                    array_push($this->primaryColumns, $primaryColumn);
                }
            } else if ($index->isUnique() && count($index->getColumns()) === 1) {
                $this->uniqueColumns[$index->getColumns()[0]] = $index;
            } else {
                array_push($this->otherIndexes, $index);
            }
        }
    }

    /**
     * @return string[]
     */
    public function getPrimaryColumns(): array
    {
        return $this->primaryColumns;
    }

    /**
     * @return Index[]
     */
    public function getUniqueColumns(): array
    {
        return $this->uniqueColumns;
    }

    /**
     * @return Index[]
     */
    public function getOtherIndexes(): array
    {
        return $this->otherIndexes;
    }

    public function getPrimaryKeyColumns(): array
    {
        return $this->primaryColumns;
    }

}
