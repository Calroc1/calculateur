<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Entity\Campaign\Campaign;
use App\Entity\Support\Support;
use App\Entity\Organism;

use App\Form\UserType;
use App\Form\PasswordType;

use App\Service\StatService;

/**
 * Ce controlleur contient toutes les fonctionnalités liées à l'interface de la plateforme
 */
class InterfaceController extends AbstractController
{
    /**
     * Page d'accueil de la plateforme
     * 
     * @return Response
     * 
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        $em = $this->getDoctrine()->getManager();
        $organisms = $em->getRepository(Organism::class)->findUserOrganisations($this->getUser());
        $latest_campaigns = $em->getRepository(Campaign::class)->findUserCampaigns($this->getUser(), [], 4);

        $statsQb = $em->getRepository(Campaign::class)->findUserCampaigns($this->getUser(), [], null, true);
        /*$statsQb->andWhere('YEAR(c.dateStart) = :year OR YEAR(c.dateEnd) = :year OR (YEAR(c.dateStart) >= :year AND YEAR(c.dateEnd) <= :year)')
            ->setParameter('year', date('Y'));*/
        $campaignStatuses = [
            'FINISHED' => 0,
            'COMPLETED' => 0,
            'ARCHIVED' => 0,
            'STARTED' => 0
        ];
        foreach($statsQb->getQuery()->getResult() as $c){
            $campaignStatuses[$c->getStatus()]++;
        }
        return $this->render('front/home.html.twig', [
            'organisms' => $organisms,
            'latestCampaigns' => $latest_campaigns,
            'campaignStatuses' => $campaignStatuses,
            'campaigns' => $em->getRepository(Campaign::class)->findUserCampaigns($this->getUser()),
        ]);
    }

    /**
     * Page d'édition du profil utilisateur
     * 
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * 
     * @return Response
     * 
     * @Route("/donnees-personnelles", name="profile")
     */
    public function profile(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = $this->getUser();
        $formUpdate = $this->createForm(UserType::class, $user);
        $formUpdate->handleRequest($request);
        if ($formUpdate->isSubmitted() && $formUpdate->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "Vos données personnelles ont bien été mises à jour.");
            return $this->redirectToRoute('front_profile');
        }
        $formPassword = $this->createForm(PasswordType::class);
        $formPassword->handleRequest($request);
        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $user->setPassword($passwordEncoder->encodePassword($user, $formPassword->getData()));

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "Votre mot de passe a bien été mis à jour.");
            return $this->redirectToRoute('front_profile');
        }
        return $this->render('front/profile.html.twig', [
            'formUpdate' => $formUpdate->createView(),
            'formPassword' => $formPassword->createView()
        ]);
    }

    /**
     * Endpoint ajax : récupération des statistiques globales
     * 
     * @param StatService $statService
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/statistiques-globales", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="dashboard_statistics")
     */
    public function statistics(StatService $statService, Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $campaigns = $em->getRepository(Campaign::class)->advancedSearchFromRequest($request, $this->getUser());
        return $this->json($statService->getCumulativeStatistics($campaigns));
    }
}
