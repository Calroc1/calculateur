<?php

namespace App\Controller\Front;

use App\Service\BudgetService;
use App\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type;

use App\Entity\Campaign\Campaign;
use App\Entity\Support\Support;
use App\Entity\Support\Type as SupportType;
use App\Entity\Campaign\Variant;
use App\Form\Campaign\CampaignType;
use App\Form\Campaign\DuplicateType;
use App\Form\Campaign\RequestType;
use App\Form\Campaign\StepSupportType;
use App\Service\StatService;
use App\Service\Mailer\AdminMailerService;

/**
 * Ce controlleur contient toutes les fonctionnalités liées aux campagnes
 * 
 * @Route("/campagnes", name="campaign_")
 */
class CampaignController extends AbstractController
{
    protected $statService;
    protected $budgetService;
    protected $contactService;

    /**
     * @param StatService $statService
     * @param BudgetService $budgetService
     * @param ContactService $contactService
     */
    public function __construct(StatService $statService, BudgetService $budgetService, ContactService $contactService)
    {
        $this->statService = $statService;
        $this->budgetService = $budgetService;
        $this->contactService = $contactService;
    }

    /**
     * Page de listing des campagnes
     * 
     * @return Response
     * 
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        $byDateByOrganism = [];
        $rootOrganism = $this->getUser()->getOrganism();

        if ($rootOrganism->getLvl() === 0) {
            foreach ($rootOrganism->getChildren() as $organism) {
                $organismName = $organism->getName();
                foreach ($organism->getCampaigns() as $campaign) {
                    $campaignDate = $campaign->getDateEnd()->format('Y');
                    if (!array_key_exists($campaignDate, $byDateByOrganism)) {
                        $byDateByOrganism[$campaignDate] = [];
                    }
                    if (!array_key_exists($organismName, $byDateByOrganism[$campaignDate])) {
                        $byDateByOrganism[$campaignDate][$organismName] = [];
                    }
                    array_push($byDateByOrganism[$campaignDate][$organismName], $campaign);
                }
            }
        } else if ($rootOrganism->getLvl() === 1) {
            foreach ($rootOrganism->getCampaigns() as $campaign) {
                $campaignDate = $campaign->getDateEnd()->format('Y');
                if (!array_key_exists($campaignDate, $byDateByOrganism)) {
                    $byDateByOrganism[$campaignDate] = [];
                }
                array_push($byDateByOrganism[$campaignDate], $campaign);
            }
        }
        krsort($byDateByOrganism);
        return $this->render('front/campaign/home.html.twig', ['allCampaigns' => $byDateByOrganism]);
    }

    /**
     * Endpoint ajax : recherche de campagnes par date ou type 
     * 
     * @param SerializerInterface $serializer
     * 
     * @return Response
     * 
     * @Route("/searchby", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="searchby")
     */
    public function searchby(SerializerInterface $serializer): Response
    {
        $repo = $this->getDoctrine()->getRepository(Campaign::class);
        if ($_GET['date'] ?? null) {
            $date = \DateTime::createFromFormat('Y-m-d', $_GET['date'])->setTime(0, 0);
            $campaigns = $repo->findUserCampaigns($this->getUser(), ['dateEnd' => $date]);
            return $this->json($serializer->normalize($campaigns, null, ['groups' => 'campaign:view']));
        } else if ($_GET['type'] ?? null) {
            $campaigns = $repo->findUserCampaigns($this->getUser());
            return $this->json($serializer->normalize($campaigns, null, ['groups' => 'campaign:date']));
        }
        return $this->json("Param not found");
    }

    /**
     * Endpoint ajax : récupération d'une campagne
     * 
     * @param Campaign $campaign
     * @param SerializerInterface $serializer
     * 
     * @return Response
     * 
     * @Route("/{campaign}/fetch", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="fetch")
     * @IsGranted("campaign_view", subject="campaign")
     */
    public function fetch(Campaign $campaign, SerializerInterface $serializer): Response
    {
        return $this->json($serializer->normalize($campaign, null, ['groups' => 'campaign:view']));
    }

    /**
     * Endpoint ajax : récupération d'une variante d'une campagne
     * 
     * @param Campaign $campaign
     * @param int $variantIndex
     * @param SerializerInterface $serializer
     * 
     * @return Response
     * 
     * @Route("/{campaign}/{variantIndex}/fetch", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="variant_fetch")
     * @IsGranted("campaign_view", subject="campaign")
     */
    public function variantFetch(Campaign $campaign, $variantIndex = 0, SerializerInterface $serializer): Response
    {
        return $this->json($serializer->normalize($campaign->getVariantByIndex($variantIndex), null, ['groups' => 'variant:view']));
    }

    /**
     * Page de comparaison de campagnes
     * 
     * @param Campaign $campaign
     * 
     * @return Response
     * 
     * @Route("/{campaign}/comparaison", options={"expose"=true}, name="compare")
     * @IsGranted("campaign_view", subject="campaign")
     */
    public function compare(Campaign $campaign): Response
    {
        $statisticsByVariant = [];
        $budgetsByVariant = [];
        $contactsByVariant = [];

        foreach($campaign->getVariants() as $v){
            $statisticsByVariant[] = $this->statService->getVariantStatistics($v);
            $budgetsByVariant[] = $this->budgetService->getVariantBudgets($v);
            $contactsByVariant[] = $this->contactService->getVariantContacts($v);
        }

        return $this->render('front/campaign/compare.html.twig', [
            'statisticsByVariant' => $statisticsByVariant,
            'budgetsByVariant' => $budgetsByVariant,
            'contactsByVariant' => $contactsByVariant
        ]);
    }

    /**
     * Endpoint ajax : récupération des statistiques d'une campagne
     * 
     * @param Campaign $campaign
     * 
     * @return Response
     * 
     * @Route("/{campaign}/statistic", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="statistic")
     * @IsGranted("campaign_view", subject="campaign")
     */
    public function statistic(Campaign $campaign): Response
    {
        $variant = ($campaign->getChosenVariant()) ? $campaign->getChosenVariant() : $campaign->getVariantByIndex(0);
        return $this->render("front/parts/_stats_campaign.html.twig", [
            'campaign' => $campaign,
            'variantIndex' => $campaign->getChosenVariantIndex(),
            'name' => $campaign->getName(),
            'dateCreation' => $campaign->getDateCreation(),
            'author' => $campaign->getAuthor(),
            'statistics' => $this->statService->getVariantStatistics($variant, true, true),
            'budgets' => $this->budgetService->getVariantBudgets($variant, true),
            'contacts' => $this->contactService->getVariantContacts($variant, true),
            'compareVariants' => false
        ]);
    }

    /**
     * Page de création d'une campagne
     * 
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/creer", name="add")
     * @IsGranted("campaign_add")
     */
    public function add(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $vars = [
            'support_types' => $em->getRepository(SupportType::class)->findBy([], ['name' => 'ASC'])
        ];

        $campaign = new Campaign();
        $campaign->setAuthor($this->getUser());

        $formAdd = $this->createForm(CampaignType::class, $campaign, [
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
        $formDuplicate = $this->createForm(DuplicateType::class, null, [
            'campaign_data' => true
        ]);

        $formAdd->handleRequest($request);
        if ($formAdd->isSubmitted() && $formAdd->isValid()) {
            if ($formAdd->has('duplication') && $formAdd->get('duplication')->getData()) {

            } else {
                $master = new Variant($campaign);
                $master->setAuthor($this->getUser());
                foreach($formAdd->get('steps')->getData() as $s){
                    $master->addSupport($s);
                }
                $em->persist($campaign);
                $em->flush();
                return $this->redirectToRoute('front_campaign_update', [
                    'campaign' => $campaign->getId()
                ]);
            }
        }

        $formDuplicate->handleRequest($request);
        if ($formDuplicate->isSubmitted() && $formDuplicate->isValid()) {
            $duplicateData = $formDuplicate->getData();
            $formAdd->submit($request->query->get($formAdd->getName()));
            if ($duplicateData['originCampaign']) {
                foreach($duplicateData['originCampaign']->getVariants() as $duplicatedVariant){
                    $variant = new Variant($campaign);
                    $variant->setAuthor($this->getUser());
                    foreach($formAdd->get('steps')->getData() as $s){
                        $variant->addSupport($s);
                    }
                    foreach ($duplicatedVariant->getDatas() as $data)
                        $variant->addData(clone $data);
                }
            }      
            $em->persist($campaign);
            $em->flush();
            return $this->redirectToRoute('front_campaign_update', [
                'campaign' => $campaign->getId()
            ]);
        }

        if (($formAdd->isSubmitted() && $formAdd->isValid()) || $formDuplicate->isSubmitted())
            $vars['formDuplicate'] = $formDuplicate->createView();
        else
            $vars['formAdd'] = $formAdd->createView();

        return $this->render('front/campaign/add.html.twig', $vars);
    }

    /**
     * Fonctionnalité de suppresion d'une campagne
     * 
     * @param Campaign $campaign
     * 
     * @return Response
     * 
     * @Route("/{campaign}/supprimer", name="delete")
     * @IsGranted("campaign_delete", subject="campaign")
     */
    public function delete(Campaign $campaign): Response
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($campaign);
        $em->flush();
        $this->addFlash('success', "La campagne a bien été supprimée.");
        return $this->redirectToRoute('front_campaign_home');
    }

    /**
     * Fonctionnalité de choix d'une variante pour une campagne (disponible sur la page de comparaison)
     * 
     * @param Campaign $campaign
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/{campaign}/choisir-variante/", options={"expose"=true}, name="variant_choose", methods={"POST"})
     * @IsGranted("campaign_update", subject="campaign")
     */
    public function variantChoose(Campaign $campaign, Request $request): Response
    {
        if($request->request->has('variantIndex')){
            $variant = $campaign->getVariantByIndex($request->request->get('variantIndex'));
            if($variant){
                $campaign->setChosenVariant(($campaign->getChosenVariant() == $variant) ? null : $variant);
                $em = $this->getDoctrine()->getManager();
                $em->persist($campaign);
                $em->flush();
            }
        }
        return $this->redirectToRoute('front_campaign_compare', [
            'campaign' => $campaign->getId()
        ]);
    }

    /**
     * Fonctionnalité de duplication d'une variante
     * 
     * @param Campaign $campaign
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/{campaign}/dupliquer-variante", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="variant_duplicate")
     * @IsGranted("campaign_add", subject="campaign")
     */
    public function variantDuplicate(Campaign $campaign, Request $request): Response
    {
        if ($campaign->getVariants()->count() >= 3)
            throw new \Exception('Too many variants for this campaign');

        $formDuplicate = $this->createForm(DuplicateType::class, null, [
            'with_submit' => true
        ]);
        $formDuplicate->add('variantName', Type\HiddenType::class);
        $formDuplicate->handleRequest($request);
        if ($formDuplicate->isSubmitted() && $formDuplicate->isValid()) {
            $duplicateData = $formDuplicate->getData();

            $variant = new Variant($campaign);
            $variant->setAuthor($this->getUser());
            $variant->setName($duplicateData['variantName']);

            if ($duplicateData['originCampaign']) {
                $duplicatedVariant = $duplicateData['originCampaign']->getVariantByIndex(0);
                foreach($duplicatedVariant->getSupports() as $s){
                    $variant->addSupport($s);
                }                
                foreach ($duplicatedVariant->getDatas() as $data) {
                    $variant->addData(clone $data);
                }
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($campaign);
            $em->flush();

            return new Response('');
        }
        if($request->request->has('name')){
            $formDuplicate->get('variantName')->setData($request->request->get('name'));
        }
        return $this->render('front/campaign/_form.duplicate.html.twig',[
            'form' => $formDuplicate->createView()
        ]);
    }

    /**
     * Fonctionnalité de création d'une variante
     * 
     * @param Campaign $campaign
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/{campaign}/creer-variante/", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="variant_create")
     * @IsGranted("campaign_update", subject="campaign")
     */
    public function variantCreate(Campaign $campaign, Request $request): Response
    {
        if ($campaign->getVariants()->count() >= 3)
            throw new \Exception('Too many variants for this campaign');
        $variant = new Variant($campaign);
        $variant->setAuthor($this->getUser());
        if($request->request->has('name')){
            $variant->setName($request->request->get('name'));
        }
        foreach($campaign->getVariantByIndex(0)->getSupports() as $s){
            $variant->addSupport($s);
        }        
        $em = $this->getDoctrine()->getManager();
        $em->persist($campaign);
        $em->flush();
        return new Response('');
    }

    /**
     * Page de visualisation et de mise à jour d'une variante
     * 
     * @param Campaign $campaign
     * @param int $variantIndex
     * 
     * @return Response
     * 
     * @Route("/{campaign}/{variantIndex}", options={"expose"=true}, name="update")
     * @IsGranted("campaign_update", subject="campaign")
     */
    public function update(Campaign $campaign, $variantIndex = 0): Response
    {
        $variant = $campaign->getVariantByIndex($variantIndex);
        if (!$variant)
            throw $this->createNotFoundException('The variant does not exist');
        $em = $this->getDoctrine()->getManager();
        return $this->render('front/campaign/detail.html.twig', [
            'campaign' => $campaign
        ]);
    }

    /**
     * Endpoint ajax : étape d'une variante
     * 
     * @param Campaign $campaign
     * @param int $variantIndex
     * @param string $stepName
     * @param Request $request
     * @param AdminMailerService $mailer
     * 
     * @return Response
     * 
     * @Route("/{campaign}/{variantIndex}/etape/{stepName}", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="step")
     * @IsGranted("campaign_update", subject="campaign")
     */
    public function step(Campaign $campaign, $variantIndex = 0, $stepName = 'informations', Request $request, AdminMailerService $mailer): Response
    {
        $variant = $campaign->getVariantByIndex($variantIndex);
        if (!$variant)
            throw $this->createNotFoundException('The variant does not exist');

        $statistics = []; $form = null;
        $em = $this->getDoctrine()->getManager();
        if ($stepName == 'informations') // étape "information""
        {
            $form = $this->createForm(CampaignType::class, $campaign, [
                'edit' => true,
                'variant' => $variant,
                'disabled' => $this->getUser()->getStatus() == 'GUEST'
            ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($campaign);
                $em->flush();
            }
            $stepContent = $this->renderView('front/campaign/_form.informations.html.twig', [
                'form' => $form->createView(),
                'support_types' => $em->getRepository(SupportType::class)->findBy([], ['name' => 'ASC'])
            ]);
            $statistics = $this->statService->getVariantStatistics($variant);
        } 
        else if ($stepName == 'bilan') // étape "bilan""
        {
            $statistics = $this->statService->getVariantStatistics($variant);
            $budgets = $this->budgetService->getVariantBudgets($variant);
            $contacts = $this->contactService->getVariantContacts($variant);
            
            $stepContent = $this->renderView('front/campaign/_bilan.html.twig', [
                'statistics' => $statistics,
                'budgets' => $budgets,
                'campaign' => $campaign,
                'contacts' => $contacts
            ]);
        } else {
            $step = null;
            if ($stepName == 'requests') // étape "demande spéciale"
            {
                $oldRequests = $variant->getAdditionnalRequests();
                $form = $this->createForm(RequestType::class, $variant);
                $tpl = 'front/campaign/_form.requests.html.twig';
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $variant->setDateUpdate(new \Datetime());
                    $em->persist($variant);
                    $em->flush();
                    foreach($variant->getAdditionnalRequests() as $k => $r){
                        $update = false;
                        if(isset($oldRequests[$k])){
                            if($oldRequests[$k] == $r)
                                continue;
                            $update = true;
                        }
                        $mailer->request($this->getUser(), $variant, $r, $update);
                    }
                }
            } 
            else // étapes de supports de diffusion
            {
                $step = $em->getRepository(Support::class)->findOneBy([
                    'name' => $stepName
                ]);
                if (!$step || !$variant->hasSupport($step))
                    throw $this->createNotFoundException('The step does not exist');                

                $form = $this->get('form.factory')->createNamed($step->getName(), StepSupportType::class, $variant, [
                    'step' => $step
                ]);
                $tpl = 'front/campaign/_form.step.html.twig';
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $variant->setDateUpdate(new \Datetime());
                    $em->persist($variant);
                    $em->flush();
                    $em->refresh($variant);
                }
            }
            $stepContent = $this->renderView($tpl, [
                'variant' => $variant,
                'form' => $form->createView()
            ]);
            $statistics = $this->statService->getVariantStatistics($variant);
        }
        return $this->json([
            'step' => $stepContent,
            'statistics' => $statistics,
            'error' => ($form && $form->isSubmitted()) ? !$form->isValid() : null
        ]);
    }

    /**
     * Endpoint Ajax : fonctionnalité de renommage d'une variante
     * 
     * @param Campaign $campaign
     * @param int $variantIndex
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/{campaign}/{variantIndex}/renommer-variante", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="variant_rename")
     * @IsGranted("campaign_update", subject="campaign")
     */
    public function variantRename(Campaign $campaign, $variantIndex = 0, Request $request): Response
    {
        $variant = $campaign->getVariantByIndex($variantIndex);
        if ($variant) {
            $em = $this->getDoctrine()->getManager();
            $variant->setName($request->request->get('name'));
            $em->persist($variant);
            $em->flush();
        }
        return new Response('');
    }

    /**
     * Fonctionnalité de suppresion d'une variante
     * 
     * @param Campaign $campaign
     * @param int $variantIndex
     * 
     * @return Response
     * 
     * @Route("/{campaign}/{variantIndex}/supprimer-variante", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="variant_delete")
     * @IsGranted("campaign_update", subject="campaign")
     */
    public function variantDelete(Campaign $campaign, $variantIndex = 0): Response
    {
        if ($variantIndex == 0)
            throw new \Exception("Master variant can't be deleted");
        $variant = $campaign->getVariantByIndex($variantIndex);
        if ($variant) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($variant);
            $em->flush();
        }
        return new Response('');
    }
}
