<?php

namespace App\Entity\Support;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Formule de support de diffusion
 * 
 * @ORM\Entity()
 * @ORM\Table(name="support_formula")
 */
class Formula
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Expression mathématique de la formule pour calcul
     * 
     * @var string|null
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $formula = null;

    /**
     * Nom de la formule pour affichage (ou de la variable intermédiaire)
     * 
     * @var string|null
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $name = null;

    /**
     * Chemin vers le champ de formulaire concerné par la formule
     * 
     * @var string|null
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $path = null;

    /**
     * Formules enfants
     * 
     * @var Collection<int, Formula>
     *    
     * @ORM\OneToMany(targetEntity="Formula", mappedBy="parentChild", cascade={"persist","remove"})
     */
    private Collection $children;

    /**
     * Variables intermédiaires
     * 
     * @var Collection<int, Formula>
     *    
     * @ORM\OneToMany(targetEntity="Formula", mappedBy="parentVar", cascade={"persist","remove"})
     */
    private Collection $vars;

    /**
     * Formule parente (si formule enfant)
     * 
     * @var Formula|null
     * 
     * @ORM\ManyToOne(targetEntity="Formula", inversedBy="children")
     * @ORM\JoinColumn(name="parent_children_id", referencedColumnName="id")
     */
    private ?Formula $parentChild;

    /**
     * Formule parente (si variable intermédiaire)
     * 
     * @var Formula|null
     * 
     * @ORM\ManyToOne(targetEntity="Formula", inversedBy="vars")
     * @ORM\JoinColumn(name="parent_vars_id", referencedColumnName="id")
     */
    private ?Formula $parentVar;

    /**
     * Support de diffusion de la formule
     * 
     * @var Support|null
     * 
     * @ORM\ManyToOne(targetEntity="Support", inversedBy="formulas")
     * @ORM\JoinColumn(name="support_id", referencedColumnName="id")
     */
    private ?Support $support;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->vars = new ArrayCollection();
    }  

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getFormula(): ?string
    {
        return $this->formula;
    }

    /**
     * @param string|null $formula
     * 
     * @return self
     */
    public function setFormula(?string $formula): self
    {
        $this->formula = $formula;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * 
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     * 
     * @return self
     */
    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return self|null
     */
    public function getParentChild(): ?self
    {
        return $this->parentChild;
    }

    /**
     * @param self|null $parentChild
     * 
     * @return self
     */
    public function setParentChild(?self $parentChild): self
    {
        $this->parentChild = $parentChild;

        return $this;
    }

    /**
     * @return self|null
     */
    public function getParentVar(): ?self
    {
        return $this->parentVar;
    }

    /**
     * @param self|null $parentVar
     * 
     * @return self
     */
    public function setParentVar(?self $parentVar): self
    {
        $this->parentVar = $parentVar;

        return $this;
    }

    /**
     * @return Collection<int, Formula>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Formula $child
     * 
     * @return self
     */
    public function addChild(Formula $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParentChild($this);
        }

        return $this;
    }

    /**
     * @param Formula $child
     * 
     * @return self
     */
    public function removeChild(Formula $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParentChild() === $this) {
                $child->setParentChild(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Formula>
     */
    public function getVars(): Collection
    {
        return $this->vars;
    }

    /**
     * @param Formula $var
     * 
     * @return self
     */
    public function addVar(Formula $var): self
    {
        if (!$this->vars->contains($var)) {
            $this->vars[] = $var;
            $var->setParentVar($this);
        }

        return $this;
    }

    /**
     * @param Formula $var
     * 
     * @return self
     */
    public function removeVar(Formula $var): self
    {
        if ($this->vars->removeElement($var)) {
            // set the owning side to null (unless already changed)
            if ($var->getParentVar() === $this) {
                $var->setParentVar(null);
            }
        }

        return $this;
    }

    /**
     * @return Support|null
     */
    public function getSupport(): ?Support
    {
        return $this->support;
    }

    /**
     * @param Support|null $support
     * 
     * @return self
     */
    public function setSupport(?Support $support): self
    {
        $this->support = $support;

        return $this;
    }
}
