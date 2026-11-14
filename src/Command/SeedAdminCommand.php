<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-admin', description: 'Create or update the pre-made admin account.')]
final class SeedAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%env(ADMIN_USERNAME)%')]
        private string $username,
        #[Autowire('%env(ADMIN_PASSWORD)%')]
        private string $plainPassword,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = strtolower(trim($this->username));

        if ('' === $username || '' === $this->plainPassword) {
            $io->error('ADMIN_USERNAME and ADMIN_PASSWORD must be set.');

            return Command::FAILURE;
        }

        $user = $this->users->findOneByUsername($username) ?? new User();
        $user->setUsername($username);
        $user->setRole(User::ROLE_ADMIN);
        $user->setPassword($this->passwordHasher->hashPassword($user, $this->plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin account ready for %s.', $username));

        return Command::SUCCESS;
    }
}
