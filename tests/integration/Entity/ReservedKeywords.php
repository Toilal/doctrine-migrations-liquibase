<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Foo
 *
 * @ORM\Entity
 * @ORM\Table
 */
class ReservedKeywords
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
     * @var \DateTime|null
     *
     * @ORM\Column(type="date")
     */
    private $from;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime")
     */
    private $to;

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

    /**
     * @return \DateTime|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param \DateTime|null $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @return \DateTime|null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param \DateTime|null $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }
}