<?php

namespace App\Form;

use App\Entity\Organism;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Formulaire pour organisme
 */
class OrganismType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $alpha = 'A';
        $builder
            ->add('name', Type\TextType::class, [
                'label' => ($alpha++)." - Nom de l'organisation",
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('email', Type\EmailType::class, [
                'label' => ($alpha++).' - Email',
                'constraints' => [
                    new Assert\Email()
                ]
            ]) 
            ->add('phone', Type\TextType::class, [
                'label' => ($alpha++).' - Téléphone'
            ])            
            ->add('address', Type\TextType::class, [
                'label' => ($alpha++).' - Adresse'
            ])
            ->add('city', Type\TextType::class, [
                'label' => ($alpha++).' - Ville'
            ])
            ->add('postalCode', Type\TextType::class, [
                'label' => ($alpha++).' - Code postal'
            ])            
            ->add('country', Type\CountryType::class, [
                'label' => ($alpha++).' - Pays'
            ])
            /*->add('users', Type\CollectionType::class, [
                'entry_type' => UserType::class,
                'block_name' => 'user',
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_data' => new User()
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Organism::class,
        ]);
    }
}
