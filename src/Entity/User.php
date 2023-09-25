<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * Utilisateur "front"
 * 
 * @ORM\Entity()
 * @UniqueEntity(fields={"email"})
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"user:edit"})
     */
    private $id;

    /**
     * Statut d'activation de l'utilisateur
     * 
     * @var bool
     * 
     * @ORM\Column(type="boolean")
     */
    private bool $enabled = false;

    /**
     * Email de l'utilisateur
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=180, unique=true)
     * @Serializer\Groups({"user:edit"})
     */
    private string $email;

    /** 
     * Mot de passe de l'utilisateur
     * 
     * @var string|null
     *    
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $password;

    /**
     * Rôles de l'utilisateur
     * 
     * @var array
     * 
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /** 
     * Prénom de l'utilisateur
     * 
     * @var string|null
     *    
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"user:edit"})
     */
    private ?string $firstname;    

    /** 
     * Nom de famille de l'utilisateur
     * 
     * @var string|null
     *    
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"user:edit"})
     */
    private ?string $lastname;   

    /** 
     * Numéro de téléphone de l'utilisateur
     * 
     * @var string|null
     *    
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"user:edit"})
     */
    private ?string $phone;

    /** 
     * Adresse de l'utilisateur
     * 
     * @var string|null
     *    
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"user:edit"})
     */
    private ?string $address;   

    /** 
     * Code postal de l'utilisateur
     * 
     * @var string|null
     *    
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"user:edit"})
     */
    private ?string $postalCode;   

    /** 
     * Ville de l'utilisateur
     * 
     * @var string|null
     *    
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"user:edit"})
     */
    private ?string $city; 

    /** 
     * Date de création de l'utilisateur
     * 
     * @var \Datetime|null
     *    
     * @ORM\Column(type="datetime")
     */
    private ?\DateTime $dateCreation;

    /** 
     * Date de dernière connexion de l'utilisateur  
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\Datetime $dateLastLogin;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"user:edit"})
     */
    private string $status = 'SUPERVISOR';  

    /**
     * Organisme (entreprise ou organisation) de l'utilisateur
     * 
     * @var Organism|null
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\Organism", inversedBy="users")
     * @ORM\JoinColumn(name="organism_id", referencedColumnName="id")
     */
    private ?Organism $organism = null;

    /**
     * Token utile pour invitation de l'utilisateur sur la plateforme
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $tokenInvitation;

    /**
     * Token utile pour réinitialisation de mot de passe de l'utilisateur
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $tokenPassword;

    private $theOrganism;

    /**
     * Phase autorisées pour l'utilisateur lors de la consultation d'une campagne
     * 
     * @var array|null
     * 
     * @ORM\Column(type="json", nullable=true)
     * @Serializer\Groups({"variant:view"})
     */
    private ?array $phases = ['conception', 'production', 'diffusion'];

    public function __construct()
    {
        $this->dateCreation = new \Datetime();
        $this->tokenInvitation = \App\Utils\Utils::generateToken();
    }

    public function __toString()
    {
        return $this->getFirstname().' '.$this->getLastname();
    }

    /**
     * Permet d'obtenir le libellé du statut d'une utilisateur
     * 
     * @return string
     */
    public function getStatusTranslated():string{
        return \App\Utils\LabelHelpers::getUserStatuses()[$this->getStatus()];
    }

    /**
     * Permet de vérifier si l'utilisateur est bien lié à un organisme
     * 
     * @param mixed $organism
     * @param bool $childrenOnly
     * 
     * @return bool
     */
    public function containsOrganism($organism, $childrenOnly = false) :bool{
        $contains = function($organism, $userOrganism, $childrenOnly = false) use ( &$contains ) {
            if(!$childrenOnly && $userOrganism == $organism)
                return true;
            foreach($userOrganism->getChildren() as $c){
                if($contains($organism, $c))
                    return true;
            }
            return false;
        };
        return $contains($organism, $this->getOrganism(), $childrenOnly);
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
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * 
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        $roles[] = 'ROLE_'.$this->status;
        return array_unique($roles);
    }

    /**
     * @param array $roles
     * 
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    /**
     * @param string $password
     * 
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        //$this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param string|null $firstname
     * 
     * @return self
     */
    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param string|null $lastname
     * 
     * @return self
     */
    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

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
    public function getDateLastLogin(): ?\DateTimeInterface
    {
        return $this->dateLastLogin;
    }

    /**
     * @param \DateTimeInterface|null $dateLastLogin
     * 
     * @return self
     */
    public function setDateLastLogin(?\DateTimeInterface $dateLastLogin): self
    {
        $this->dateLastLogin = $dateLastLogin;

        return $this;
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
     * @return string|null
     */
    public function getTokenInvitation(): ?string
    {
        return $this->tokenInvitation;
    }

    /**
     * @param string|null $tokenInvitation
     * 
     * @return self
     */
    public function setTokenInvitation(?string $tokenInvitation): self
    {
        $this->tokenInvitation = $tokenInvitation;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTokenPassword(): ?string
    {
        return $this->tokenPassword;
    }

    /**
     * @param string|null $tokenPassword
     * 
     * @return self
     */
    public function setTokenPassword(?string $tokenPassword): self
    {
        $this->tokenPassword = $tokenPassword;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * 
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

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
     * @return array|null
     */
    public function getPhases(): ?array
    {
        if($this->phases === null){
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
     * @param mixed $phase
     * 
     * @return bool
     */
    public function hasPhase($phase) :bool{
        return $this->phases === null || in_array($phase, $this->getPhases());
    }

    public function getOrganism(): ?Organism
    {
        return $this->organism;
    }

    public function setOrganism(?Organism $organism): self
    {
        $this->organism = $organism;

        return $this;
    }
}
