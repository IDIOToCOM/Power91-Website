<?php

namespace App\Controller;

use App\Entity\User;
use App\Station\StationBoard;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AdminController extends AbstractController
{
    public function __construct(
        private readonly AdminView $adminView,
        private readonly StationBoard $board,
    ) {
    }

    #[Route('/admin-panel', name: 'admin_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $user->isAdmin()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $response = $this->render('admin/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => null !== $authenticationUtils->getLastAuthenticationError(),
        ]);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    #[Route('/admin-panel/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function dashboard(): Response
    {
        return $this->board('admin/dashboard.html.twig', 'overview');
    }

    #[Route('/admin-panel/schedule', name: 'admin_schedule', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function schedule(): Response
    {
        return $this->board('admin/schedule.html.twig', 'schedule', [
            'slots' => $this->board->slotRecords(),
        ]);
    }

    #[Route('/admin-panel/live', name: 'admin_live', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function live(): Response
    {
        return $this->board('admin/live.html.twig', 'live');
    }

    #[Route('/admin-panel/team', name: 'admin_team', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function team(): Response
    {
        return $this->board('admin/team.html.twig', 'team', [
            'hosts' => $this->board->hostRecords(),
        ]);
    }

    #[Route('/admin-panel/logout', name: 'admin_logout')]
    public function logout(): never
    {
        throw new \LogicException('The security firewall handles logout.');
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function board(string $template, string $section, array $extra = []): Response
    {
        return $this->adminView->render($template, $section, $extra);
    }
}
