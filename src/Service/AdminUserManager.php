<?php

namespace App\Service;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AdminUserManager
{
    public function __construct(
        private readonly AdminUserRepository $repository,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function findOneByEmail(string $email): ?AdminUser
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findOneByResetPasswordToken(string $token): ?AdminUser
    {
        return $this->repository->findOneBy(['resetPasswordToken' => $token]);
    }

    /**
     * @throws \Exception
     */
    public function generateResetPasswordToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function save(AdminUser $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}
