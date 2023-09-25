<?php


namespace App\Service\Mailer;

use App\Entity\Admin;
use App\Entity\Campaign\Variant;
use App\Entity\User;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

/**
 * Ce service contient les fonctionnalités d'envoi d'email aux superadmins de la plateforme
 */
class AdminMailerService  extends MailerService
{
    protected $twig;
    protected $em;
    protected $superadmins = [];

    protected $subjectPrefix = '[CALCULATEUR BILOBAY] ';

    
    public function __construct(UrlGeneratorInterface $router, ParameterBagInterface $params, Environment $twig, EntityManagerInterface $em,  string $apiKey){
        parent::__construct($router, $params, $apiKey);
        $this->twig = $twig;
        $this->em = $em;
        foreach($this->em->getRepository(Admin::class)->findAll() as $admin){
            $this->superadmins[] = ['email' => $admin->getEmail() ];
        }
    }

    /**
     * Permet d'envoyer un email aux superadmins sur la base d'un template twig
     * 
     * @param string $subject
     * @param string $templatePath Chemin vers le template twig
     * @param array $params Variables à fournir au template twig
     * 
     * @return void
     */
    private function send(string $subject, string $templatePath, array $params = []): void
    {
        $this->sendCustom($this->superadmins, $this->subjectPrefix.$subject, $this->twig->render('emails/admin/'.$templatePath, $params));
    }

    /**
     * Envoi de l'email lié à la fonctionnalité "Nous contacter"
     * 
     * @param User $user Auteur de la demande de contact
     * @param string $message Message renseigné par l'utilisateur
     * 
     * @return void
     */
    public function message(User $user, string $message): void
    {
        $this->send('Nouveau message', 'message.html.twig', [
            'user' => $user,
            'message' => $message
        ]);
    }

    /**
     * Envoi de l'email lié à une demande spéciales dans le cadre d'une campagne 
     * 
     * @param User $user Auteur de la demande
     * @param Variant $variant Variante de la campagne concernée
     * @param string $request Message  renseigné par l'utilisateur
     * @param bool $update Définit s'il s'agit d'une mise à jour de demande
     * 
     * @return void
     */
    public function request(User $user, Variant $variant, string $request, bool $update = false): void
    {
        $subject = 'Nouvelle demande supplémentaire pour une campagne';
        if($update)
            $subject = 'Demande supplémentaire modifiée pour une campagne';
        $this->send($subject, 'request.html.twig', [
            'variant' => $variant,
            'request' => $request
        ]);
    }
}