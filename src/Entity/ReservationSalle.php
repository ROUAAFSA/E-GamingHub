<?php

namespace App\Entity;

use App\Repository\ReservationSalleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Utilisateur;
use App\Entity\Salle;

#[ORM\Entity(repositoryClass: ReservationSalleRepository::class)]
#[ORM\Table(name: 'reservationssalle')]
class ReservationSalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner une salle')]
    private ?Salle $salle = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: 'La date de début est obligatoire')]
    #[Assert\GreaterThanOrEqual(
        'today',
        message: 'La date de début doit être aujourd\'hui ou ultérieure'
    )]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: 'La date de fin est obligatoire')]
    #[Assert\GreaterThan(
        propertyPath: 'dateDebut',
        message: 'La date de fin doit être postérieure à la date de début'
    )]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'En attente';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalle(): ?Salle
    {
        return $this->salle;
    }

    public function setSalle(?Salle $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
