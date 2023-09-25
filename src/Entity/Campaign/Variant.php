<?php

namespace App\Entity\Campaign;

use App\Entity\Support\FormElement;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\SerializedName;
use App\Entity\Support\Support;

/**
 * Variante de campagne
 * 
 * @ORM\Entity()
 * @ORM\Table(name="campaign_variant")
 */
class Variant
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Date de dernière mise à jour de la variante
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime")
     */
    private ?\Datetime $dateCreation;

    /**
     * Date de dernière mise à jour de la variante
     * 
     * @var \Datetime|null
     * 
     * @ORM\Column(type="datetime")
     */
    private ?\Datetime $dateUpdate;

    /**
     * Nom de la variante
     * 
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"variant:view"})
     */
    private ?string $name;    

    /**
     * Demandes spéciales liées à la variante
     * 
     * @var array|null
     * 
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $additionnalRequests = [];

    /**
     * Supports de diffusion associés à la campagne
     * 
     * @var Collection<int, Support>
     * 
     * @ORM\ManyToMany(targetEntity="App\Entity\Support\Support")
     * @ORM\JoinTable(name="campaign_variant_support",
     *      joinColumns={@ORM\JoinColumn(name="variant_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="support_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     */
    private Collection $supports;

    /**
     * Métadonnées liées à la variante
     * 
     * @var array|null
     * 
     * @ORM\Column(type="json")
     */
    private ?array $metadatas = [];
    
    /**
     * Campagne de la variante
     * 
     * @var Campaign|null
     * 
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="variants")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Campaign $campaign = null;

    /**
     * Auteur de la variante
     * 
     * @var User|null
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL", nullable=true)
     */
    private ?User $author;

    /**   
     * Données de formulaire liées à la variante
     * 
     * @var Collection<int, Data>
     *   
     * @ORM\OneToMany(targetEntity="Data", mappedBy="variant", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private Collection $datas; 
    
    /**
     * Permet de récupérer le tableau des noms des supports
     * 
     * @return array
     * 
     * @Serializer\Groups({"variant:view"})
     * @SerializedName("supports")
     */
    public function getSupportsNames(): array
    {
        $return = [];
        foreach($this->getSupports() as $support){
            $return[] = $support->getName();
        }
        return $return;
    }

    /**
     * Permet de savoir si la variante est la variante "master" de la campagne
     * 
     * @return bool
     */
    public function isMaster(): bool
    {
        return $this->getCampaign()->getVariantByIndex(0) == $this;
    }

    /**
     * Permet de savoir si la phase est activée pour la variante
     * 
     * @param string $phase
     * 
     * @return bool
     */
    public function hasPhase(string $phase): bool
    {
        return in_array($phase, $this->getCampaign()->getPhases());
    }

    /**
     * Permet de savoir si le support est activé pour la variante
     * 
     * @param Support $support
     * 
     * @return bool
     */
    public function hasSupport(Support $support): bool
    {
        if($support->getEnabled()){
            return $this->getSupports()->contains($support);
        }
        return false;
    }

    /**
     * Permet d'ajouter des metadonnées à un support de la variante
     * 
     * @param Support $support
     * @param mixed $data
     * 
     * @return void
     */
    public function addStepMetadata(Support $support, $data): void
    {
        $metadatas = $this->getMetadatas();
        $stepMetadata = isset($metadatas[$support->getName()]) ? array_merge($metadatas[$support->getName()], $data) : $data;
        $metadatas[$support->getName()] = $stepMetadata;
        $this->setMetadatas($metadatas);
    }

    /**
     * Obtenir les métadonnées d'un support de la variante
     * 
     * @param Support $support
     * 
     * @return mixed|null
     */
    public function getSupportMetadata(Support $support)
    {
        $metadatas = $this->getMetadatas();
        if(isset($metadatas[$support->getName()]))
            return $metadatas[$support->getName()];
        else 
            return null;
    }

    /**
     * Obtenir les données d'un support de la variante
     * 
     * @param Support $support
     * @param bool $valueOnly Permet de définir si on veut récupérer les valeurs uniquement ou les objets Value
     * @param bool $existsOnly Permet de définir si on veut récupérer les données existantes ou les données par défauts si elles n'existent pas
     * 
     * @return array
     */
    public function getSupportData(Support $support, bool $valueOnly = true, bool $existsOnly = false): array
    {
        $return = [];
        foreach($support->getFormElements() as $c){
            $data = $this->getFieldData($c, $valueOnly, $existsOnly);
            if(!$existsOnly || $data)
                $return[$c->getName()] = $data;
        }
        return $return;
    }

    /**
     * Obtenir les données d'un élement de formulaire de la variante
     * 
     * @param FormElement $field
     * @param bool $valueOnly Permet de définir si on veut récupérer les valeurs uniquement ou les objets Value
     * @param bool $existsOnly Permet de définir si on veut récupérer les données existantes ou les données par défauts si elles n'existent pas
     * 
     * @return ?mixed
     */
    public function getFieldData(FormElement $field, $valueOnly = true, $existsOnly = false)
    {
        if($field->getType() == 'section'){
            $return = [];
            foreach($field->getChildren() as $c){
                $data = $this->getFieldData($c, $valueOnly, $existsOnly);
                if(!$existsOnly || $data)
                    $return[$c->getName()] = $data;
            }
            return $return;
        }
        if($data = $this->getDataByField($field)){
            if($field->getType() == 'collection'){
                $dataCollection = $data->getValue();
                foreach($dataCollection as $dk => $dv){
                    $dataCollection[$dk] = $this->processCollectionEntry($dv, $field);
                }
                $data->setValue(json_encode($dataCollection));
            }    
            return $valueOnly ? $data->getValue() : $data;
        }
        return (!$existsOnly && $valueOnly) ? $field->getDefaultValue() : ($field->getType() == 'collection' ? [] : null);
    }

    /**
     * Procédure permettant de récupérer les données d'un élément de formulaire de type "collection"
     * 
     * @param mixed $data
     * @param FormElement $field
     * 
     * @return mixed
     */
    public function processCollectionEntry($data, FormElement $field)
    {
        $processEntry = function($data, $default) use(&$processEntry){
            $return = $default;
            if(is_array($default)){
                foreach($default as $dk => $dv){
                    if(isset($data[$dk])){
                        $return[$dk] = is_array($dv) ? $processEntry($data[$dk], $dv) : (is_array($data[$dk]) ? $dv : $data[$dk]);
                    }
                }
                // pour entrée "renamable"
                if(isset($data['_name']))
                    $return['_name'] = $data['_name'];
            }
            else if($data)
                $return = $data;
            return $return;
        };
        return $processEntry($data, $field->getDefaultEntry());
    }

    /**
     * Obtenir la donnée d'un élément de formulaire
     * 
     * @param mixed $field
     * 
     * @return Data|null
     */
    public function getDataByField($field): ?Data
    {        
        foreach($this->getDatas() as $data){
            if($data->getField() == $field)
                return $data;
        }            
        return null;
    }

    public function __construct($campaign = null)
    {
        if($campaign)
            $campaign->addVariant($this);

        $count = ($this->getCampaign() && $this->getCampaign()->getVariants()) ? $this->getCampaign()->getVariants()->count() : 0;
        $this->name = ($count > 1) ? 'Version '.($count++) : 'Master';
        $this->dateCreation = new \Datetime();
        $this->dateUpdate = new \Datetime();
        $this->datas = new ArrayCollection();
        $this->supports = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return array|null
     */
    public function getAdditionnalRequests(): ?array
    {
        return $this->additionnalRequests;
    }

    /**
     * @param array|null $additionnalRequests
     * 
     * @return self
     */
    public function setAdditionnalRequests(?array $additionnalRequests): self
    {
        $this->additionnalRequests = $additionnalRequests;

        return $this;
    }

    /**
     * @return Campaign|null
     */
    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    /**
     * @param Campaign|null $campaign
     * 
     * @return self
     */
    public function setCampaign(?Campaign $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return Collection|Data[]
     */
    public function getDatas(): Collection
    {
        return $this->datas;
    }

    /**
     * @param Data $data
     * 
     * @return self
     */
    public function addData(Data $data): self
    {
        if (!$this->datas->contains($data)) {
            $this->datas[] = $data;
            $data->setVariant($this);
        }

        return $this;
    }

    /**
     * @param Data $data
     * 
     * @return self
     */
    public function removeData(Data $data): self
    {
        if ($this->datas->removeElement($data)) {
            // set the owning side to null (unless already changed)
            if ($data->getVariant() === $this) {
                $data->setVariant(null);
            }
        }

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
     * @return array|null
     */
    public function getMetadatas(): ?array
    {
        if($this->metadatas == null)
            return [];
        return $this->metadatas;
    }

    /**
     * @param array|null $metadatas
     * 
     * @return self
     */
    public function setMetadatas(?array $metadatas): self
    {
        $this->metadatas = $metadatas;

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
        }

        return $this;
    }

    public function removeSupport(Support $support): self
    {
        $this->supports->removeElement($support);

        return $this;
    }
}
