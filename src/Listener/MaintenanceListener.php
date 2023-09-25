<?php

namespace App\Listener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class MaintenanceListener
{
    private $lockFilePath = '../.lock';
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function onKernelRequest(RequestEvent $event)
    {              
        if(!file_exists($this->lockFilePath)) {
            return;
        }
        $event->setResponse(new Response($this->twig->render('front/maintenance.html.twig')));
        $event->stopPropagation();
    }
}