<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Administrateur 
 * 
 * @ORM\Entity()
 * @UniqueEntity(fields={"email"})
 */
class Admin implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Email de l'administrateur 
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private string $email;

    /**   
     * Mot de passe de l'administrateur
     *  
     * @var string
     * 
     * @ORM\Column(type="string")
     */
    private string $password;

    /**
     * Rôles de l'administrateur
     * 
     * @var string
     * 
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * Permet de définir si l'administrateur est un superadmin ou non
     * 
     * @var bool
     * 
     * @ORM\Column(type="boolean")
     */
    private bool $super = false;

    /**
     * Prénom de l'administrateur
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $firstname;    

    /**
     * Nom de famille de l'administrateur
     * 
     * @var string|null
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastname;   

    /**
     * Date de création de l'utilisateur  
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime")
     */
    private ?\Datetime $dateCreation;

    /**
     * Date de dernière connexion de l'utilisateur  
     * 
     * @var \Datetime|null 
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\Datetime $dateLastLogin;    

    /**
     * @return bool
     */
    public function hasPhase() :bool{
        return true;
    }

    public function __construct()
    {
        $this->dateCreation = new \Datetime();
    }

    public function __toString()
    {
        return $this->getFirstname().' '.$this->getLastname();
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
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_ADMIN';
        if($this->getSuper())
            $roles[] = 'ROLE_ADMIN_SUPER';
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

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateLastLogin(): ?\DateTimeInterface
    {
        return $this->dateLastLogin;
    }

    public function setDateLastLogin(?\DateTimeInterface $dateLastLogin): self
    {
        $this->dateLastLogin = $dateLastLogin;

        return $this;
    }

    public function getSuper(): ?bool
    {
        return $this->super;
    }

    public function setSuper(bool $super): self
    {
        $this->super = $super;

        return $this;
    }
}
