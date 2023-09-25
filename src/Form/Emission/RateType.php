<?php

namespace App\Form\Emission;

use App\Entity\Emission\Rate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type;

/**
 * FormType pour facteur d'émission
 */
class RateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', Type\TextType::class, [
                'label' => 'Nom'
            ])
            ->add('unit', Type\TextType::class, [
                'label' => 'Unité'
            ])
            ->add('comment', Type\TextareaType::class, [
                'label' => 'Remarque'
            ])
            ->add('source', Type\TextareaType::class, [
                'label' => 'Source'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rate::class,
        ]);
    }
}
