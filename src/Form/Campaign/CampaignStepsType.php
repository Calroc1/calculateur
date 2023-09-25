<?php

namespace App\Form\Campaign;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\DataMapperInterface;

use App\Entity\Support\Support;
use App\Entity\Support\Referential;
use App\Entity\Support\Type as SupportType;

/**
 * FormType pour étapes (= supports de diffusions) d'une campagne
 */
class CampaignStepsType extends AbstractType implements DataMapperInterface
{
    private $em;
    private $formSupports;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {         
        $this->formSupports = [];

        $referentials = $this->em->getRepository(Referential::class)->findBy([], ['name' => 'ASC']);
        $types = $this->em->getRepository(SupportType::class)->findBy([]);
        foreach($referentials as $referential){
            $referentialName = $referential->getSafeName();
            $builder->add($referential->getSafeName(), Type\FormType::class, [
                'label' => $referential->getName()
            ]);            
            foreach($types as $type){
                $typeName = $type->getSafeName();
                $builder->get($referentialName)->add($typeName, Type\FormType::class, [
                    'label' => $type->getName()
                ]);
                $supports = $this->em->getRepository(Support::class)->findBy([
                    'enabled' => true,
                    'referential' => $referential,
                    'type' => $type
                ]);
                //$this->supports = array_merge($this->supports, $supports);
                foreach($supports as $support){
                    $builder->get($referentialName)->get($typeName)->add($support->getId(), Type\CheckboxType::class, [
                        'label' => $support->getDisplayName() ? $support->getLabel() : ''
                    ]);
                    $this->formSupports["{$referentialName}_{$typeName}_{$support->getId()}"] = $support;
                }
            } 
        }
        $builder->setDataMapper($this);
    }

    public function mapDataToForms($viewData, $forms): void
    {        
        $supportIds = [];
        if($viewData){
            foreach($viewData as $v)
                $supportIds[] = $v->getId();
        }

        $forms = iterator_to_array($forms);
        foreach($forms as $referentialName => $referential){
            $data = [];
            foreach($referential->all() as $typeName => $type){
                $data[$typeName] = [];
                foreach($type->all() as $supportId => $support){
                    if($viewData && in_array($supportId, $supportIds)){
                        $data[$typeName][$supportId] = true;
                    }
                }                
            } 
            $forms[$referentialName]->setData($data);
        }
    }

    public function mapFormsToData($forms, &$viewData): void
    {  
        $forms = iterator_to_array($forms);   
        $viewData = [];            
        foreach($forms as $referential){      
            $referentialName = $referential->getName();      
            foreach($referential->all() as $type){    
                $typeName = $type->getName();                  
                foreach($type->all() as  $support){
                    $supportId = $support->getName();         
                    if($support->getData() == true)
                        $viewData[] = $this->formSupports["{$referentialName}_{$typeName}_{$supportId}"];
                }
            }    
        }
    }
}
