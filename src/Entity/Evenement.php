<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]

class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne peut pas dépasser 255 caractères')]
    private string $titre;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'La description ne peut pas être vide')]
    #[Assert\Length(max: 255, maxMessage: 'La description ne peut pas dépasser 255 caractères')]
    private string $description;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Le nom de l\'image ne peut pas dépasser 255 caractères')]
    private ?string $imageName = null;

    #[ORM\Column(type: 'string', length: 30)]
    #[Assert\NotBlank(message: 'La catégorie ne peut pas être vide')]
    #[Assert\Length(max: 30, maxMessage: 'La catégorie ne peut pas dépasser 30 caractères')]
    private string $categorie;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThanOrEqual(0, message: 'Le nombre de likes ne peut pas être négatif')]
    private int $likeCount = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $signaled = false;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThanOrEqual(0, message: 'Le nombre de dislikes ne peut pas être négatif')]
    private int $dislikeCount = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $enable = true;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Commentaire::class, cascade: ['persist', 'remove'])]
    private Collection $commentaires;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;
        return $this;
    }

    public function getCategorie(): string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLikeCount(): int
    {
        return $this->likeCount;
    }

    public function setLikeCount(int $likeCount): self
    {
        $this->likeCount = $likeCount;
        return $this;
    }

    public function isSignaled(): bool
    {
        return $this->signaled;
    }

    public function setSignaled(bool $signaled): self
    {
        $this->signaled = $signaled;
        return $this;
    }

    public function getDislikeCount(): int
    {
        return $this->dislikeCount;
    }

    public function setDislikeCount(int $dislikeCount): self
    {
        $this->dislikeCount = $dislikeCount;
        return $this;
    }

    public function isEnable(): bool
    {
        return $this->enable;
    }

    public function setEnable(bool $enable): self
    {
        $this->enable = $enable;
        return $this;
    }

    /**
     * @return Collection|Commentaire[]
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires[] = $commentaire;
            $commentaire->setPost($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getPost() === $this) {
                $commentaire->setPost(null);
            }
        }

        return $this;
    }
}