<?php
namespace App\Controller\Front;

use App\Service\BudgetService;
use App\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use App\Entity\Campaign\Campaign;
use App\Entity\Support\Support;
use App\Service\StatService;

/**
 * Ce controlleur contient toutes les fonctionnalités liées aux statistiques
 * 
 * @Route("/analyses", name="statistic_")
 */
class StatisticController extends AbstractController
{
    /**
     * Page d'accueil des statistiques
     * 
     * @return Response
     * 
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        return $this->render('front/statistic/home.html.twig');
    }

    /**
     * Page des statistiques cumulatives
     * 
     * @param Request $request
     * @param StatService $statService
     * @param BudgetService $budgetService
     * @param ContactService $contactService
     * 
     * @return Response
     * 
     * @Route("/cumulative", name="cumulative", options={"expose"=true})
     */
    public function cumulative(Request $request, StatService $statService, BudgetService $budgetService, ContactService $contactService): Response
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('front/statistic/cumulative.html.twig', [
            'campaigns' => $em->getRepository(Campaign::class)->findUserCampaigns($this->getUser()),
            'statistics' => $statService->getCumulativeStatistics($em->getRepository(Campaign::class)->findByIds($request->query->get('campaigns'))),
            'budgets' => $budgetService->getCumulativeBudgets($em->getRepository(Campaign::class)->findByIds($request->query->get('campaigns'))),
            'contacts' => $contactService->getCumulativeContacts($em->getRepository(Campaign::class)->findByIds($request->query->get('campaigns')))
        ]);
    }

    /**
     * Page des statistiques comparatives
     * 
     * @return Response
     * 
     * @Route("/comparative", name="comparative")
     */
    public function comparative(): Response
    {
        $em = $this->getDoctrine()->getManager();
        return $this->render('front/statistic/comparative.html.twig', [
            'campaigns' => $em->getRepository(Campaign::class)->findUserCampaigns($this->getUser())
        ]);
    }

    /**
     * Endpoint ajax : récupération de campagnes en fonction de certains critères de recherche
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * 
     * @return Response
     * 
     * @Route("/filtre-campagnes", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="filter_campaigns")
     */
    public function filterCampaigns(Request $request, SerializerInterface $serializer): Response
    {
        $campaigns = $this->getDoctrine()->getManager()->getRepository(Campaign::class)->advancedSearchFromRequest($request, $this->getUser());
        return $this->json($serializer->normalize($campaigns, null, ['groups' => 'stats']));
    }
}