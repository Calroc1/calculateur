<?php

namespace App\Form;

use App\Entity\Organism;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use App\Form\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * FormType pour utilisateur
 */
class UserType extends AbstractType
{
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $alpha = 'A';
        $builder
            ->add('firstname', Type\TextType::class, [
                'label' => ($alpha++).' - Prénom',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('lastname', Type\TextType::class, [
                'label' => ($alpha++).' - Nom',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('email', Type\EmailType::class, [
                'label' => ($alpha++).' - Email',
                'constraints' => [
                    new Assert\NotBlank(),
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
        ;
        // option "envoyer une invitation" 
        if ($options['with_invitation']) {
            $builder->add('invitation', Type\ChoiceType::class, [
                'expanded' => true,
                'mapped' => false,
                'label' => 'Envoyer une invitation ?', 
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'constraints' => [
                    new Assert\NotNull()
                ]
            ]);
        }
        // option "password"
        if ($options['with_password']) {
            $builder->add('password', PasswordType::class, [
                'mapped' => false
            ]);
        }
        // option "statut"
        if ($options['with_status']) {
            $builder->add('status', Type\ChoiceType::class, [
                'label' => ($alpha++).' - Rôle',
                'choices' => array_flip(\App\Utils\LabelHelpers::getUserStatuses()),
                'constraints' => [
                    new Assert\NotNull()
                ]
            ]);
        }
        // option "organisme"
        if ($options['with_organism']) {
            $userOrganism = $this->tokenStorage->getToken()->getUser()->getOrganism();
            if($userOrganism->getLvl() == 0){
                $builder->add('organism', EntityType::class, [
                    'label' => ($alpha++)." - Entreprise d'appartenance",
                    'class' => Organism::class,
                    'choice_label' => 'name',
                    'query_builder' => function (EntityRepository $er) use ($userOrganism) {
                        return $er->createQueryBuilder('o')
                            ->where('o.lvl = 1')
                            ->andWhere('o.parent = :parent')
                            ->orderBy('o.name', 'ASC')
                            ->setParameter('parent', $userOrganism);
                    },
                    'constraints' => [
                        new Assert\NotNull()
                    ]
                ]);
            }            
        }        
        // option "phases"
        if ($options['with_phases']) {
            // -- phases
            $builder->add('phases', Type\ChoiceType::class, [
                'label' => ($alpha++).' - Phases autorisées',
                'expanded' => true,
                'multiple' => true,
                'choices' => [
                    'Conception' => 'conception',
                    'Production' => 'production',
                    'Diffusion' => 'diffusion'
                ],
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'with_password' => false,
            'with_status' => false,
            'with_organism' => false,
            'with_invitation' => false,
            'with_phases' => false
        ]);
    }
}
