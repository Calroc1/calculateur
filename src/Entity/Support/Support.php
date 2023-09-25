<?php

namespace App\Entity\Support;

use App\Entity\Support\Referential;
use App\Entity\Support\Type;
use App\Repository\Support\SupportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Support de diffusion
 * 
 * @ORM\Entity(repositoryClass=SupportRepository::class)
 * @UniqueEntity(fields={"name"})
 */
class Support
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Statut d'activation du support de diffusion
     * 
     * @var bool
     * 
     * @ORM\Column(type="boolean")
     */
    private bool $enabled = false;

    /**
     * Position du support de diffusion pour ordre d'affichage
     * 
     * @var int
     * 
     * @ORM\Column(type="integer")
     */
    private int $position;

    /**
     * Nom du support de diffusion (pour usage interne)
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"support:view"})
     */
    private string $name;

    /**
     * Libellé du support de diffusion
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"support:view"})
     */
    private string $label;

    /**
     * Couleur associée au support de diffusion
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Regex(
     *     pattern="/#[a-fA-F0-9]{6}/",
     *     message="La couleur doit être fournie au format hexadécimal"
     * )     
     */
    private ?string $color;

    /**
     * Date de création du support de diffusion
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime")
     */
    private ?\Datetime $dateCreation;

    /**
     * Date de dernière mise à jour du support de diffusion
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\Datetime $dateUpdate; 

    /**
     * Date de dernière mise à jour du formulaire du support de diffusion
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\Datetime $dateUpdateForm;

    /**
     * Date de dernière mise à jour de l'algorithme du support de diffusion
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\Datetime $dateUpdateAlgorithm;

    /**
     * Eléments de formulaires du support de diffusion
     * 
     * @var Collection<FormElement>
     *  
     * @ORM\OneToMany(targetEntity="FormElement", mappedBy="support", cascade={"persist", "remove"}, indexBy="name")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private Collection $formElements;

    /**    
     * Formules du support de diffusion
     * 
     * @var Collection<Formula>
     *  
     * @ORM\OneToMany(targetEntity="Formula", mappedBy="support", cascade={"persist", "remove"})
     */
    private Collection $formulas;

    /**
     * Référentiel du support de diffusion
     * 
     * @var Referential|null
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\Support\Referential", inversedBy="supports")
     * @ORM\JoinColumn(name="referential_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private ?Referential $referential;

    /**
     * Type du support de diffusion
     * 
     * @var Type|null
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\Support\Type", inversedBy="supports")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private ?Type $type;

    /**
     * Activation de l'affichage du nom sur la plateforme
     * 
     * @var bool
     * 
     * @ORM\Column(type="boolean")
     */
    private bool $displayName = false;

    /**
     * Obtenir la color au format RVB
     * 
     * @return string
     * 
     * @Serializer\Groups({"support:view"})
     * @SerializedName("color")
     */
    public function getColorRgb(): string
    {
        return \App\Utils\Utils::hexaToRgb($this->getColor());
    }

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->formElements = new ArrayCollection();
        $this->formulas = new ArrayCollection();
    }

    /**
     * Obtenir un élément de formulaire en fonction de son chemin
     * 
     * @param mixed $names
     * 
     * @return FormElement|null
     */
    public function getFormElementByPath($names): ?FormElement
    {
        $names = is_string($names) ? explode('.', $names) : $names;        
        $name = array_shift($names);
        if(isset($this->getFormElements()[$name])){
            $child = $this->getFormElements()[$name];
            if(count($names) > 0){
                return $child->getChildByPath($names);
            }
            return $child;
        }
        return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return bool|null
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * 
     * @return self
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @param int $position
     * 
     * @return self
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;

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
     * @param string $name
     * 
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     * 
     * @return self
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string $color
     * 
     * @return self
     */
    public function setColor(string $color): self
    {
        $this->color = $color;

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
     * @return \DateTimeInterface|null
     */
    public function getDateUpdate(): ?\DateTimeInterface
    {
        return $this->dateUpdate;
    }

    /**
     * @param \DateTimeInterface|null $dateUpdate
     * 
     * @return self
     */
    public function setDateUpdate(?\DateTimeInterface $dateUpdate): self
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * @return Collection<int, FormElement>
     */
    public function getFormElements(): Collection
    {
        return $this->formElements;
    }

    /**
     * @param FormElement $formElement
     * 
     * @return self
     */
    public function addFormElement(FormElement $formElement): self
    {
        if (!$this->formElements->contains($formElement)) {
            $this->formElements[] = $formElement;
            $formElement->setSupport($this);
        }

        return $this;
    }

    /**
     * @param FormElement $formElement
     * 
     * @return self
     */
    public function removeFormElement(FormElement $formElement): self
    {
        if ($this->formElements->removeElement($formElement)) {
            // set the owning side to null (unless already changed)
            if ($formElement->getSupport() === $this) {
                $formElement->setSupport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Formula>
     */
    public function getFormulas(): Collection
    {
        return $this->formulas;
    }

    /**
     * @param Formula $formula
     * 
     * @return self
     */
    public function addFormula(Formula $formula): self
    {
        if (!$this->formulas->contains($formula)) {
            $this->formulas[] = $formula;
            $formula->setSupport($this);
        }

        return $this;
    }

    /**
     * @param Formula $formula
     * 
     * @return self
     */
    public function removeFormula(Formula $formula): self
    {
        if ($this->formulas->removeElement($formula)) {
            // set the owning side to null (unless already changed)
            if ($formula->getSupport() === $this) {
                $formula->setSupport(null);
            }
        }

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateUpdateForm(): ?\DateTimeInterface
    {
        return $this->dateUpdateForm;
    }

    /**
     * @param \DateTimeInterface|null $dateUpdateForm
     * 
     * @return self
     */
    public function setDateUpdateForm(?\DateTimeInterface $dateUpdateForm): self
    {
        $this->dateUpdateForm = $dateUpdateForm;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateUpdateAlgorithm(): ?\DateTimeInterface
    {
        return $this->dateUpdateAlgorithm;
    }

    /**
     * @param \DateTimeInterface|null $dateUpdateAlgorithm
     * 
     * @return self
     */
    public function setDateUpdateAlgorithm(?\DateTimeInterface $dateUpdateAlgorithm): self
    {
        $this->dateUpdateAlgorithm = $dateUpdateAlgorithm;

        return $this;
    }

    /**
     * @return Referential|null
     */
    public function getReferential(): ?Referential
    {
        return $this->referential;
    }

    /**
     * @param Referential|null $referential
     * 
     * @return self
     */
    public function setReferential(?Referential $referential): self
    {
        $this->referential = $referential;

        return $this;
    }

    /**
     * @return Type|null
     */
    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * @param Type|null $type
     * 
     * @return self
     */
    public function setType(?Type $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getDisplayName(): ?bool
    {
        return $this->displayName;
    }

    /**
     * @param bool $displayName
     * 
     * @return self
     */
    public function setDisplayName(bool $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }
}
