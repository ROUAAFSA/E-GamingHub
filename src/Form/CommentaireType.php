<?php

namespace App\Form;

use App\Entity\Commentaire;
use App\Entity\Post;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu')
            ->add('auteur');

        if ($options['post_field_enabled']) {
            $builder->add('post', EntityType::class, [
                'class' => Post::class,
                'choice_label' => 'titre',
                'label' => 'Post associé',
            ]);
        } else {
            $builder->add('post', EntityType::class, [
                'class' => Post::class,
                'choice_label' => 'titre',
                'label' => 'Post associé',
                'disabled' => true, // Disable the field to prevent changes
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
            'post_field_enabled' => true, // Default to enabled
        ]);

        $resolver->setAllowedTypes('post_field_enabled', 'bool');
    }
}