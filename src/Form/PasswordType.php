<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as BasePasswordType;

use App\Validator\PasswordRule;

/**
 * FormType pour mot de passe
 */
class PasswordType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'type' => BasePasswordType::class,
            'first_options'  => [
                'label' => 'Mot de passe'
            ],
            'second_options' => [
                'label' => 'Confirmation du mot de passe'
            ],
            'invalid_message' => 'Les mots de passe doivent être identiques.',
            'constraints' => [
                new Assert\NotNull(),
                new PasswordRule()
            ]
        ]);
    }

    public function getParent()
    {
        return RepeatedType::class;
    }
}