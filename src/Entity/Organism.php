<?php

namespace App\Entity;

use App\Entity\Campaign\Campaign;
use App\Repository\OrganismRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\Criteria;

use Symfony\Component\Intl\Countries;

/**
 * Organisme (organisation ou entreprise)
 * 
 * @ORM\Entity(repositoryClass=OrganismRepository::class)
 */
class Organism
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"organism:edit", "stats"})
     */
    private $id;

    /**
     * Niveau de l'organisme dans la hiérarchie (0 = entreprise / 1 = organisation)
     * 
     * @var int
     * 
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"organism:edit"})
     */
    private int $lvl = 0;

    /**
     * Date de création de l'organisme
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime")
     */
    private ?\Datetime $dateCreation;

    /**
     * Nom de l'organisme
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"organism:edit", "stats"})
     */
    private string $name;

    /**
     * Téléphone de l'organisme
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"organism:edit"})
     */
    private ?string $phone;

    /**
     * Email de l'organisme
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=180, nullable=true)
     * @Serializer\Groups({"organism:edit"})
     */
    private ?string $email;

    /**
     * Adresse de l'organisme
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"organism:edit"})
     */
    private ?string $address;

    /**
     * Code postal de l'organisme
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"organism:edit"})
     */
    private ?string $postalCode;

    /**
     * Ville de l'organisme
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"organism:edit"})
     */
    private ?string $city;

    /**
     * Code pays de l'organisme (ISO 3166-1 alpha-2)
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"organism:edit"})
     */
    private ?string $country = 'FR';

    /**
     * Organisme parent (si organisation)
     * 
     * @var Organism|null
     * 
     * @ORM\ManyToOne(targetEntity="Organism", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Organism $parent;

    /**    
     * Organismes enfants (si entreprise)
     *
     * @var Collection<int, Organism>
     *  
     * @ORM\OneToMany(targetEntity="Organism", mappedBy="parent", cascade={"remove"})
     */
    private Collection $children;

    /**
     * Utilisateurs liés à l'organisme
     * 
     * @var Collection<int, User>
     * 
     * @ORM\OneToMany(targetEntity="User", mappedBy="organism", cascade={"remove"})
     */
    private Collection $users;

    /**     
     * Campagnes liées à l'organisme
     * 
     * @var Collection<int, Campaign>
     * 
     * @ORM\OneToMany(targetEntity="App\Entity\Campaign\Campaign", mappedBy="organism", cascade={"remove"})
     * @ORM\OrderBy({"dateStart" = "DESC"})
     */
    private Collection $campaigns;

    /**
     * Permet de récupérer le nombre de campagnes
     * 
     * @Serializer\Groups({"organism:edit"})
     */
    public function getCountCampaigns(): int
    {
        return $this->getCampaigns()->count();
    }

    /**
     * Permet de récupérer le tableau des pays couverts par les campagnes de l'organisme
     * 
     * @Serializer\Groups({"stats"})
     */
    public function getCountries(): array{
        $countries = [];
        foreach($this->getCampaigns() as $c){
            $countries[$c->getCountry()] = Countries::getName($c->getCountry());
        }
        return $countries;
    }

    public function __construct()
    {
        $this->dateCreation = new \Datetime();
        $this->children = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
    }

    

    public function __serialize(): array
    {
        return ['id' => $this->getId()];
    }
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
    }

    /**
     * Permet de récupérer l'organisme parent le plus élévé
     * 
     * @return Organism
     */
    public function getEldest(): Organism
    {
        $parent = $this->getParent();
        if ($parent)
            return $parent->getEldest();
        return $this;
    }

    /**
     * Permet de récupérer le libellé du type d'organisme
     * 
     * @return string
     */
    public function getType(): string
    {
        if ($this->lvl == 0)
            return 'entreprise';
        return 'organisation';
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
     * @return Collection|Organism[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Organism $child
     * 
     * @return self
     */
    public function addChild(Organism $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    /**
     * @param Organism $child
     * 
     * @return self
     */
    public function removeChild(Organism $child): self
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
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @param User $user
     * 
     * @return self
     */
    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addOrganism($this);
        }

        return $this;
    }

    /**
     * @param User $user
     * 
     * @return self
     */
    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeOrganism($this);
        }

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
     * @return Collection|Campaign[]
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    /**
     * @param Campaign $campaign
     * 
     * @return self
     */
    public function addCampaign(Campaign $campaign): self
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns[] = $campaign;
            $campaign->setOrganism($this);
        }

        return $this;
    }

    /**
     * @param Campaign $campaign
     * 
     * @return self
     */
    public function removeCampaign(Campaign $campaign): self
    {
        if ($this->campaigns->removeElement($campaign)) {
            // set the owning side to null (unless already changed)
            if ($campaign->getOrganism() === $this) {
                $campaign->setOrganism(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string|null $address
     * 
     * @return self
     */
    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @param string|null $postalCode
     * 
     * @return self
     */
    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     * 
     * @return self
     */
    public function setCity(?string $city): self
    {
        $this->city = $city;

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
     * @param string|null $country
     * 
     * @return self
     */
    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     * 
     * @return self
     */
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * 
     * @return self
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
