<?php

namespace App\Service\Mailer;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Brevo\Client\Configuration;
use Brevo\Client\Api as BrevoApi;

/**
 * Ce service contient les fonctionnalités d'envoi d'email de la plateforme
 */
class MailerService
{
    protected $router;
    protected $params;
    protected $brevoApi;

    protected $sender = [
        'email' => 'databilobay@gmail.com',
        'name' => 'Bilobay'
    ];

    /**
     * @param UrlGeneratorInterface $router
     * @param ParameterBagInterface $params
     * @param string $apiKey Clé Api Brevo
     */
    public function __construct(UrlGeneratorInterface $router, ParameterBagInterface $params, string $apiKey){
        $this->router = $router;
        $this->params = $params;
        
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $this->brevoApi = new BrevoApi\TransactionalEmailsApi(null, $config); // service Brevo
    }
    
    /**
     * Permet d'envoyer un email sur la base d'un template Brevo
     * 
     * @param array $to
     * @param int $templateId Brevo template ID
     * @param array $params
     * @param array $attachments
     * 
     * @return void
     */
    public function sendTemplate(array $to, int $templateId, array $params = [], array $attachments = []): void
    {         
        $sendEmail = new \Brevo\Client\Model\SendSmtpEmail([
            'to' => $to,            
            'templateId' => $templateId,
            'params' => $params,
            //'attachment' => $attachments
        ]);
        if(!empty($attachments))
            $sendEmail->setAttachment($attachments);
        $this->brevoApi->sendTransacEmail($sendEmail);
    }

    /**
     * Permet d'envoyer un email avec un sujet et un corps personnalisé
     * 
     * @param array $tos
     * @param string $subject
     * @param string $html
     * @param array $attachments
     * 
     * @return [type]
     */
    public function sendCustom(array $tos, string $subject, string $html, array $attachments = []){  
        $sendEmail = new \Brevo\Client\Model\SendSmtpEmail([
            'sender' => $this->sender,   
            'subject' => $subject,   
            'htmlContent' => $html,
            //'attachment' => $attachments
        ]);
        if(!empty($attachments))
            $sendEmail->setAttachment($attachments);
        foreach($tos as $to){      
            $sendEmail->setTo([$to]);
            $this->brevoApi->sendTransacEmail($sendEmail);
        }
    }           
}