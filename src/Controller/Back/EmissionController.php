<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Emission\Rate;
use App\Entity\Emission\Value;
use App\Form\Emission\RateType;

/**
 * Ce controlleur contient toutes les fonctionnalités liées aux facteurs d'émission
 * 
 * @Route("/facteurs-emission", name="emission_")
 */
class EmissionController extends AbstractController
{
    /**
     * Page de listing des facteurs d'émission
     * 
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        $em = $this->getDoctrine()->getManager();
        return $this->render('back/emission/list.html.twig', [
            'rates' => $em->getRepository(Rate::class)->findByParent(null)
        ]);        
    }

    /**
     * Page d'édition d'un facteur d'émission
     * 
     * @param Rate $rate
     * @param Request $request
     * 
     * @return Response 
     * 
     * @Route("/modifier/{rate}", name="update")
     */
    public function update(Rate $rate, Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $formRate = $this->createForm(RateType::class, $rate);
        $formRate->handleRequest($request);
        if ($formRate->isSubmitted() && $formRate->isValid()) {
            $rate->setDateUpdate(new \Datetime());
            $rate->addHistory($this->getUser());
            $em->flush();
            $this->addFlash('success', 'Les informations ont bien été mises à jour.');
        }
        $formValue = $this->createForm(NumberType::class, $rate->getCurrentValue(), [
            'constraints' => [
                new Assert\NotNull(),
                new Assert\PositiveOrZero(),
            ]
        ]);
        $formValue->handleRequest($request);
        if ($formValue->isSubmitted() && $formValue->isValid()) {
            if($rate->getCurrentValue() != $formValue->getData()){
                $rate->setDateUpdate(new \Datetime());
                $rate->addHistory($this->getUser());
                $value = new Value();
                $value->setValue($formValue->getData());
                $value->setRate($rate);
                $rate->addValue($value);
                $em->flush();
                $this->addFlash('success', 'La valeur a bien été mise à jour.');
            }            
        }
        $formAlert = $this->createFormBuilder($rate);
        $formAlert->add('dateAlert', DateTimeType::class, [
            'label' => false,
            'date_widget' => 'single_text',
        ]);
        $formAlert = $formAlert->getForm();
        $formAlert->handleRequest($request);
        if ($formAlert->isSubmitted() && $formAlert->isValid()) {
            $em->flush();
            $this->addFlash('success', 'La date a bien été mise à jour.');
        }
        return $this->render('back/emission/update.html.twig', [
            'formRate' => $formRate->createView(),
            'formValue' => $formValue->createView(),
            'formAlert' => $formAlert->createView()
        ]);        
    }
}