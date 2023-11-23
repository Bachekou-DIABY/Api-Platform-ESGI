<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    private ?int $siren = null;
    private ?int $siret = null;
    private ?String $raisonSociale = null;
    private ?String $adresse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setRaisonSociale(String $raisonSociale): void
    {
        $this->raisonSociale = $raisonSociale;
    }

    public function getRaisonSociale(): ?String
    {
        return $this->raisonSociale;
    }

    public function setSiren(String $siren): void
    {
        $this->siren = $siren;
    }

    public function getSiren(): ?int
    {
        return $this->siren;
    }

    public function setSiret(String $siret): void
    {
        $this->siret = $siret;
    }

    public function getSiret(): ?int
    {
        return $this->siret;
    }

    public function setAdresse(String $adresse): void
    {
        $this->adresse = $adresse;
    }

    public function getAdresse(): ?String
    {
        return $this->adresse;
    }
}
