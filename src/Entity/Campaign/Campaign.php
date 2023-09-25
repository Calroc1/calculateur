<?php

namespace App\Entity\Campaign;

use App\Entity\Organism;
use App\Entity\User;
use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * Campagne
 * 
 * @ORM\Entity(repositoryClass=App\Repository\Campaign\CampaignRepository::class)
 */
class Campaign
{
    /**
     * Option de budget
     * 
     * @var array
     */
    const NOTION_BUDGET = [
        'Oui, à l’échelle de la campagne' => self::NOTION_BUDGET_YES_CAMPAIGN,
        'Oui, à l’échelle des médias' => self::NOTION_BUDGET_YES_MEDIA,
        'Non' => self::NOTION_BUDGET_NO,
    ];
    const NOTION_BUDGET_YES_CAMPAIGN = 1;
    const NOTION_BUDGET_YES_MEDIA = 2;
    const NOTION_BUDGET_NO = 0;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"campaign:view", "stats"})
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"stats"})
     */
    private ?\Datetime $dateCreation;

    /**
     * @var bool
     * 
     * @ORM\Column(type="boolean")
     */
    private bool $completed = false;

    /**
     * Nom de la campagne
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"campaign:view", "stats"})
     */
    private string $name;

    /**
     * Date de début de la campagne
     * 
     * @var \Datetime
     * 
     * @ORM\Column(type="date")
     * @Serializer\Groups({"stats"})
     */
    private ?\Datetime $dateStart;

    /**
     * Date de fin de la campagne
     * 
     * @var \Datetime
     * 
     * @ORM\Column(type="date")
     * @Serializer\Groups({"campaign:view", "campaign:date", "stats"})
     */
    private ?\Datetime $dateEnd;

    /**
     * Pays de la campagne
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=255)
     */
    private string $country = 'FR';

    /**
     * Organisme de la campagne
     * 
     * @var Organism
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\Organism", inversedBy="campaigns")
     * @ORM\JoinColumn(name="organism_id", referencedColumnName="id")
     * @Serializer\Groups({"stats"})
     */
    private ?Organism $organism = null;

    /**
     * Auteur de la campagne
     * 
     * @var User|null
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL", nullable=true)
     */
    private ?User $author;

    /**
     * Variante de la campagne
     * 
     * @var Collection<int, Variant>
     * 
     * @ORM\OneToMany(targetEntity="Variant", mappedBy="campaign", cascade={"persist","remove"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private Collection $variants;

    /**
     * Variante sélectionnée
     * 
     * @var Variant|null
     * 
     * @ORM\OneToOne(targetEntity="Variant")
     * @ORM\JoinColumn(name="chosen_variant_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private ?Variant $chosenVariant;

    /**
     * Phases activées pour la campagne
     * 
     * @var array|null
     * 
     * @ORM\Column(type="json", nullable=true)
     * @Serializer\Groups({"variant:view"})
     */
    private ?array $phases = [
        'conception', 'production', 'diffusion'
    ];

    /**
     * Permet de définir si la notion de performance média est activée
     * 
     * @var bool
     * 
     * @ORM\Column(type="boolean")
     */
    private bool $hasNotionMediaEfficiency = false;

    /**
     * Permet de définir si la notion de budget est activée
     * 
     * @var bool
     * 
     * @ORM\Column(type="integer")
     */
    private int $notionBudget = Campaign::NOTION_BUDGET_NO;

    /**
     * Budget de la campagne
     * 
     * @var int
     * 
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $budget;

    /**
     * Obtenir le nom tronqué
     * 
     * @return string
     */
    public function getTruncatedName(): string
    {
        return (strlen($this->name) > 50) ? substr($this->name, 0, 50) . '...' : $this->name;
    }

    /**
     * Obtenir la variante via son index
     * 
     * @param int $index
     * 
     * @return Variant|null
     */
    public function getVariantByIndex($index = 0): ?Variant
    {
        return $this->getVariants()->get($index);
    }

    /**
     * Obtenir la liste des noms des variants
     * 
     * @return array<string>
     */
    public function getVariantsList(): array
    {
        $return = [];
        foreach ($this->getVariants() as $variant) {
            $return[] = $variant->getName();
        }
        return $return;
    }

    /**
     * Obtenir l'index du variant sélectionné
     * 
     * @return int|null
     */
    public function getChosenVariantIndex(): ?int
    {
        if ($this->getChosenVariant())
            return $this->getVariants()->indexOf($this->getChosenVariant());
        return null;
    }

    /**
     * Statut de la campagne
     * 
     * @return string
     * 
     * @Serializer\Groups({"campaign:view", "stats"})
     */
    public function getStatus(): string
    {
        $now = new \Datetime();
        if ($this->getDateCreation()->diff($now)->days > 365)
            return 'ARCHIVED';
        else if ($this->getDateEnd() < $now)
            return 'FINISHED';
        else if ($this->completed)
            return 'COMPLETED';
        return 'STARTED';
    }

    /**
     * Obtenir le libellé du statut de la campagne
     * 
     * @Serializer\Groups({"campaign:view"})
     */
    public function getStatusTranslated(): string
    {
        return \App\Utils\LabelHelpers::getCampaignStatuses()[$this->getStatus()];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->dateCreation = new \Datetime();
        $this->dateStart = new \Datetime();
        $this->variants = new ArrayCollection();
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
     * @return Organism|null
     */
    public function getOrganism(): ?Organism
    {
        return $this->organism;
    }

    /**
     * @param Organism|null $organism
     * 
     * @return self
     */
    public function setOrganism(?Organism $organism): self
    {
        $this->organism = $organism;

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
     * @return \DateTimeInterface|null
     */
    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    /**
     * @param \DateTimeInterface|null $dateStart
     * 
     * @return self
     */
    public function setDateStart(?\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    /**
     * @param \DateTimeInterface|null $dateEnd
     * 
     * @return self
     */
    public function setDateEnd(?\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     * 
     * @return self
     */
    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    /**
     * @param bool $completed
     * 
     * @return self
     */
    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * @param User|null $author
     * 
     * @return self
     */
    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection|Variant[]
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    /**
     * @param Variant $variant
     * 
     * @return self
     */
    public function addVariant(Variant $variant): self
    {
        if (!$this->variants->contains($variant)) {
            $this->variants[] = $variant;
            $variant->setCampaign($this);
        }

        return $this;
    }

    /**
     * @param Variant $variant
     * 
     * @return self
     */
    public function removeVariant(Variant $variant): self
    {
        if ($this->variants->removeElement($variant)) {
            // set the owning side to null (unless already changed)
            if ($variant->getCampaign() === $this) {
                $variant->setCampaign(null);
            }
        }

        return $this;
    }

    /**
     * @return Variant|null
     */
    public function getChosenVariant(): ?Variant
    {
        return $this->chosenVariant;
    }

    /**
     * @param Variant|null $chosenVariant
     * 
     * @return self
     */
    public function setChosenVariant(?Variant $chosenVariant): self
    {
        $this->chosenVariant = $chosenVariant;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getPhases(): ?array
    {
        if ($this->phases === null) {
            return ['conception', 'production', 'diffusion'];
        }
        return $this->phases;
    }

    /**
     * @param array|null $phases
     * 
     * @return self
     */
    public function setPhases(?array $phases): self
    {
        $this->phases = $phases;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getHasNotionMediaEfficiency(): ?bool
    {
        return $this->hasNotionMediaEfficiency;
    }

    /**
     * @param bool $hasNotionMediaEfficiency
     * 
     * @return self
     */
    public function setHasNotionMediaEfficiency(bool $hasNotionMediaEfficiency): self
    {
        $this->hasNotionMediaEfficiency = $hasNotionMediaEfficiency;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getNotionBudget(): ?int
    {
        return $this->notionBudget;
    }

    /**
     * @param int $notionBudget
     * 
     * @return self
     */
    public function setNotionBudget(int $notionBudget): self
    {
        $this->notionBudget = $notionBudget;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getBudget(): ?int
    {
        return $this->budget;
    }

    /**
     * @param int|null $budget
     * 
     * @return self
     */
    public function setBudget(?int $budget): self
    {
        $this->budget = $budget;

        return $this;
    }
}
