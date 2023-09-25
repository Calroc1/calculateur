<?php

namespace App\Form\Campaign;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Form\Campaign\FieldType;

use App\Entity\Campaign\Variant;
use App\Entity\Support\FormElement;

/**
 * FormType pour "demande spéciale"
 */
class RequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {             
        $collection = new FormElement();
        $collection->setType('collection');
        $collection->setconfig([
            'unit' => 'demande'
        ]);
        $title = new FormElement();
        $title->setType('text');
        $title->setName('title');
        $title->setLabel('Nommez votre demande');
        $collection->addChild($title);
        $content = new FormElement();
        $content->setType('textarea');
        $content->setName('content');
        $content->setLabel('Votre demande');
        $collection->addChild($content);
        $builder->add('additionnalRequests', FieldType::class, [
            'label' => 'Déposer une requête',
            'form_element' => $collection,
            'lvl' => 2
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Variant::class
        ]);
    }
}
