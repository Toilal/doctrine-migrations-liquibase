<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Foo
 *
 * @ORM\Entity
 * @ORM\Table
 */
class Bar
{
    /**
     * @var string|null
     *
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    private $id;


    /**
     * @return null|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param null|string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}