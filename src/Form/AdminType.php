<?php

namespace App\Form;

use App\Entity\Admin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type;

use App\Form\PasswordType;

/**
 * Formulaire pour admin
 */
class AdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', Type\EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email()
                ]
            ])
            ->add('firstname', Type\TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('lastname', Type\TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])            
            ->add('super', Type\ChoiceType::class, [
                'expanded' => true,
                'label' => 'Superadmin', 
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'constraints' => [
                    new Assert\NotNull()
                ]
            ])
        ;
        if ($options['with_password']) {
            $builder->add('password', PasswordType::class, [
                'mapped' => false
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
            'with_password' => false,
        ]);
    }
}
