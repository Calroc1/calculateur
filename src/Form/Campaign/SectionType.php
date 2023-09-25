<?php

namespace App\Form\Campaign;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use App\Entity\Support\FormElement;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Security;

/**
 * FormType pour élément de formulaire de type "section"
 */
class SectionType extends AbstractType
{
    protected ?FormElement $section; // élément de formulaire concerné
    protected $lvl = 1; // niveau d'affichage (par défaut = 1)

    protected $user;

    public function __construct(Security $security){
        $this->user = $security->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $section = $options['form_element']; // on récupère l'élément de formulaire concerné
        $config = $section->getConfig(); // config de l'élément
        // -- s'il s'agit d'une entrée de collection
        if($options['isCollectionEntry'])
        {
            if(isset($config['renamable']) && $config['renamable']) // option de collection "renamable"
            {
                $builder->add('_name', TextType::class); // ajout d'un champ pour le renommage
            }
        }        
        // éléments de formulaires "enfants" de la section
        foreach ($section->getChildren() as $c) 
        {           
            if ($c->getPhase())  // -- vérification de la phase pour affichage ou non de l'élément
            {
                if (isset($options['phases']) && (!in_array($c->getPhase(), $options['phases'])))
                    continue;
                if ((!$this->user->hasPhase($c->getPhase())))
                    continue;
            }
            if ($c->getType() == 'section') 
            {
                $builder->add($c->getName(), SectionType::class, [
                    'lvl' => ($options['lvl'] + 1), // on incrémente le niveau d'affichage
                    'form_element' => $c, // éléments de formulaire enfant concerné
                    'error_bubbling' => ($options['lvl'] > 2), // pour affichage des erreurs
                    'demo' => $options['demo'] // pour mode "démo" sur superadmin
                ]);
            }
            else
            {
                $builder->add($c->getName(), FieldType::class, [
                    'lvl' => ($options['lvl'] + 1), // on incrémente le niveau d'affichage
                    'form_element' => $c, // éléments de formulaire enfant concerné
                    'error_bubbling' => ($options['lvl'] > 2), // pour affichage des erreurs
                    'demo' => $options['demo'] // pour mode "démo" sur superadmin
                ]);
            }
        }        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'form_element' => null, // élément de formulaire concerné
            'lvl' => 1, // niveau d'affichage
            'demo' => false, // mode "démo" sur superadmin
            'isCollectionEntry' => false, // pour gestion d'une entrée de collection
            'error_bubbling' => false // pour affichage des erreurs
        ]);
        $resolver->setRequired('form_element');
        $resolver->setAllowedTypes('form_element', [FormElement::class]);
        $resolver->setAllowedTypes('lvl', ['integer']);        
        $resolver->setAllowedTypes('isCollectionEntry', ['boolean']);
        $resolver->setAllowedTypes('demo', ['boolean']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $section = $options['form_element'];
        // données à transmettre au template twig
        $view->vars['form_element'] = $section; // élément de formulaire concerné
        $view->vars['label'] = $section->getLabel(); // libellé du champ
        $view->vars['lvl'] = $options['lvl']; // niveau d'affichage du champ
        $view->vars['isCollectionEntry'] = $options['isCollectionEntry']; // entrée de collection
        $view->vars['demo'] = $options['demo']; // mode démo

        $config = $section->getConfig();        
        $view->vars['help'] = $config['help'] ?? null; // affichage : légende du champ    
        $view->vars['addendum'] = $config['addendum'] ?? null; // affichage : informations additionnels du champ  
        $view->vars['linebreak'] = $config['linebreak'] ?? false; // affichage : saut de ligne après le champ
        $view->vars['bold'] = $config['bold'] ?? null; // affichage : libellé en gras
        $view->vars['italic'] = $config['italic'] ?? null; // affichage : libellé en italique
        $view->vars['underline'] = $config['underline'] ?? null;  // affichage : libellé souligné
        
        $view->vars['display'] = $config['display'] ?? 'default'; // affichage : type d'affichage (tableau, etc)
        $view->vars['renamable'] = $config['renamable'] ?? false; // option de renommage pour une entrée de collection
        $view->vars['percentage'] = $config['percentage'] ?? false; // affichage : activation du module js d'affichage des pourcentages
    }

    public function getBlockPrefix()
    {
        return 'section';
    }
}
