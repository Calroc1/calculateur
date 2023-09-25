<?php

namespace App\Controller\Front;

use App\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Campaign\Campaign;
use App\Entity\Support\Formula;
use App\Entity\Support\Support;
use App\Entity\Campaign\Variant;
use App\Entity\Support\FormElement;
use App\Service\StatService;

/**
 * Ce controlleur contient toutes les fonctionnalités liées aux exports
 * 
 * @Route("/export", name="export_")
 */
class ExportController extends AbstractController
{
    private $statService;

    /**
     * @param StatService $statService
     */
    public function __construct(StatService $statService)
    {
        $this->statService = $statService;
    }

    /**
     * Fonctionnalité d'export des campagnes
     * 
     * @param Request $request
     * @param ContactService $contactService
     * 
     * @return Response
     * 
     * @Route("/campaigns", name="campaigns")
     */
    public function exportCampaign(Request $request, ContactService $contactService): Response
    {
        $em = $this->getDoctrine()->getManager();
        $rows = [
            [
                "Organisation",
                "Intitule de campagne",
                "Tagg associes",
                "Pays",
                "Date debut",
                "Date fin",
                "Media",
                "Phase",
                "Support",
                "Nombre de contacts",
                "Emissions carbones (Kg CO2 eq)",
                "Emissions carbones par contact (g CO2 eq/contact)"
            ]
        ];
        foreach($em->getRepository(Campaign::class)->advancedSearchFromRequestPost($request, $this->getUser()) as $campaign) {
            foreach ($campaign->getVariants() as $variant) {      
                $this->statService->setShortcodeServiceVariant($variant);          
                foreach($em->getRepository(Support::class)->findEnabledSupports() as $support){
                    if(!$variant->hasSupport($support))
                        continue;

                    $contacts = $contactService->getSupportContacts($variant, $support);
                    $totalContacts = $contacts ? (int)$contacts['contacts'] + (int)$contacts['contacts_unique'] : 0;
                    $row = [
                        $campaign->getOrganism()->getName(),
                        $campaign->getName() . ' - ' . $variant->getName(),
                        "",
                        Countries::getName($campaign->getCountry()),
                        $campaign->getDateStart()->format('d/m/Y'),
                        $campaign->getDateEnd()->format('d/m/Y'),
                        $support->getLabel(),
                        "",
                        "",
                        $totalContacts,
                        0,
                        0
                    ];
                    foreach($support->getFormulas() as $formula){
                        $this->handleFormElement($formula, $variant, $support->getFormElementByPath($formula->getPath()), $rows, $row, $totalContacts);                        
                    }
                }
            }
        }

        $dateStart = \Datetime::createFromFormat('Y-m-d', $request->request->get("dateStart"));
        $dateEnd = \Datetime::createFromFormat('Y-m-d', $request->request->get("dateEnd"));
        $filename = "BILOBAY_export_" . $dateStart->format("dmY") . "-" . $dateEnd->format("dmY") . ".csv";
        $content = '';
        foreach ($rows as $row) {
            $content .= implode(';', $row) . "\n";
        }
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Content-Description', 'File Transfer');
        $response->headers->set('Expires', '0');
        $response->headers->set('Pragma', 'public');
        return $response;
    }

    /**
     * Parcoure l'élément de formulaire pour récupération des données à afficher dans le fichier excel
     * 
     * @param Formula $formula
     * @param Variant $variant
     * @param FormElement|null $formElement
     * @param array $rows
     * @param array $row
     * @param float $totalContacts
     * 
     * @return void
     */
    private function handleFormElement(Formula $formula, Variant $variant, ?FormElement $formElement, array &$rows, array $row, float $totalContacts = 0): void
    {
        if(!$formElement)
            return;       
        if(($phase = $formElement->getPhase()) && (!$variant->hasPhase($phase) || ($this->getUser() && !$this->getUser()->hasPhase($phase))))
            return;

        $data = $variant->getFieldData($formElement);
        if($formElement->getType() == 'collection'){
            $i = 1;
            foreach($data as $d){
                $formElement->setType('section');
                $total = round($this->statService->calculateFormula($formula, $variant, $formElement, $d));
                $formElement->setType('collection');
                $this->addRow($total, $formElement, $d, $rows, $row, $i, $totalContacts);
                $i++;
            }
        }
        else if($formElement->getType() == 'section'){
            $total = $this->statService->calculateFormula($formula, $variant, $formElement);
            if(!$formula->getFormula()) {
                foreach($formula->getChildren() as $subformula){
                    $this->handleFormElement($subformula, $variant, $formElement->getChildByPath($subformula->getPath()), $rows, $row, $totalContacts);
                }
            }
            else {
                $this->addRow($total, $formElement, $data, $rows, $row, "", $totalContacts);
            }
        }
    }    
   
    /**
     * Ajout d'une ligne dans le fichier excel
     * 
     * @param float $totalEmission
     * @param FormElement $formElement
     * @param mixed $data
     * @param array $rows
     * @param array $row
     * @param string $count
     * @param float $totalContacts
     * 
     * @return void
     */
    private function addRow(float $totalEmission, FormElement $formElement, $data, array &$rows, array $row, string $count = "", float $totalContacts = 0): void
    {
        $row[7] = $formElement->getAbsolutePhase();       
        $name = $formElement->getLabel();     
        $config = $formElement->getConfig();   
        if($formElement->getType() == 'collection'){            
            if(isset($data['_name'])){
                $entry = $data['_name'];
            }
            else {
                $unit = $config['unit'] ?? "";
                $entry = "{$unit} {$count}";
            }
            $name .= " - $entry";
        }
        $row[8] = $name;
        
        $row[10] = str_replace('.', ',', $totalEmission);
        $row[11] = $totalContacts > 0 ? str_replace('.', ',', round((float)($totalEmission / $totalContacts), 2)) : 0;        
        $rows[] = $row;
    }
}