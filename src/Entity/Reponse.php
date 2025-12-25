<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\ReponseRepository;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
#[ORM\Table(name: 'reponse')]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\ManyToOne(targetEntity: Reclamation::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(name: 'reclamation_id', referencedColumnName: 'id')]
    private ?Reclamation $reclamation = null;

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): self
    {
        $this->reclamation = $reclamation;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    #[Assert\NotBlank(message: "Le contenu de la réponse ne peut pas être vide")]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "La réponse doit contenir au moins {{ limit }} caractères",
        maxMessage: "La réponse ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $contenu = null;

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
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

    public function __construct()
    {
        $this->date_creation = new \DateTime();
    }
}
