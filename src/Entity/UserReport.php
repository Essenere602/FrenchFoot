<?php

namespace App\Entity;

use App\Repository\UserReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserReportRepository::class)]
class UserReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $reason = null;

    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reportingUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reportedUser = null;

    #[ORM\Column(type: 'boolean')]
    private bool $archived = false;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getReportingUser(): ?User
    {
        return $this->reportingUser;
    }

    public function setReportingUser(?User $reportingUser): self
    {
        $this->reportingUser = $reportingUser;

        return $this;
    }

    public function getReportedUser(): ?User
    {
        return $this->reportedUser;
    }

    public function setReportedUser(?User $reportedUser): self
    {
        $this->reportedUser = $reportedUser;

        return $this;
    }
    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;
        return $this;
    }
}
