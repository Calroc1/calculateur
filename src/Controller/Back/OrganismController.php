<?php
namespace App\Controller\Back;
 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use App\Entity\Organism;

use App\Form\OrganismType;
 
/**
 * Ce controlleur contient toutes les fonctionnalités liées aux entreprises
 * 
 * @Route("/entreprises", name="organism_")
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
        $organisms = $this->getDoctrine()->getRepository(Organism::class)->findByLvl(0);
        return $this->render('back/organism/home.html.twig', [
            'organisms' => $organisms
        ]);
    }

    /**
     * Page de création d'un organisme
     * 
     * @param mixed $idParent
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/ajouter/{idParent?}", name="add")
     */
    public function add($idParent, Request $request): Response
    {
        $organism = new Organism();
        if($idParent){
            $parent = $this->getDoctrine()->getRepository(Organism::class)->findOneById($idParent);
            if(!$parent || $parent->getLvl() != 0)
                throw new NotFoundHttpException("Parent not found");
            $organism->setParent($parent);
            $organism->setLvl(1);
        }
        $form = $this->createForm(OrganismType::class, $organism);       
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager(); 
            $em->persist($organism);
            $em->flush();

            $this->addFlash('success', "L'".$organism->getType()." a bien été créée.");
            return $this->redirectToRoute('back_organism_detail', [
                'organism' => $organism->getId()
            ]);
        }
        return $this->render('back/organism/add.html.twig', [
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
     * @Route("/{organism}", name="detail")
     */
    public function detail(Organism $organism, Request $request): Response
    {       
        $form = $this->createForm(OrganismType::class, $organism);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager(); 
            $em->persist($organism);
            $em->flush();

            $this->addFlash('success', "L'".$organism->getType()." a bien été mise à jour.");
            return $this->redirectToRoute('back_organism_detail', [
                'organism' => $organism->getId()
            ]);
        }
        return $this->render('back/organism/detail.html.twig', [
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
     */
    public function delete(Organism $organism): Response
    {  
        $parent = $organism->getParent();
        $em = $this->getDoctrine()->getManager(); 
        $em->remove($organism);
        $em->flush();
        $this->addFlash('success', "L'".$organism->getType()." a bien été supprimée.");
        if($parent){
            return $this->redirectToRoute('back_organism_detail', [
                'organism' => $parent->getId()
            ]);    
        }        
        else
            return $this->redirectToRoute('back_organism_home');
    }
}