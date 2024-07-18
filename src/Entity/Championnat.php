<?php
// src/Entity/Championnat.php
namespace App\Entity;

use App\Repository\ChampionnatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChampionnatRepository::class)]
class Championnat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $country = null;

    #[ORM\Column(length: 50)]
    private ?string $ligue = null;

    #[ORM\Column(length: 80)]
    private ?string $code_api = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getLigue(): ?string
    {
        return $this->ligue;
    }

    public function setLigue(string $ligue): self
    {
        $this->ligue = $ligue;

        return $this;
    }

    public function getCodeApi(): ?string
    {
        return $this->code_api;
    }

    public function setCodeApi(string $code_api): self
    {
        $this->code_api = $code_api;

        return $this;
    }
}
