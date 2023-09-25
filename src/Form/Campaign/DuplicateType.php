<?php

namespace App\Form\Campaign;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type;

use App\Entity\Campaign\Campaign;

/**
 * FormType pour duplication d'une campagne
 */
class DuplicateType extends AbstractType
{
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $builder             
            ->add('originCampaign', EntityType::class, [
                'label' => "A - Campagne existante",
                'class' => Campaign::class,
                'choice_label' => function ($c) {
                    return $c->getName().' '.$c->getDateEnd()->format('d/m/Y');
                },
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->findUserCampaigns($user, [], null, true);                    
                }
            ])
        ;
        if($options['campaign_data']){
            $builder->add('campaignData', Type\HiddenType::class);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    { 
        $view->vars['with_submit'] = $options['with_submit'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'campaign_data' => false,
            'with_submit' => false
        ]);
    }
}
