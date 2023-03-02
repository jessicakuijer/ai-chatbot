<?php

namespace App\Controller;

use Symfony\Component\Console\Exception\LogicException;
use App\Form\ResetPassword\ResetPasswordRequestType;
use App\Form\ResetPassword\ResetPasswordType;
use App\Service\MailerService;
use App\Service\AdminUserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public const LOGIN_ROUTE = 'app_admin_security_login';
    public const LOGOUT_ROUTE = 'app_admin_security_logout';

    public function __construct(
        private readonly AdminUserManager $adminUserManager,
/*         private readonly MailerService $mailerService */
    ) {
    }

    #[Route('/login', name: self::LOGIN_ROUTE)]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('chat_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }


    #[Route('/chat', name: 'chat_index')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_admin_security_login');
        }

        return $this->render('chat/index.html.twig');
    }

    #[Route('/logout', name: self::LOGOUT_ROUTE)]
    public function logout(): Response
    {
        throw new LogicException();
    }

    /* TODO: Add a password reset functionality */
    
    /* #[Route('/reset-password-request')]
    public function resetPasswordRequest(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()['email'];
            $user = $this->adminUserManager->findOneByEmail($email);

            if (null !== $user) {
                $user->resetPasswordToken = $this->adminUserManager->generateResetPasswordToken();
                $this->adminUserManager->save($user);

                $this->mailerService->sendResetPasswordEmail($user);
            }

            return $this->redirectToRoute('app_admin_security_resetpasswordrequestsuccess');
        }

        return $this->render('admin/security/reset_password_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/success')]
    public function resetPasswordRequestSuccess(): Response
    {
        return $this->render('admin/security/reset_password_request_success.html.twig');
    }

    #[Route('/reset-password/fail')]
    public function resetPasswordRequestFail(): Response
    {
        return $this->render('admin/security/reset_password_request_fail.html.twig');
    }

    #[Route('/reset-password/{token}')]
    public function resetPassword(Request $request, string $token, UserPasswordHasherInterface $hasher): Response
    {
        $user = $this->adminUserManager->findOneByResetPasswordToken($token);
        if (null === $user) {
            return $this->redirectToRoute('app_admin_security_resetpasswordrequestfail');
        }

        $form = $this->createForm(ResetPasswordType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->password = $hasher->hashPassword($user, $user->_plainPassword);
            $user->resetPasswordToken = null;

            $this->adminUserManager->save($user);

            return $this->redirectToRoute('app_admin_security_login');
        }

        return $this->render('admin/security/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $user->resetPasswordToken,
        ]);
    } */
}
