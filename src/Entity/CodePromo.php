<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CodePromoRepository;

#[ORM\Entity(repositoryClass: CodePromoRepository::class)]
class CodePromo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $montantFixe = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?float $pourcentage = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbUtilisationsMax = null;

    #[ORM\Column(type: 'integer')]
    private int $nbUtilisations = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbUtilisationsParUtilisateur = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $montantMinimumPanier = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    private ?Produit $produitSpecifique = null;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getMontantFixe(): ?float
    {
        return $this->montantFixe;
    }

    public function setMontantFixe(?float $montantFixe): self
    {
        $this->montantFixe = $montantFixe;
        return $this;
    }

    public function getPourcentage(): ?float
    {
        return $this->pourcentage;
    }

    public function setPourcentage(?float $pourcentage): self
    {
        $this->pourcentage = $pourcentage;
        return $this;
    }

    public function getNbUtilisationsMax(): ?int
    {
        return $this->nbUtilisationsMax;
    }

    public function setNbUtilisationsMax(?int $nbUtilisationsMax): self
    {
        $this->nbUtilisationsMax = $nbUtilisationsMax;
        return $this;
    }

    public function getNbUtilisations(): int
    {
        return $this->nbUtilisations;
    }

    public function setNbUtilisations(int $nbUtilisations): self
    {
        $this->nbUtilisations = $nbUtilisations;
        return $this;
    }

    public function incrementNbUtilisations(): self
    {
        $this->nbUtilisations++;
        return $this;
    }

    public function getNbUtilisationsParUtilisateur(): ?int
    {
        return $this->nbUtilisationsParUtilisateur;
    }

    public function setNbUtilisationsParUtilisateur(?int $nbUtilisationsParUtilisateur): self
    {
        $this->nbUtilisationsParUtilisateur = $nbUtilisationsParUtilisateur;
        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeInterface $dateExpiration): self
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getMontantMinimumPanier(): ?float
    {
        return $this->montantMinimumPanier;
    }

    public function setMontantMinimumPanier(?float $montantMinimumPanier): self
    {
        $this->montantMinimumPanier = $montantMinimumPanier;
        return $this;
    }

    public function getProduitSpecifique(): ?Produit
    {
        return $this->produitSpecifique;
    }

    public function setProduitSpecifique(?Produit $produitSpecifique): self
    {
        $this->produitSpecifique = $produitSpecifique;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;
        return $this;
    }

    public function estValide(): bool
    {
        if (!$this->actif) {
            return false;
        }

        $maintenant = new \DateTime();
        
        if ($this->dateExpiration !== null && $maintenant > $this->dateExpiration) {
            return false;
        }

        if ($this->nbUtilisationsMax !== null && $this->nbUtilisations >= $this->nbUtilisationsMax) {
            return false;
        }

        return true;
    }

    public function calculerReduction(float $montantPanier): ?float
    {
        if (!$this->estValide()) {
            return null;
        }

        if ($this->montantMinimumPanier !== null && $montantPanier < $this->montantMinimumPanier) {
            return null;
        }

        if ($this->montantFixe !== null) {
            return min($this->montantFixe, $montantPanier);
        }

        if ($this->pourcentage !== null) {
            return ($montantPanier * $this->pourcentage) / 100;
        }

        return null;
    }
} 