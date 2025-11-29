<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\ReclamationRepository;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
#[ORM\Table(name: 'reclamation')]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_creation = null;

    /**
     * @deprecated Utilisez getDateCreation() à la place
     */
    public function getDate_creation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    /**
     * @deprecated Utilisez setDateCreation() à la place
     */
    public function setDate_creation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    /**
     * Getter pour obtenir la date de création
     */
    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    /**
     * Setter pour définir la date de création
     */
    public function setDateCreation(\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reponse = null;

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(?string $reponse): self
    {
        $this->reponse = $reponse;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $archived = null;

    public function isArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(?bool $archived): self
    {
        $this->archived = $archived;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image_path = null;

    public function getImage_path(): ?string
    {
        return $this->image_path;
    }

    public function setImage_path(?string $image_path): self
    {
        $this->image_path = $image_path;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $pdf_path = null;

    public function getPdf_path(): ?string
    {
        return $this->pdf_path;
    }

    public function setPdf_path(?string $pdf_path): self
    {
        $this->pdf_path = $pdf_path;
        return $this;
    }

    #[ORM\Column(name: 'utilisateur_id', type: 'integer', nullable: true)]
    private ?int $utilisateur_id = null;

    public function getUtilisateurId(): ?int
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateurId(?int $utilisateur_id): self
    {
        $this->utilisateur_id = $utilisateur_id;
        return $this;
    }

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La catégorie ne peut pas être vide")]
    private ?string $categorie = null;

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: 'reclamation')]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
        $this->date_creation = new \DateTime();
        $this->statut = 'En attente';
        $this->archived = false;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        if (!$this->reponses instanceof Collection) {
            $this->reponses = new ArrayCollection();
        }
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): self
    {
        if (!$this->getReponses()->contains($reponse)) {
            $this->getReponses()->add($reponse);
        }
        return $this;
    }

    public function removeReponse(Reponse $reponse): self
    {
        $this->getReponses()->removeElement($reponse);
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->image_path;
    }

    public function setImagePath(?string $image_path): static
    {
        $this->image_path = $image_path;

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdf_path;
    }

    public function setPdfPath(?string $pdf_path): static
    {
        $this->pdf_path = $pdf_path;

        return $this;
    }

}
