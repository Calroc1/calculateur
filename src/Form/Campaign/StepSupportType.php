<?php

namespace App\Form\Campaign;

use App\Entity\Campaign\Campaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\DataMapperInterface;

use App\Entity\Campaign\Data;
use App\Entity\Support\FormElement;
use App\Entity\Support\Support;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;

/**
 * Formulaire pour étape "support de diffusion" d'une campagne
 */
class StepSupportType extends AbstractType  implements DataMapperInterface
{
    protected ?Support $step; // support de diffusion concerné
    protected $user; // utilisateur connecté

    public function __construct(Security $security){
        $this->user = $security->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $variant = $options['data']; // on récupère la variante de campagne concernée
        $builder->setDataMapper($this);
        $this->step = $options['step']; // on récupère le support de diffusion concerné

        $formElements = [];
        // ------ Ajout des sections "fixes" selon les options actives dans la configuration de la campagne   
        // section fixe : EFFICACITE MEDIA
        if ($variant->getCampaign() && $variant->getCampaign()->getHasNotionMediaEfficiency()) 
        {
            $efficiencyMedia = new FormElement();
            $efficiencyMedia->setType('section');
            $efficiencyMedia->setName('efficiency_media');
            $efficiencyMedia->setLabel('Performance média');
            $efficiencyMediaContacts = new FormElement();
            $efficiencyMediaContacts->setType('integer');
            $efficiencyMediaContacts->setName('contacts');
            $efficiencyMediaContacts->setLabel('Nombre de contact');
            $efficiencyMediaContactsUnique = new FormElement();
            $efficiencyMediaContactsUnique->setType('integer');
            $efficiencyMediaContactsUnique->setName('contacts_unique');
            $efficiencyMediaContactsUnique->setLabel('Nombre de contact unique');
            $efficiencyMedia->addChild($efficiencyMediaContacts);
            $efficiencyMedia->addChild($efficiencyMediaContactsUnique);
            $formElements[] = $efficiencyMedia;
        }
        // section fixe : BUDGET 
        if ($variant->getCampaign() && ($variant->getCampaign()->getNotionBudget() == Campaign::NOTION_BUDGET_YES_MEDIA && $this->user->getStatus() == 'SUPERVISOR'))
        {            
            $budget = new FormElement();
            $budget->setType('section');
            $budget->setName('budget');
            $budget->setLabel('Budget');
            $budgetBudget = new FormElement();
            $budgetBudget->setType('integer');
            $budgetBudget->setName('budget');
            $budgetBudget->setLabel('Budget associé au support de diffusion');
            $budgetBudget->setConfig([
                'unit' => "Euros (HT)"
            ]);
            $budget->addChild($budgetBudget);
            $formElements[] = $budget;
        }
        // récupérations des éléments de formulaire du support de diffusion
        $formElements = array_merge($formElements, $this->step->getFormElements()->toArray());
        foreach ($formElements as $fe) {            
            if ($fe->getPhase()) // -- vérification de la phase pour affichage ou non de l'élément
            {
                if ($variant->getCampaign() && !in_array($fe->getPhase(), $variant->getCampaign()->getPhases()))
                    continue;
                if ((!$this->user->hasPhase($fe->getPhase())))
                    continue;
            }
            if ($fe->getType() == 'section') 
            {
                $builder->add($fe->getName(), SectionType::class, [
                    'form_element' => $fe,
                    'demo' => $options['demo']
                ]);
            }           
            else
            {
                $builder->add($fe->getName(), FieldType::class, [
                    'form_element' => $fe,
                    'demo' => $options['demo']
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'step' => null, // support de diffusion concerné
            'demo' => false // mode démo pour affichage sur interface superadmin
        ]);
        $resolver->setRequired('step');
        $resolver->setAllowedTypes('step', [Support::class]);
        $resolver->setAllowedTypes('demo', ['boolean']);        
    }

    /**
     * Alimentation des champs en fonction des éléments en bdd (Données de formulaires + métadatas)
     */
    public function mapDataToForms($viewData, $forms): void
    {        
        $forms = iterator_to_array($forms);
        $metadatas = $viewData->getSupportMetadata($this->step);
        $datas = $viewData->getSupportData($this->step, true);
        foreach($forms as $n => $f) {
            if(isset($f->getConfig()->getOptions()['form_element'])){
                $formElement = $f->getConfig()->getOptions()['form_element'];
                if($formElement->getId()){
                    if(isset($datas[$n])){
                        $forms[$n]->setData($datas[$n]);
                    }                        
                }
                else {                    
                    if(isset($metadatas[$n])){
                        $forms[$n]->setData($metadatas[$n]);
                    }                        
                }      
            }       
        }
    }

    /**
     * Création des données en bdd en fonction des données renseignées dans le formulaire
     */
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        $save = function($formData, $formElement, $variant) use ( &$save ) {
            if(!$formElement)
                return;
            if($formElement->getType() == 'section'){
                foreach($formElement->getChildren() as $c){
                    $save($formData[$c->getName()], $c, $variant);
                }
            }
            else {
                if($formElement->getMapped()){                    
                    $dbData = $variant->getFieldData($formElement, false); 
                    if($formData == $formElement->getDefaultValue()){
                        if($dbData)
                            $variant->removeData($dbData);
                    }
                    else{
                        if(!$dbData){
                            $dbData = new Data();
                            $dbData->setField($formElement);                            
                            $variant->addData($dbData);
                        }
                        $dbData->setValue(json_encode($formData));
                    }
                }
            }            
        };
        foreach($forms as $n => $f){
            if(isset($f->getConfig()->getOptions()['form_element'])){
                $formElement = $f->getConfig()->getOptions()['form_element'];
                if($formElement->getId()){
                    $save($f->getData(), $this->step->getFormElementByPath($n), $viewData);
                }
                else {
                    $viewData->addStepMetadata($this->step, [
                        $n => $f->getData()]
                    );
                }
            }            
        }    
    }
}
