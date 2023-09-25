<?php

namespace App\Entity\Campaign;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Support\FormElement;

/**
 * Donnée de formulaire
 * 
 * @ORM\Entity()
 * @ORM\Table(
 *    name="campaign_data",
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(columns={"field_id", "variant_id"})
 *    }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Data
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Donnée renseignée pour l'élément de formulaire
     * 
     * @ORM\Column(type="json", nullable=true)
     */
    private $value;

    /**
     * Elément de formulaire
     * 
     * @var FormElement|null
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\Support\FormElement")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private ?FormElement $field;

    /**
     * Variante
     * 
     * @var Variant|null
     * 
     * @ORM\ManyToOne(targetEntity="Variant", inversedBy="datas")
     * @ORM\JoinColumn(name="variant_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Variant $variant;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return FormElement|null
     */
    public function getField(): ?FormElement
    {
        return $this->field;
    }

    /**
     * @param FormElement|null $field
     * 
     * @return self
     */
    public function setField(?FormElement $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getValue()
    {
        return json_decode($this->value, true);
    }

    /**
     * @param mixed $value
     * 
     * @return self
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Variant|null
     */
    public function getVariant(): ?Variant
    {
        return $this->variant;
    }

    /**
     * @param Variant|null $variant
     * 
     * @return self
     */
    public function setVariant(?Variant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }
}
