<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * FormType pour select + option "autre" avec champ de texte
 */
class ChoiceWithDetailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options){
        $choiceOptions = [];
        $detailOptions = [];
        foreach($options as $n => $o){
            if(substr($n, 0, 6 ) == 'choice'){
                $choiceOptions[$n] = $o;
            }
            if(substr($n, 0, 6 ) == 'detail'){
                $detailOptions[$n] = $o;
            }
        }    
        $choiceOptions['error_bubbling'] = true;
        if($options['detail']){
            $choiceOptions['choices']['Autre (à préciser)'] = 'other';
        }
        $choiceOptions['attr'] = [            
            'data-trigger-detail' => $options['detail_trigger']
        ];
        $builder     
            ->add('choice', ChoiceType::class, $choiceOptions)
        ;    
        if($options['detail']){
            $builder->add('detail', NumberType::class, [
                'label' => $detailOptions['detail_label'],
                //'attr' => [ 'placeholder' => $detailOptions['detail_placeholder'] ]
            ]);
        }            
        $choices = array_keys($choiceOptions['choices']);
        $multiple = $options['multiple'];
        $builder
            ->addModelTransformer(new CallbackTransformer(
                function ($data) use ($choices, $multiple) {
                    $choice = null;
                    $detail = null;
                    if($data !== null){
                        if($multiple){
                            $choice = [];
                            foreach($data as $d){  
                                if(!in_array($d, $choices)){
                                    $choice[] = 'other';
                                    $detail = $d;
                                }
                                else
                                    $choice[] = $d;
                            }
                        }
                        else{
                            if(!in_array($data, $choices)){
                                $choice = 'other';
                                $detail = $data;
                            }
                            else
                                $choice = array_search($data, $choices); 
                        }
                    }
                    return ['choice' => $choice, 'detail' => $detail];
                },
                function($data) use ($choices, $multiple) {
                    if($multiple){
                        if(($i = array_search('other', $data['choice'])) !== false){
                            $data['choice'][$i] = $data['detail'];
                        }
                        return $data['choice'];
                    }
                    else{
                        if($data['choice'] === 'other'){
                            return $data['detail'];
                        }                            
                        else
                            return $choices[$data['choice']] ?? null;
                    }
                }
            ))
        ;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options, $detailOptions) {
            $data = $event->getData();
            $form = $event->getForm();
            if(isset($data['choice'])){
                if((!$options['multiple'] && $data['choice'] == $detailOptions['detail_trigger']) || ($options['multiple'] && in_array($detailOptions['detail_trigger'], $data['choice']))){
                    $form = $event->getForm();
                    $options = $form->get('detail')->getConfig()->getOptions();
                    $options['constraints'] = $detailOptions['detail_constraints'];
                    $form->add('detail', NumberType::class, $options);
                }
            }
        });
    }
    
    public function configureOptions(OptionsResolver $resolver){        
        $parent = new ChoiceType;
        $parent->configureOptions($resolver);        
        $resolver->setDefaults([
            'compound' => true,
            'detail' => true,
            'detail_trigger' => 'Autre (à préciser)',
            'detail_placeholder' => 'Autre (à préciser)',
            'detail_label' => false,
            'detail_constraints' => [ new Assert\NotNull() ]
        ]);
        $resolver->setAllowedTypes('detail_trigger', 'string');
        $resolver->setAllowedTypes('detail_placeholder', 'string');
        $resolver->setAllowedTypes('detail_label', ['null', 'bool', 'string']);
        $resolver->setAllowedTypes('detail', ['bool']);
    }
}