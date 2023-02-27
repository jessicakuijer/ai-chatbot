<?php

namespace App\Security;

use App\Entity\AdminUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AdminUser) {
            return;
        }

        if (!$user->active) {
            throw new CustomUserMessageAccountStatusException('account_disabled');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
