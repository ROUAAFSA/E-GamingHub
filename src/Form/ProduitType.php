<?php
namespace App\Form;

use App\Entity\Produit;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'attr' => ['placeholder' => 'Nom du produit']
            ])
            ->add('description', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Description du produit']
            ])
            ->add('prix', MoneyType::class, [
                'currency' => 'EUR',
                'attr' => ['placeholder' => 'Prix', 'min' => 0, 'step' => '0.01', 'type' => 'number']
            ])
            ->add('stock', NumberType::class, [
                'attr' => ['placeholder' => 'Quantité en stock', 'min' => 0, 'step' => 1, 'type' => 'number']
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => true,
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => '.jpg,.jpeg,.png',
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image au format JPG ou PNG',
                    ])
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Modifier' : 'Ajouter'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            'is_edit' => false
        ]);
    }
} 