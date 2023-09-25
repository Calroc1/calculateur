<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\User;

/**
 * Cette commande permet de créer un utilisateur
 */
class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create:user';
    
    private $em;
    private $passwordEncoder;
    
    /**
     * @param EntityManagerInterface $em 
     * @param UserPasswordEncoderInterface $passwordEncoder 
     */
    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(['Creating user..']);  
        
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');        
       
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $user->setEnabled(1);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(['...done...']);        
        return 0;
    }
}

