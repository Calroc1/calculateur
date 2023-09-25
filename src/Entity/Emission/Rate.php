<?php

namespace App\Entity\Emission;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Facteur d'émission
 * 
 * @ORM\Entity()
 * @ORM\Table(name="emission_rate", uniqueConstraints={
 *        @ORM\UniqueConstraint(columns={"name", "parent_id"})
 *    }
 * )
 * @UniqueEntity(fields={"name", "parent"})
 */
class Rate
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Nom
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * Libellé
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $label;

    /**
     * Unité de mesure
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $unit;

    /**
     * Commentaire
     * 
     * @var string|null
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $comment;

    /**
     * Source
     * 
     * @var string|null
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $source;

    /**
     * Facteur d'émission parent
     * 
     * @var Rate
     * 
     * @ORM\ManyToOne(targetEntity="Rate", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Rate $parent;

    /**     
     * Facteurs d'émission enfants
     * 
     * @var Collection<int, Rate>
     *  
     * @ORM\OneToMany(targetEntity="Rate", indexBy="name", mappedBy="parent", cascade={"persist", "remove"}, indexBy="name")
     */
    private Collection $children; 

    /**    
     * Valeurs
     * 
     * @var Collection<int, Value>
     *  
     * @ORM\OneToMany(targetEntity="Value", mappedBy="rate", cascade={"persist", "remove"})
     */
    private Collection $values;
    
    /**
     * Date de création
     * 
     * @var \Datetime
     * 
     * @ORM\Column(type="datetime")
     */
    private \Datetime $dateCreation;

    /**
     * Date de mise à jour
     * 
     * @var \Datetime
     * 
     * @ORM\Column(type="datetime")
     */
    private \Datetime $dateUpdate;

    /**
     * Date d'alerte
     * 
     * @var \Datetime
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    private \Datetime $dateAlert;

    /**
     * Historique
     * 
     * @var array|null
     * 
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $history = [];

    /**
     * Permet d'ajouter une entrée au log d'historique du facteur d'émission
     * 
     * @param User $user
     * @param string $type
     * 
     * @return void
     */
    public function addHistory(UserInterface $user, string $type = "update") :void{
        $now = new \Datetime();
        $history = $this->getHistory();
        $history[] = [
            'type' => $type,
            'user' => $user->__toString(),
            'datetime' => $now->format('d/m/Y à H:i')
        ];
        $this->setHistory($history);
    }

    /**
     * Permet d'obtenir le nom complet du facteur d'émission
     * 
     * @return string
     */
    public function getCompleteName() :string{
        $getParentName = function($rate) use (&$getParentName){
            $return = $rate->getName();
            if($rate->getParent())
                $return = $getParentName($rate->getParent()).".".$return;
            return $return;
        };
        return $getParentName($this);
    }

    /**
     * Permet d'obtenir le libellé complet du facteur d'émission
     * 
     * @return string
     */
    public function getCompleteLabel(): string{
        $getParentLabel = function($rate) use (&$getParentLabel){
            $return = $rate->getLabel();
            if($rate->getParent())
                $return = $getParentLabel($rate->getParent())." > ".$return;
            return $return;
        };
        return $getParentLabel($this);
    }

    /**
     * Permet d'obtenir la source du facteur d'émission, en fonction de son parent
     * 
     * @return string
     */
    public function getRecursiveSource(): ?string{
        $getSource = function($rate) use (&$getSource){
            return $rate->getSource() ? $rate->getSource() : ($rate->getParent() ? $getSource($rate->getParent()) : null);
        };
        return $getSource($this);
    }

    /**
     * Permet d'obtenir l'unité de mesure du facteur d'émission, en fonction de son parent
     * 
     * @return string
     */
    public function getRecursiveUnit(): ?string{
        $getUnit = function($rate) use (&$getUnit){
            return $rate->getUnit() ? $rate->getUnit() : ($rate->getParent() ? $getUnit($rate->getParent()) : 'kg équ. CO2/unité');
        };
        return $getUnit($this);
    }

    /**
     * Permet d'obtenir le commentaire du facteur d'émission, en fonction de son parent
     * 
     * @return string
     */
    public function getRecursiveComment(): ?string{
        $getComment = function($rate) use (&$getComment){
            return $rate->getComment() ? $rate->getComment() : ($rate->getParent() ? $getComment($rate->getParent()) : null);
        };
        return $getComment($this);
    }

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->values = new ArrayCollection();
        $this->dateCreation = new \Datetime();
        $this->dateUpdate = new \Datetime();
    }

    /**
     * Permet d'obtenir la valeur la plus récente (donc actuelle)
     * 
     * @return float
     */
    public function getCurrentValue(): float{
        $last = $this->getValues()->last();
        return $last ? $last->getValue() : null;
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
     * @return Collection|Rate[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Rate $child
     * 
     * @return self
     */
    public function addChild(Rate $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    /**
     * @param Rate $child
     * 
     * @return self
     */
    public function removeChild(Rate $child): self
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
     * @return Collection|Value[]
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    /**
     * @param Value $value
     * 
     * @return self
     */
    public function addValue(Value $value): self
    {
        if (!$this->values->contains($value)) {
            $this->values[] = $value;
            $value->setRate($this);
        }

        return $this;
    }

    /**
     * @param Value $value
     * 
     * @return self
     */
    public function removeValue(Value $value): self
    {
        if ($this->values->removeElement($value)) {
            // set the owning side to null (unless already changed)
            if ($value->getRate() === $this) {
                $value->setRate(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     * 
     * @return self
     */
    public function setUnit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     * 
     * @return self
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @param string|null $source
     * 
     * @return self
     */
    public function setSource(?string $source): self
    {
        $this->source = $source;

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
     * @param \DateTimeInterface $dateUpdate
     * 
     * @return self
     */
    public function setDateUpdate(\DateTimeInterface $dateUpdate): self
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        if(!$this->label)
            return $this->name;
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
     * @return array|null
     */
    public function getHistory(): ?array
    {
        return $this->history;
    }

    /**
     * @param array|null $history
     * 
     * @return self
     */
    public function setHistory(?array $history): self
    {
        $this->history = $history;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAlert(): ?\DateTimeInterface
    {
        return $this->dateAlert;
    }

    /**
     * @param \DateTimeInterface|null $dateAlert
     * 
     * @return self
     */
    public function setDateAlert(?\DateTimeInterface $dateAlert): self
    {
        $this->dateAlert = $dateAlert;

        return $this;
    }
}
