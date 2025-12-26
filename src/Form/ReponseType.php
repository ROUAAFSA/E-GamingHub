<?php

namespace App\Form;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer les champs désactivés
        $disabledFields = $options['disabled_fields'] ?? [];
        
        $builder
            ->add('contenu', TextareaType::class, [
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre réponse ici...'
                ],
                'label' => 'Contenu de la réponse',
                'disabled' => in_array('contenu', $disabledFields)
            ]);
            
        // Si réclamation n'est pas désactivé ou présélectionné
        if (!in_array('reclamation', $disabledFields)) {
            $builder->add('reclamation', EntityType::class, [
                'class' => Reclamation::class,
                'choice_label' => function (Reclamation $reclamation) {
                    return sprintf('#%d - %s', $reclamation->getId(), 
                        substr($reclamation->getDescription(), 0, 50) . (strlen($reclamation->getDescription()) > 50 ? '...' : ''));
                },
                'placeholder' => 'Sélectionnez une réclamation',
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
                'label' => 'Réclamation associée',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('r')
                        ->where('r.archived = :archived')
                        ->setParameter('archived', false)
                        ->orderBy('r.date_creation', 'DESC');
                }
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
            'disabled_fields' => [],
        ]);
        
        $resolver->setAllowedTypes('disabled_fields', 'array');
    }
} 