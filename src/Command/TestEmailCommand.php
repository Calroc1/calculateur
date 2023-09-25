<?php
namespace App\Command;

use App\Service\Mailer\MailerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestEmailCommand extends Command
{
    protected static $defaultName = 'app:test:email';

    private $mailerService;
   
    /**
     * @param MailerService $mailerService
     */
    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(['...sending email...']);    

        $this->mailerService->sendCustom([
            ['email' => $input->getArgument('email')]
        ], 'TEST', 'Ceci est un test');

        $output->writeln(['...done...']);        
        return 0;
    }
}

