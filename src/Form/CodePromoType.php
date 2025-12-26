<?php

namespace App\Form;

use App\Entity\CodePromo;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CodePromoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => ['placeholder' => 'SOLDES2024'],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('montantFixe', MoneyType::class, [
                'label' => 'Montant fixe',
                'required' => false,
                'currency' => 'EUR',
                'help' => 'Laissez vide si vous utilisez un pourcentage',
            ])
            ->add('pourcentage', NumberType::class, [
                'label' => 'Pourcentage',
                'required' => false,
                'help' => 'Laissez vide si vous utilisez un montant fixe',
            ])
            ->add('nbUtilisationsMax', IntegerType::class, [
                'label' => 'Nombre maximum d\'utilisations',
                'required' => false,
                'help' => 'Laissez vide pour un nombre illimité',
            ])
            ->add('nbUtilisationsParUtilisateur', IntegerType::class, [
                'label' => 'Nombre maximum d\'utilisations par utilisateur',
                'required' => false,
                'help' => 'Laissez vide pour un nombre illimité',
            ])
            ->add('dateExpiration', DateTimeType::class, [
                'label' => 'Date d\'expiration',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('montantMinimumPanier', MoneyType::class, [
                'label' => 'Montant minimum du panier',
                'required' => false,
                'currency' => 'EUR',
            ])
            ->add('produitSpecifique', EntityType::class, [
                'label' => 'Produit spécifique',
                'class' => Produit::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Tous les produits',
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CodePromo::class,
        ]);
    }
} 