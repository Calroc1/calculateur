<?php

namespace App\Entity\Support;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * Elément de formulaire de support de diffusion
 * 
 * @ORM\Entity()
 * @ORM\Table(name="support_form_element")
 * @UniqueEntity(fields={"name", "parent"})
 * @UniqueEntity(fields={"name", "support"})
 */
class FormElement
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Niveau de l'élement de formulaire dans la hiérarchie
     * 
     * @var int
     * 
     * @ORM\Column(type="integer")
     */
    private int $lvl = 0;

    /**
     * Position de l'élément de formulaire pour ordre d'affichage
     * 
     * @ORM\Column(type="integer")
     */
    private int $position;

    /**
     * Nom l'élément de formulaire pour chemin
     * 
     * @var string
     * 
     * 
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"support:view"})
     */
    private string $name;

    /**
     * Phase de l'élement de formulaire
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"support:view"})
     */
    private ?string $phase = null;

    /**
     * Libellé de l'élément de formulaire
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"support:view"})
     */
    private ?string $label = null;    

    /**
     * Type de l'élément de formulaire
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255)
     */
    private ?string $type;

    /**
     * Permet de définir si l'élément de formulaire est enreigstré en bdd ou non
     * 
     * @var bool
     * 
     * @ORM\Column(type="boolean")
     */
    private bool $mapped = true;

    /**
     * Configuration de l'élément de formulaire
     * 
     * @var array
     * 
     * @ORM\Column(type="json", nullable=true)
     */
    private array $config = [];

    /**
     * Date de création de l'élement
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime")
     */
    private ?\Datetime $dateCreation;

    /**
     * Elément de formulaire parent
     * 
     * @var FormElement|null
     * 
     * @ORM\ManyToOne(targetEntity="FormElement", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?FormElement $parent;

    /** 
     * Eléments de formulaire enfants (si section ou collection)
     * 
     * @var Collection<int, FormElement>
     *     
     * @ORM\OneToMany(targetEntity="FormElement", indexBy="name", mappedBy="parent", cascade={"persist", "remove"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private Collection $children;    

    /**
     * Support de diffusion de l'élément de formulaire
     * 
     * @var Support|null
     * 
     * @ORM\ManyToOne(targetEntity="Support", inversedBy="formElements")
     * @ORM\JoinColumn(name="support_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Support $support;

    /**
     * Obtenir un enfant en fonction de son chemin
     * 
     * @param mixed $names chemin vers l'enfant
     * 
     * @return FormElement|null
     */
    public function getChildByPath($names): ?FormElement
    {
        if($names === null)
            return $this;
        $names = is_string($names) ? explode('.', $names) : $names;
        $name = array_shift($names);
        if(isset($this->getChildren()[$name])){
            $child = $this->getChildren()[$name];
            if(count($names) > 0){
                return $child->getChildByPath($names);
            }
            return $child;
        }
        return null;
    }

    /**
     * Obtenir la valeur par défaut du champ de formulaire
     * 
     * @param bool $submit
     * 
     * @return mixed
     */
    public function getDefaultValue(bool $submit = false)
    {
        $config = $this->getConfig();
        if(!$submit && isset($config['default'])){
            if($config['default'] === 'entry' && $this->getType() == 'collection'){
                return [ $this->getDefaultEntry() ];
            }
            if($this->getType() == 'integer' && !is_numeric($config['default'])){
                return 0;
            }
            return $config['default'];
        }            
        switch($this->getType()){
            case 'section':
                return $this->getDefaultEntry();
            case 'collection':
                return [];
            case 'select':
                $return = $config['choices'][0];
                if(is_array($return)){
                    if(isset($return['label']))
                        return $return['label'];
                    return $return['value'];
                }
                return $return;
            case 'select_with_detail':                
                $return = $config['choices'][0];
                if(is_array($return)){
                    if(isset($return['label']))
                        return $return['label'];
                    return $return['value'];
                }
                return $return;
            case 'textarea':
                return "";
            case 'text':
                return "";
            case 'country':
                return "FR";
            case 'string':
                return "";
            case 'boolean':
                return 0;
            default:
                return '0';
        }
    }

    /**
     * Obtenir le tableau des choix pour un champ de type "select"
     * 
     * @return array
     */
    public function getChoices(): array
    {
        $return = [];
        if($this->getType() == 'select' || $this->getType() == 'select_with_detail'){
            foreach($this->getConfig()['choices'] as $k => $choice){
                $return[$k] = $choice['label'] ?? ($choice['value'] ?? $choice);
            }
        }
        return $return;
    }

    /**
     * Obtenir la phase de l'élement (ou de son parent le plus haut)
     * 
     * @return string
     */
    public function getAbsolutePhase(): string
    {
        if($this->getPhase())
            return $this->getPhase();
        else if ($this->getParent())
            return $this->getParent()->getAbsolutePhase();
        return "";
    }

    /**
     * Obtenir la valeur par défaut pour un champ de type "collection"
     * 
     * @return array
     */
    public function getDefaultEntry(): array
    {
        $defaultEntry = [];
        foreach($this->getChildren() as $c){
            $defaultEntry[$c->getName()] = $c->getDefaultValue();
        }
        return $defaultEntry;
    }

    /**
     * Obtenir le chemin complet vers l'élément
     * 
     * @return string
     */
    public function getCompletePath(): string
    {
        $return = $this->getName();
        if($this->getParent()){
            $return = $this->getParent()->getCompletePath().'.'.$return;
        }
        return $return;
    }

    /**
     * Obtenir le libellé complet de l'élément
     * 
     * @return string
     */
    public function getCompleteLabel(): string
    {
        $getParentLabel = function($rate) use (&$getParentLabel){
            $return = $rate->getLabel();            
            if($rate->getParent()){
                $add = "";
                if($rate->getLabel())
                    $add = " > ";
                $return = $getParentLabel($rate->getParent()).$add.$return;
            } 
            return $return;
        };
        return $getParentLabel($this);
    }

    public function __construct()
    {
        $this->dateCreation = new \Datetime();
        $this->children = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection|FormElement[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param FormElement $child
     * 
     * @return self
     */
    public function addChild(FormElement $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    /**
     * @param FormElement $child
     * 
     * @return self
     */
    public function removeChild(FormElement $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return self|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param self|null $parent
     * 
     * @return self
     */
    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLvl(): ?int
    {
        return $this->lvl;
    }

    /**
     * @param int $lvl
     * 
     * @return self
     */
    public function setLvl(int $lvl): self
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * 
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getMapped(): ?bool
    {
        return $this->mapped;
    }

    /**
     * @param bool $mapped
     * 
     * @return self
     */
    public function setMapped(bool $mapped): self
    {
        $this->mapped = $mapped;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array|null $config
     * 
     * @return self
     */
    public function setConfig(?array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhase(): ?string
    {
        return $this->phase;
    }

    /**
     * @param string $phase
     * 
     * @return self
     */
    public function setPhase(string $phase): self
    {
        $this->phase = $phase;

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
