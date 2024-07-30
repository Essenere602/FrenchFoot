<?php
// src/Entity/UserBanned.php

namespace App\Entity;

use App\Repository\UserBannedRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: UserBannedRepository::class)]
class UserBanned
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $bannedDate = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $numberBan = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPermanentlyBanned = false;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="userBanned", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: "userBanned", cascade: ["persist"])]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBannedDate(): ?\DateTimeInterface
    {
        return $this->bannedDate;
    }

    public function setBannedDate(?\DateTimeInterface $bannedDate): self
    {
        $this->bannedDate = $bannedDate;
        return $this;
    }

    public function getNumberBan(): ?int
    {
        return $this->numberBan;
    }

    public function setNumberBan(?int $numberBan): self
    {
        $this->numberBan = $numberBan;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function isBanned(): bool
{
    if ($this->isPermanentlyBanned) {
        return false; // Un utilisateur définitivement banni ne devrait pas être considéré comme temporairement banni
    }

    if ($this->bannedDate === null) {
        return false;
    }

    $now = new \DateTime();
    $bannedUntil = (clone $this->bannedDate)->modify('+7 days');

    return $now <= $bannedUntil;
}
    public function isPermanentlyBanned(): bool
    {
        return $this->isPermanentlyBanned;
    }

    public function setPermanentlyBanned(bool $isPermanentlyBanned): self
    {
        $this->isPermanentlyBanned = $isPermanentlyBanned;
        return $this;
    }
}
