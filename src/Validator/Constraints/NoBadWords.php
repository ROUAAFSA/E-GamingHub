<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoBadWords extends Constraint
{
    public string $message = 'Le texte contient des mots inappropriés : {{ value }}. Veuillez rester respectueux.';

    public function __construct(string $message = null, array $groups = null, mixed $payload = null)
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }
} 