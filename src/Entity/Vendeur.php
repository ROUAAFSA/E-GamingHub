<?php

namespace App\Entity;

use App\Entity\Utilisateur;
use App\Repository\VendeurRepository;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: VendeurRepository::class)]
#[ORM\Table(name: 'Vendeur')]
class Vendeur extends Utilisateur
{

}
