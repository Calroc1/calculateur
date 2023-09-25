<?php

namespace App\Form\Support;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type;

use App\Entity\Support\Referential;
use App\Entity\Support\Support;
use App\Entity\Support\Type as SupportTypeEntity;

/**
 * FormType pour support
 */
class SupportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', Type\TextType::class, [
                'label' => "Nom",
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('color', Type\ColorType::class, [
                'label' => "Couleur",
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('displayName', Type\CheckboxType::class, [
                'label' => "Afficher le nom sur le tableau des supports de communication - Front"
            ])
            ->add('type', EntityType::class, [
                'label' => "Type de support",
                'class' => SupportTypeEntity::class,
                'choice_label' => 'name',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('referential', EntityType::class, [
                'label' => "Référentiel",
                'class' => Referential::class,
                'choice_label' => 'name',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Support::class,
        ]);
    }
}
