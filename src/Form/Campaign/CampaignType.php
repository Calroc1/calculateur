<?php

namespace App\Form\Campaign;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use App\Entity\Campaign\Campaign;
use App\Entity\Organism;

/**
 * FormType pour configuration d'une campagne
 */
class CampaignType extends AbstractType
{
    private $em;
    protected $tokenStorage;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaign = $options['data'];
        $variant = $options['variant'];
        $isMasterVariant = !$variant || $variant->isMaster();
        $user = $this->tokenStorage->getToken()->getUser();
        $userOrganism = $this->tokenStorage->getToken()->getUser()->getOrganism();

        $letter = 'A';
        // ----- organisation
        if ($userOrganism->getLvl() == 0) {
            $builder->add('organism', EntityType::class, [
                'label' => ($letter++) . " - Organisation",
                'placeholder' => 'Pour qui est cette campagne',
                'class' => Organism::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->findUserOrganisations($user, true);
                },
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'disabled' => !$isMasterVariant
            ]);
        } else if (!$options['edit']) {
            $campaign->setOrganism($userOrganism);
        }
        // ---- champs basiques
        $builder
            ->add('name', Type\TextType::class, [
                'label' => ($letter++) . ' - Nom de la campagne',
                'attr' => [
                    'placeholder' => 'Entrer un nom de campagne',
                ],
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'disabled' => !$isMasterVariant
            ])
            ->add('dateStart', Type\DateType::class, [
                'label' => ($letter++) . ' - Date de début estimée de la campagne',
                'widget' => 'single_text',
                //'format' => 'dd-MM-yyyy',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'disabled' => !$isMasterVariant
            ])
            ->add('dateEnd', Type\DateType::class, [
                'label' => ($letter++) . ' - Date de fin estimée de la campagne',
                'widget' => 'single_text',
                //'format' => 'dd-MM-yyyy',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'disabled' => !$isMasterVariant
            ])
            ->add('country', Type\CountryType::class, [
                'label' => ($letter++) . ' - Pays de destination de la campagne',
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'disabled' => !$isMasterVariant
            ]);
        // -- phases
        $builder->add('phases', Type\ChoiceType::class, [
            'label' => ($letter++) . ' - Phases actives',
            'expanded' => true,
            'multiple' => true,
            'choices' => [
                'Conception' => 'conception',
                'Production' => 'production',
                'Diffusion' => 'diffusion'
            ],
            'disabled' => !$isMasterVariant,
            'constraints' => [
                new Assert\NotBlank()
            ]
        ]);
        // -- etapes      
        $builder->add('steps', CampaignStepsType::class, [
            'mapped' => false,
            'label' => ($letter++) . ' - Supports de diffusion'
        ]);
        // duplication
        if (!$options['edit'] && count($this->em->getRepository(Campaign::class)->findUserCampaigns($user)) > 0) {
            $builder
                ->add('duplication', Type\ChoiceType::class, [
                    'mapped' => false,
                    'data' => false,
                    'expanded' => true,
                    'label' => ($letter++) . ' - Dupliquer une campagne existante ?',
                    'choices' => [
                        'Oui' => true,
                        'Non' => false,
                    ],
                    'constraints' => [
                        new Assert\NotNull()
                    ]
                ]);
        }
        // FORM EVENTS        
        if ($variant) {
            $builder->get('steps')->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($variant) {
                $event->setData($variant->getSupports());
            });
            $builder->get('steps')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($variant) {
                $newSupports = $event->getData();
                foreach($newSupports as $support){
                    $variant->addSupport($support);
                }       
                foreach($variant->getSupports() as $support){
                    if(!in_array($support, $newSupports))
                        $variant->removeSupport($support);
                }         
            });
        }
        $builder
            ->add('hasNotionMediaEfficiency', Type\ChoiceType::class, [
                'expanded' => true,
                'label' => ($letter++) . ' - Intégrer la notion de performance média (nombre de contact par média)',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'constraints' => [
                    new Assert\NotNull()
                ],
                'disabled' => !$isMasterVariant
            ]);

        if ($user->getStatus() == 'SUPERVISOR') {
            $builder
                ->add('notionBudget', Type\ChoiceType::class, [
                    'expanded' => true,
                    'label' => ($letter++) . ' - Intégrer la notion de budget ?',
                    'choices' => Campaign::NOTION_BUDGET,
                    'constraints' => [
                        new Assert\NotNull()
                    ],
                    'disabled' => !$isMasterVariant
                ])
                ->add('budget', Type\IntegerType::class, [
                    'label' => '',
                    'attr' => [
                        'placeholder' => 'Indiquer le budget associé à la campagne'
                    ],
                    'disabled' => !$isMasterVariant
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Campaign::class,
            'edit' => 0,
            'variant' => null
        ]);
    }
}
