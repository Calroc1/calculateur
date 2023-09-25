<?php

namespace App\Form\Campaign;

use App\Entity\Support\FormElement;
use App\Form\ChoiceWithDetailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\BaseType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

/**
 * FormType pour élément de formulaire autre que "section"
 */
class FieldType extends BaseType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {       
        $field = $options['form_element']; // élément de formulaire concerné
        $config = $field->getConfig(); // config de l'élément
        $readonly = $options['demo']; // activation de la lecture seule si mode démo pour interface superdmin
        $fieldOptions = [
            'error_bubbling' => true,
            'disabled' => isset($config['disabled']) ? $config['disabled'] : false,
            'attr' => [
                'readonly' => $readonly
            ]
        ];        
        // configuration du champ en fonction de son type
        switch($field->getType())
        {
            case 'integer': // type "numérique"
                $typeClass = NumberType::class;
                $fieldOptions['attr']['min'] = 0;
                $fieldOptions['scale'] = 10;
                if(!$fieldOptions['disabled']){
                    $fieldOptions['constraints'] =  [
                        new Assert\NotBlank(),
                        new Assert\Type(['type' => 'numeric']),
                        new Assert\PositiveOrZero(),                        
                    ];
                }
                break;
            case 'select': // type "liste déroulante"
                $typeClass = ChoiceWithDetailType::class;
                $fieldOptions['detail'] = false;
                $fieldOptions['choices'] = [];
                $fieldOptions['choice_attr'] = [];
                foreach($config['choices'] as $k => $c){
                    $label = $c['label'] ?? ($c['value'] ?? $c);
                    $fieldOptions['choices'][$label] = $k; 
                    $fieldOptions['choice_attr'][$label] = [
                        'data-value' => $c['value'] ?? $c
                    ];
                }
                break;
            case 'select_with_detail': // type "liste déroulante avec champ autre"
                $typeClass = ChoiceWithDetailType::class;
                $fieldOptions['choices'] = [];
                $fieldOptions['choice_attr'] = [];
                foreach($config['choices'] as $k => $c){
                    $label = $c['label'] ?? ($c['value'] ?? $c);
                    $fieldOptions['choices'][$label] = $k;                     
                    $fieldOptions['choice_attr'][$label] = [
                        'data-value' => $c['value'] ?? $c
                    ]; 
                }             
                break;
            case 'collection': // type "collection"
                $typeClass = CollectionType::class;
                $fieldOptions['entry_type'] = SectionType::class;
                $fieldOptions['entry_options'] = [
                    'form_element' => $field,
                    'isCollectionEntry' => true,
                    'lvl' => 1,
                    'demo' => $options['demo']
                ];
                $fieldOptions['allow_add'] = true;
                $fieldOptions['allow_delete'] = true;
                if($options['demo']){
                    $config['default'] = 'entry';
                    $field->setConfig($config);
                }                    
                break;
            case 'textarea': // type "zone de texte"
                $typeClass = TextareaType::class;
                $fieldOptions['attr'] = [ 'rows' => $config['rows'] ?? 5 ];
                $fieldOptions['constraints'] =  [
                    new Assert\NotBlank()
                ];
                break;
            case 'boolean': // type "case à cocher oui/non"
                $typeClass = ChoiceType::class;
                $fieldOptions['choices'] = [
                    'Non' => 0,
                    'Oui' => 1
                ];
                $fieldOptions['expanded'] = true;
                $fieldOptions['constraints'] =  [
                    new Assert\NotBlank()
                ];
                break;
            case 'country': // type "liste déroulante des pays"
                $typeClass = CountryType::class;
                $fieldOptions['constraints'] =  [
                    new Assert\NotBlank()
                ];
                break;
            default: // type "champ de texte standard"
                $typeClass = TextType::class;
                $fieldOptions['constraints'] =  [
                    new Assert\NotBlank()
                ];
        }
        $builder
            ->add('field', $typeClass, $fieldOptions) // ajout du champ
            ->setDataMapper($this)
        ;
        // assignation de la valeur par défaut
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($field) {
            $val = $event->getData();
            if($val === null)
            {  
                $event->setData($field->getDefaultValue());
            }
        });
        // traitement de la valeurs à la soumission
        $builder->get('field')->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($field) {            
            if($event->getData() == null) // si champ vidé on assigne la valeur par défaut pour ne pas enregistrer des données inutiles en bdd
            {
                $event->setData($field->getDefaultValue(true));
            }
            else
            {                
                if($field->getType() == 'collection') // reorder collections
                {
                    $data = $event->getData();
                    $form = $event->getForm();
                    $form->setData(array_values($data));      
                    $event->setData(array_values($data));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'form_element' => null, // élément de formulaire concerné
            'lvl' => 1, // niveau d'affichage
            'demo' => false, // mode démo pour interface superadmin
            'error_bubbling' => false // pour affichage des erreurs
        ]);
        $resolver->setRequired('form_element');
        $resolver->setAllowedTypes('form_element', [FormElement::class]);
        $resolver->setAllowedTypes('lvl', ['integer']);
        $resolver->setAllowedTypes('demo', ['boolean']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    { 
        $field = $options['form_element'];
        // données à transmettre au template twig
        $view->vars['form_element'] = $field; // élément de formulaire concerné
        $view->vars['label'] = $field->getLabel(); // libellé du champ
        $view->vars['lvl'] = $options['lvl']; // niveau d'affichage
        $view->vars['field_type'] = $field->getType();  // type du champ      
        $view->vars['demo'] = $options['demo']; // mode démo

        $config = $field->getConfig();                
        $view->vars['help'] = $config['help'] ?? null; // affichage : légende du champ      
        $view->vars['addendum'] = $config['addendum'] ?? null;  // affichage : informations additionnels du champ         
        $view->vars['linebreak'] = $config['linebreak'] ?? false; // affichage : saut de ligne après le champ
        $view->vars['bold'] = $config['bold'] ?? null;  // affichage : libellé en gras      
        $view->vars['italic'] = $config['italic'] ?? null;  // affichage : libellé en italique      
        $view->vars['underline'] = $config['underline'] ?? null; // affichage : libellé souligné
        
        $view->vars['mapped'] = $config['mapped'] ?? true; // champ dont la valeur doit être enregistré en bdd ou non
        $view->vars['unit'] = $config['unit'] ?? null; // affichage : unité du champ
        $view->vars['size'] = $config['size'] ?? null; // affichage : longueur du champ
        $view->vars['renamable'] = $config['renamable'] ?? false; // option de renommage pour une collection

        $error = false;
        if($form->isSubmitted()){
            $error = !$form->isValid();
        }
        $view->vars['hasError'] = $error; // si le champ est invalide après soumission
    }

    public function getBlockPrefix()
    {
        return 'field';
    }

    /**
     * Assignation de la donnée du FieldType vers le champ
     */
    public function mapDataToForms($viewData, $forms): void
    {  
        $forms = iterator_to_array($forms);
        $data = $viewData;
        $forms['field']->setData($data);
    }

    /**
     * Transmission de la donnée renseignée dans le champs vers le FieldType
     */
    public function mapFormsToData($forms, &$viewData): void
    {  
        $forms = iterator_to_array($forms);
        $data = $forms['field']->getData();
        $viewData = $data;
    }
}
