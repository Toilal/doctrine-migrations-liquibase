<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Foo
 *
 * @ORM\Entity
 * @ORM\Table
 */
class Foo
{
    /**
     * @var int|null
     *
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}