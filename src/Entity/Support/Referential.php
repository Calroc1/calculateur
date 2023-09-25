<?php

namespace App\Entity\Support;

use App\Entity\Support\Support;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Référentiel
 * 
 * @ORM\Entity()
 * @ORM\Table(name="support_referential")
 * @UniqueEntity(fields={"name"})
 */
class Referential
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Libellé du référentiel
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**     
     * Supports de diffusion liés au référentiel
     * 
     * @var Collection
     * 
     * @ORM\OneToMany(targetEntity="App\Entity\Support\Support", mappedBy="referential")
     */
    private Collection $supports;

    /**
     * Obtenir le nom du référentiel "propre" sans caractères spéciaux
     * 
     * @return string
     */
    public function getSafeName(): string
    {
        return \App\Utils\Utils::sanitizeForFilename($this->getName());
    }

    public function __construct()
    {
        $this->supports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Support>
     */
    public function getSupports(): Collection
    {
        return $this->supports;
    }

    public function addSupport(Support $support): self
    {
        if (!$this->supports->contains($support)) {
            $this->supports[] = $support;
            $support->setReferential($this);
        }

        return $this;
    }

    public function removeSupport(Support $support): self
    {
        if ($this->supports->removeElement($support)) {
            // set the owning side to null (unless already changed)
            if ($support->getReferential() === $this) {
                $support->setReferential(null);
            }
        }

        return $this;
    }
}
