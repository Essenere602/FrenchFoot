<?php
namespace App\Entity;

use App\Repository\BlockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlockRepository::class)]
class Block
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'blocksInitiated')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $blocker = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'blocksReceived')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $blocked = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlocker(): ?User
    {
        return $this->blocker;
    }

    public function setBlocker(?User $blocker): self
    {
        $this->blocker = $blocker;

        return $this;
    }

    public function getBlocked(): ?User
    {
        return $this->blocked;
    }

    public function setBlocked(?User $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }
}
