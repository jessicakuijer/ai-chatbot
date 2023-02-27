<?php

namespace App\Entity;

use App\Repository\AdminUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdminUserRepository::class)]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    public bool $active = true;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $firstname = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Email]
    public ?string $email = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $lastname = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $password = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $resetPasswordToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    public ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    public ?\DateTime $updatedAt = null;

    public ?string $_plainPassword = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->updatedAt = new \DateTime('now');
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstname, $this->lastname);
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        $this->_plainPassword = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }
}
