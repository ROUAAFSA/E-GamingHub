<?php

namespace App\Entity;

use Couchbase\User;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\AdminRepository;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\Table(name: 'Admin')]
class Admin extends Utilisateur
{

}
