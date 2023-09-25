<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Entity\Organism;

use App\Form\OrganismType;

/**
 * Ce controlleur contient toutes les fonctionnalités liées aux entreprises
 * 
 * @Route("/entreprise", name="organism_")
 * @IsGranted("organism_view")
 */
class OrganismController extends AbstractController
{
    /**
     * Page de listing des entreprises
     * 
     * @return Response
     * 
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        return $this->render('front/organism/home.html.twig');
    }

    /**
     * Page de création d'un organisme
     * 
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/creer-organisation", name="add")
     * @IsGranted("organism_add")
     */
    public function add(Request $request): Response
    {
        $organism = new Organism();
        $organism->setLvl(1);
        $organism->setParent($this->getUser()->getOrganism());
        $form = $this->createForm(OrganismType::class, $organism);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organism);
            $em->flush();

            $this->addFlash('success', "L'organisation a bien été créée.");
            return $this->redirectToRoute('front_organism_home');
        }
        return $this->render('front/organism/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Page d'édition d'un organisme
     * 
     * @param Organism $organism
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/{organism}/editer", name="update")
     * @IsGranted("organism_update", subject="organism")
     */
    public function update(Organism $organism, Request $request): Response
    {
        $form = $this->createForm(OrganismType::class, $organism);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organism);
            $em->flush();

            $this->addFlash('success', "L'organisation a bien été mise à jour.");
            return $this->redirectToRoute('front_organism_home');
        }
        return $this->render('front/organism/update.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Fonctionnalité de suppression d'un organisme
     * 
     * @param Organism $organism
     * 
     * @return Response
     * 
     * @Route("/{organism}/supprimer", name="delete")
     * @IsGranted("organism_delete", subject="organism")
     */
    public function delete(Organism $organism): Response
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($organism);
        $em->flush();
        $this->addFlash('success', "L'organisation a bien été supprimée.");
        return $this->redirectToRoute('front_organism_home');
    }

    /**
     * Endpoint ajax : récupération d'un organisme
     * 
     * @param Organism $organism
     * @param SerializerInterface $serializer
     * 
     * @return Response
     * 
     * @Route("/{organism}/fetch", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="fetch")
     * @IsGranted("organism_view", subject="organism")
     */
    public function fetch(Organism $organism, SerializerInterface $serializer): Response
    {
        return $this->json($serializer->normalize($organism, null, ['groups' => 'organism:edit']));
    }
}
