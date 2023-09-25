<?php

namespace App\Entity\Emission;

use Doctrine\ORM\Mapping as ORM;

/**
 * Valeur de facteur d'émission
 * 
 * @ORM\Entity()
 * @ORM\Table(name="emission_value")
 */
class Value
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Valeur du facteur d'émission
     * 
     * @var float
     * 
     * @ORM\Column(type="float")
     */
    private float $value;

    /**
     * Date de création de la valeur
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime")
     */
    private ?\Datetime $dateCreation;

    /**
     * Facteur d'émission
     * 
     * @var Rate
     * 
     * @ORM\ManyToOne(targetEntity="Rate", inversedBy="values")
     * @ORM\JoinColumn(name="rate_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private Rate $rate;

    public function __construct()
    {
        $this->dateCreation = new \Datetime();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float|null
     */
    public function getValue(): ?float
    {
        return $this->value;
    }

    /**
     * @param float $value
     * 
     * @return self
     */
    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    /**
     * @param \DateTimeInterface $dateCreation
     * 
     * @return self
     */
    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * @return Rate|null
     */
    public function getRate(): ?Rate
    {
        return $this->rate;
    }

    /**
     * @param Rate|null $rate
     * 
     * @return self
     */
    public function setRate(?Rate $rate): self
    {
        $this->rate = $rate;

        return $this;
    }
}
