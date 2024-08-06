<?php
// src/Entity/User.php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Block;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private ?string $username = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $email = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserProfile $userProfile = null;

    #[ORM\OneToOne(targetEntity: UserBanned::class, mappedBy:"user", cascade: ["persist", "remove"])]
    private ?UserBanned $userBanned = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\OneToMany(mappedBy: 'blocker', targetEntity: Block::class, cascade: ['persist', 'remove'])]
    private Collection $blocksInitiated;

    #[ORM\OneToMany(mappedBy: 'blocked', targetEntity: Block::class, cascade: ['persist', 'remove'])]
    private Collection $blocksReceived;

    public function __construct()
    {
        $this->blocksInitiated = new ArrayCollection();
        $this->blocksReceived = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; // Chaque utilisateur a au moins ROLE_USER
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données temporaires ou sensibles sur l'utilisateur, effacez-les ici
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getUserProfile(): ?UserProfile
    {
        return $this->userProfile;
    }

    public function setUserProfile(?UserProfile $userProfile): self
    {
        // Unset the owning side of the relation if necessary
        if ($userProfile === null && $this->userProfile !== null) {
            $this->userProfile->setUser(null);
        }

        // Set the owning side of the relation if necessary
        if ($userProfile !== null && $userProfile->getUser() !== $this) {
            $userProfile->setUser($this);
        }

        $this->userProfile = $userProfile;

        return $this;
    }

    public function getUserBanned(): ?UserBanned
    {
        return $this->userBanned;
    }

    public function setUserBanned(?UserBanned $userBanned): self
    {
        $this->userBanned = $userBanned;
        
        if ($userBanned !== null) {
            $userBanned->setUser($this);
        }
        
        return $this;
    }
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }
    /**
     * @return Collection<int, Block>
     */
    public function getBlocksInitiated(): Collection
    {
        return $this->blocksInitiated;
    }

    public function addBlockInitiated(Block $block): self
    {
        if (!$this->blocksInitiated->contains($block)) {
            $this->blocksInitiated->add($block);
            $block->setBlocker($this);
        }

        return $this;
    }

    public function removeBlockInitiated(Block $block): self
    {
        if ($this->blocksInitiated->removeElement($block)) {
            if ($block->getBlocker() === $this) {
                $block->setBlocker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Block>
     */
    public function getBlocksReceived(): Collection
    {
        return $this->blocksReceived;
    }

    public function addBlockReceived(Block $block): self
    {
        if (!$this->blocksReceived->contains($block)) {
            $this->blocksReceived->add($block);
            $block->setBlocked($this);
        }

        return $this;
    }

    public function removeBlockReceived(Block $block): self
    {
        if ($this->blocksReceived->removeElement($block)) {
            if ($block->getBlocked() === $this) {
                $block->setBlocked(null);
            }
        }

        return $this;
    }
}

