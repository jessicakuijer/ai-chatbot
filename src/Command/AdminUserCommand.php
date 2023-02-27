<?php

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminUserCommand extends Command
{
    public const CMD_NAME = 'app:create:admin';

    private SymfonyStyle $io;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct(self::CMD_NAME);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $firstname = $this->io->askQuestion(new Question('Firstname'));
        $lastname = $this->io->askQuestion(new Question('Lastname'));
        $email = $this->io->askQuestion(new Question('Email (must be unique)'));
        $password = $this->io->askQuestion(new Question('Password'));

        $user = new AdminUser();
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->_plainPassword = $password;

        $user->password = $this->passwordHasher->hashPassword($user, $user->_plainPassword);

        $this->em->persist($user);
        $this->em->flush();

        $this->io->success('Administrateur créé.');

        return self::SUCCESS;
    }
    
}
