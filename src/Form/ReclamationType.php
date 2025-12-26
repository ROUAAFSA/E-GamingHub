<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        $builder
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 5, 
                    'class' => 'modern-textarea',
                    'placeholder' => 'Décrivez votre problème en détail...',
                    'required' => 'required',
                    'minlength' => 10
                ],
                'label' => 'Description du problème',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La description ne peut pas être vide']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('categorie', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'modern-field',
                    'placeholder' => 'Ex: Technique, Service client, Facturation...',
                    'required' => 'required'
                ],
                'label' => 'Catégorie',
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie ne peut pas être vide'])
                ]
            ])
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF)',
                    ])
                ],
                'label' => 'Image (optionnel)'
            ])
            ->add('pdfFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5120k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
                'label' => 'Document PDF (optionnel)'
            ]);

        // Ajouter le champ statut uniquement en édition
        if ($isEdit) {
            $builder->add('statut', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'En attente',
                    'En cours' => 'En cours',
                    'Résolue' => 'Résolue'
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
                'label' => 'Statut'
            ]);
        }
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
} 