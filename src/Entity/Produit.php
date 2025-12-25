<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProduitRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive(message: 'Le prix doit être un nombre positif.')]
    private ?float $prix = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero(message: 'La quantité doit être un entier positif.')]
    private ?int $stock = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero(message: 'Le seuil d\'alerte doit être un entier positif.')]
    private ?int $seuilAlerte = 5;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: MouvementStock::class)]
    private $mouvementsStock;

    public function __construct()
    {
        $this->mouvementsStock = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }

    public function getSeuilAlerte(): ?int
    {
        return $this->seuilAlerte;
    }

    public function setSeuilAlerte(int $seuilAlerte): self
    {
        $this->seuilAlerte = $seuilAlerte;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getMouvementsStock()
    {
        return $this->mouvementsStock;
    }

    public function ajouterStock(int $quantite, string $type = 'entrée', ?string $commentaire = null): self
    {
        if ($quantite < 0) {
            throw new \InvalidArgumentException('La quantité doit être positive pour un ajout de stock.');
        }

        $this->stock += $quantite;

        $mouvement = new MouvementStock();
        $mouvement->setProduit($this)
            ->setQuantite($quantite)
            ->setType($type)
            ->setCommentaire($commentaire)
            ->setDate(new \DateTime());

        $this->mouvementsStock->add($mouvement);

        return $this;
    }

    public function retirerStock(int $quantite, string $type = 'sortie', ?string $commentaire = null): self
    {
        if ($quantite < 0) {
            throw new \InvalidArgumentException('La quantité doit être positive pour un retrait de stock.');
        }

        if ($this->stock < $quantite) {
            throw new \InvalidArgumentException('Stock insuffisant.');
        }

        $this->stock -= $quantite;

        $mouvement = new MouvementStock();
        $mouvement->setProduit($this)
            ->setQuantite(-$quantite)
            ->setType($type)
            ->setCommentaire($commentaire)
            ->setDate(new \DateTime());

        $this->mouvementsStock->add($mouvement);

        return $this;
    }

    public function estEnAlerte(): bool
    {
        return $this->stock <= $this->seuilAlerte;
    }
}
