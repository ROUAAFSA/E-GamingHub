<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoBadWordsValidator extends ConstraintValidator
{
    private const BAD_WORDS = [
        'merde', 'putain', 'connard', 'salope', 'pute', 'enculé', 'bite', 'couille',
        'fuck', 'shit', 'bitch', 'ass', 'dick', 'bastard', 'cunt',
        'nique', 'connasse', 'salaud', 'pétasse', 'bordel', 'enfoiré',
        // Ajoutez d'autres mots inappropriés selon vos besoins
    ];

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NoBadWords) {
            throw new UnexpectedTypeException($constraint, NoBadWords::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $foundBadWords = [];
        foreach (self::BAD_WORDS as $badWord) {
            if (stripos($value, $badWord) !== false) {
                $foundBadWords[] = $badWord;
            }
        }

        if (!empty($foundBadWords)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', implode(', ', $foundBadWords))
                ->addViolation();
        }
    }
} 