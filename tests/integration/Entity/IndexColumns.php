<?php

namespace Tests\Toilal\Doctrine\Migrations\Liquibase\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"date"}),
 *     @ORM\Index(columns={"libelle"}),
 *     @ORM\Index(columns={"commentaire"})
 * })
 */
class IndexColumns
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
     *
     * @ORM\Column(nullable=true, type="date")
     */
    private $date;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $libelle;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true, length=500)
     */
    private $commentaire;

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
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime|null $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return null|string
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * @param null|string $libelle
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
    }

    /**
     * @return null|string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * @param null|string $commentaire
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;
    }
}