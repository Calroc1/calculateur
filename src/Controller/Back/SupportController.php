<?php

namespace App\Controller\Back;

use App\Entity\Support\FormElement;
use App\Entity\Support\Formula;
use App\Entity\Support\Support;
use App\Entity\Support\Type as SupportType;
use App\Entity\Campaign\Variant;
use App\Entity\Support\Referential;
use App\Form\Campaign\StepSupportType;
use App\Form\Support\ReferentialType;
use App\Form\Support\SupportType as SupportFormType;
use App\Form\Support\TypeType as SupportTypeFormType;
use App\Form\UploadType;
use App\Service\ImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * Ce controlleur contient les fonctionnalités liées aux supports de diffusion
 * 
 * @Route("/supports-de-diffusion", name="support_")
 */
class SupportController extends AbstractController
{
    private $importService;

    /**
     * @param ImportService $importService
     */
    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Page de listing des supports de diffusion
     * 
     * @return Response
     * 
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        $em = $this->getDoctrine()->getManager();
        return $this->render('back/support/list.html.twig', [
            'supports' => $em->getRepository(Support::class)->findBy([], [ 'label' => 'ASC' ])
        ]);        
    }

    /**
     * Page de création de support de diffusion
     * 
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/ajouter", name="add")
     */
    public function add(Request $request): Response
    {
        $support = new Support();  
        $support->setName('support_'.uniqid());
        $support->setEnabled(1);
        $support->setPosition(0);
        $form = $this->createForm(SupportFormType::class, $support);       
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager(); 
            $em->persist($support);
            $em->flush();

            $this->addFlash('success', "Le formulaire a bien été créé.");
            return $this->redirectToRoute('back_support_detail', [
                'support' => $support->getId()
            ]);
        }
        return $this->render('back/support/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Page de listing des types de supports de diffusion
     * 
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/types", name="types")
     */
    public function types(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $type = new SupportType();
        $formAdd = $this->createForm(SupportTypeFormType::class, $type);
        $formAdd->handleRequest($request);
        if ($formAdd->isSubmitted() && $formAdd->isValid()) {
            $em->persist($type);
            $em->flush();
            $this->addFlash('success', "Le type de support de diffusion a bien été ajouté.");
            return $this->redirectToRoute('back_support_types');
        }
        return $this->render('back/support/types.html.twig', [
            'types' => $em->getRepository(SupportType::class)->findBy([], [ 'name' => 'ASC' ]),
            'formAdd' => $formAdd->createView()
        ]);        
    }

    /**
     * Page de mise à jour d'un type de support de diffusion
     * 
     * @param Request $request
     * @param Support $type
     * 
     * @return Response
     * 
     * @Route("/types/{type}", name="type_update")
     */
    public function typeUpdate(Request $request, SupportType $type): Response
    {  
        $em = $this->getDoctrine()->getManager();
        $formUpdate = $this->createForm(SupportTypeFormType::class, $type);
        $formUpdate->handleRequest($request);
        if ($formUpdate->isSubmitted() && $formUpdate->isValid()) {
            $em->persist($type);
            $em->flush();
            $this->addFlash('success', "Le type de support de diffusion a bien été modifié.");
            return $this->redirectToRoute('back_support_types');
        }
        return $this->render('back/support/type-update.html.twig', [
            'formUpdate' => $formUpdate->createView()
        ]);  
    }

    /**
     * Fonctionnalité de suppression d'un type de support de diffusion
     * 
     * @param Support $type
     * 
     * @return Response
     * 
     * @Route("/types/{type}/supprimer", name="type_delete")
     */
    public function typeDelete(SupportType $type): Response
    {  
        $em = $this->getDoctrine()->getManager(); 
        $em->remove($type);
        $em->flush();
        $this->addFlash('success', "Le type de support de diffusion a bien été supprimé.");
        return $this->redirectToRoute('back_support_types');
    }    
    
    /**
     * Page de listing des référentiels
     * 
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/referentiels", name="referentials")
     */
    public function referentials(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $referential = new Referential();
        $formAdd = $this->createForm(ReferentialType::class, $referential);
        $formAdd->handleRequest($request);
        if ($formAdd->isSubmitted() && $formAdd->isValid()) {
            $em->persist($referential);
            $em->flush();
            $this->addFlash('success', "Le référentiel a bien été ajouté.");
            return $this->redirectToRoute('back_support_referentials');
        }
        return $this->render('back/support/referentials.html.twig', [
            'referentials' => $em->getRepository(Referential::class)->findBy([], [ 'name' => 'ASC' ]),
            'formAdd' => $formAdd->createView()
        ]);        
    }

    /**
     * Page d'édition d'un référentiel
     * 
     * @param Request $request
     * @param Referential $referential
     * 
     * @return Response
     * 
     * @Route("/referentiels/{referential}", name="referential_update")
     */
    public function referentialUpdate(Request $request, Referential $referential): Response
    {  
        $em = $this->getDoctrine()->getManager();
        $formUpdate = $this->createForm(ReferentialType::class, $referential);
        $formUpdate->handleRequest($request);
        if ($formUpdate->isSubmitted() && $formUpdate->isValid()) {
            $em->persist($referential);
            $em->flush();
            $this->addFlash('success', "Le référentiel a bien été modifié.");
            return $this->redirectToRoute('back_support_referentials');
        }
        return $this->render('back/support/referential-update.html.twig', [
            'formUpdate' => $formUpdate->createView()
        ]);  
    }

    /**
     * Fonctionnalité de suppression d'un référentiel
     * 
     * @param Referential $referential
     * 
     * @return Response
     * 
     * @Route("/referentiels/{referential}/supprimer", name="referential_delete")
     */
    public function referentialDelete(Referential $referential): Response
    {  
        $em = $this->getDoctrine()->getManager(); 
        $em->remove($referential);
        $em->flush();
        $this->addFlash('success', "Le référentiel a bien été supprimé.");
        return $this->redirectToRoute('back_support_referentials');
    }

    /**
     * Page d'édition d'un support de diffusion
     * 
     * @param Support $support
     * @param Request $request
     * 
     * @return Response
     * 
     * @Route("/{support}", name="detail")
     */
    public function detail(Support $support, Request $request): Response
    {       
        $em = $this->getDoctrine()->getManager(); 
        $formPreview = $this->get('form.factory')->createNamed($support->getName(), StepSupportType::class, new Variant(), [
            'step' => $support,
            'demo' => true
        ]);
        $formSupport = $this->createForm(SupportFormType::class, $support);
        $formSupport->handleRequest($request);
        if ($formSupport->isSubmitted() && $formSupport->isValid()) {
            $support->setDateUpdate(new \DateTime());            
            $em->persist($support);
            $em->flush();
            $this->addFlash('success', "Le formulaire a bien été mis à jour.");
            return $this->redirectToRoute('back_support_detail', [
                'support' => $support->getId()
            ]);
        }
        $formForm = $this->get('form.factory')->createNamed('form', UploadType::class, null, [
            'fileType' => 'yaml'
        ]);
        $formForm->handleRequest($request);
        if ($formForm->isSubmitted() && $formForm->isValid()) {
            $support->setDateUpdate(new \DateTime());
            try {
                $this->importService->form($support, Yaml::parseFile($formForm->getData()->getPathName()));          
                $this->addFlash('success', "La structure du formulaire a bien été mis à jour.");
                return $this->redirectToRoute('back_support_detail', [
                    'support' => $support->getId()
                ]);
            }
            catch(\Exception $e){
                $formForm->addError(new FormError($e->getMessage()));
            }
        }
        $formAlgo = $this->get('form.factory')->createNamed('algorithm', UploadType::class, null, [
            'fileType' => 'yaml'
        ]);
        $formAlgo->handleRequest($request);
        if ($formAlgo->isSubmitted() && $formAlgo->isValid()) {
            $support->setDateUpdate(new \DateTime());
            try {
                $this->importService->algorithm($support, Yaml::parseFile($formAlgo->getData()->getPathName()));      
                $this->addFlash('success', "L'algorithme du formulaire a bien été mis à jour.");
                return $this->redirectToRoute('back_support_detail', [
                    'support' => $support->getId()
                ]);
            }
            catch(\Exception $e){
                $formAlgo->addError(new FormError($e->getMessage()));
            }
        }        
        return $this->render('back/support/detail.html.twig', [
            'formSupport' => $formSupport->createView(),
            'formForm' => $formForm->createView(),
            'formAlgo' => $formAlgo->createView(),
            'formPreview' => $formPreview->createView(),
        ]);
    }

    /**
     * Fonctionnalité de suppression d'un support de diffusion
     * 
     * @param Support $support
     * 
     * @return Response
     * 
     * @Route("/{support}/supprimer", name="delete")
     */
    public function delete(Support $support): Response
    {  
        $em = $this->getDoctrine()->getManager(); 
        $em->remove($support);
        $em->flush();
        $this->addFlash('success', "Le formulaire a bien été supprimé.");
        return $this->redirectToRoute('back_support_home');
    }

    /**
     * Fonctionnalité de publication/dépublication d'un support de diffusion
     * 
     * @param Support $support
     * 
     * @return Response
     * 
     * @Route("/{support}/publication", name="enable_toggle")
     */
    public function enableToggle(Support $support): Response
    {  
        $em = $this->getDoctrine()->getManager(); 
        $em = $this->getDoctrine()->getManager(); 
        $support->setEnabled(!$support->getEnabled());
        $em->persist($support);
        $em->flush();
        $action = $support->getEnabled() ? "activé" : "désactivé";
        $this->addFlash('success', "Le formulaire a bien été $action.");
        return $this->redirectToRoute('back_support_home');
    }

    /**
     * Fonctionnalité de téléchargement du fichier YAML de fomulaire d'un support de diffusion
     * 
     * @param Support $support
     * 
     * @return Response
     * 
     * @Route("/{support}/telecharger-formulaire", name="download_form")
     */
    public function downloadForm(Support $support): Response
    {  
        $array = [];
        $recursive = function(FormElement $element) use (&$recursive){
            $return = [];
            $return['label'] = $element->getLabel();
            $return['name'] = $element->getName();
            $return['type'] = $element->getType();
            if($element->getPhase())
                $return['phase'] = $element->getPhase();
            $return = array_merge($return, $element->getConfig());
            if($element->getChildren()->count() > 0){
                $return['children'] = [];
                foreach($element->getChildren() as $children){
                    $return['children'][] = $recursive($children);
                }  
            }                      
            return $return;
        };
        foreach($support->getFormElements() as $element){
            $array[] = $recursive($element);
        }        
        $yaml = Yaml::dump($array, 99, 2);
        $response = new Response();
        //$response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', "attachment;filename=form_{$support->getName()}.yaml");
        $response->setContent($yaml);
        return $response;
    }

    /**
     * Fonctionnalité de téléchargement du fichier YAML d'algorithme d'un support de diffusion
     * 
     * @param Support $support
     * 
     * @return Response
     * 
     * @Route("/{support}/telecharger-algorithme", name="download_algorithm")
     */
    public function downloadAlgorithm(Support $support): Response
    {  
        $array = [];
        $recursive = function(Formula $element) use (&$recursive){
            $return = [];
            $return['name'] = $element->getName();
            if($element->getPath())
                $return['path'] = $element->getPath();            
            if($element->getVars()->count() > 0){
                $return['vars'] = [];
                foreach($element->getVars() as $children){
                    $return['vars'][] = $recursive($children);
                }  
            }   
            if($element->getChildren()->count() > 0){
                $return['children'] = [];
                foreach($element->getChildren() as $children){
                    $return['children'][] = $recursive($children);
                }  
            }   
            if($element->getFormula()){
                $return['formula'] = $element->getFormula();   
            }                    
            return $return;
        };
        foreach($support->getFormulas() as $element){
            $array[] = $recursive($element);
        }        
        $yaml = Yaml::dump($array, 99, 2);
        $response = new Response();
        //$response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', "attachment;filename=algo_{$support->getName()}.yaml");
        $response->setContent($yaml);
        return $response;        
    }
}