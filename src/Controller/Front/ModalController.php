<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert;

use App\Service\Mailer\AdminMailerService;

/**
 * Ce controlleur contient toutes les fonctionnalités liées aux modals (fenêtre popup)
 * 
 * @Route("/modals", name="modal_")
 */
class ModalController extends AbstractController
{
    /**
     * Endpoint ajax : Contenu de la modal "nous contacter"
     * 
     * @param Request $request
     * @param AdminMailerService $mailer
     * 
     * @return Response
     * 
     * @Route("/contact", name="contact", condition="request.isXmlHttpRequest()", options={"expose"=true})
     */
    public function contact(Request $request, AdminMailerService $mailer): Response
    {
        $form = $this->createFormBuilder()
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
        ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $mailer->message($this->getUser(), $form->get('message')->getData());
        }

        return $this->render('front/modal/contact.html.twig', [
            'title' => 'Nous contacter',
            'form' => $form->createView()
        ]);
    }

    /**
     * Endpoint ajax : Contenu de la modal de sélection d'une campagne
     * 
     * @return Response
     * 
     * @Route("/selectionner-campagne", name="select_campaign", condition="request.isXmlHttpRequest()", options={"expose"=true})
     */
    public function selectCampaign(): Response
    {        
        return $this->render('front/modal/select-campaign.html.twig', [
            'title' => 'Choisir une campagne',
        ]);
    }
}
