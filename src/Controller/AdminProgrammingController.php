<?php

namespace App\Controller;

use App\Entity\AirHost;
use App\Entity\ProgramSlot;
use App\Form\AirHostType;
use App\Form\ProgramSlotType;
use App\Repository\AirHostRepository;
use App\Repository\ProgramSlotRepository;
use App\Station\StationBoard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminProgrammingController extends AbstractController
{
    public function __construct(
        private readonly AdminView $adminView,
        private readonly StationBoard $board,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProgramSlotRepository $slots,
        private readonly AirHostRepository $hosts,
    ) {
    }

    #[Route('/admin-panel/schedule/new', name: 'admin_schedule_new', methods: ['GET', 'POST'])]
    public function newSlot(Request $request): Response
    {
        $this->board->slotRecords();
        $slot = new ProgramSlot();
        $slot->setPosition($this->slots->nextPosition());

        return $this->slotForm($request, $slot, 'Add a show');
    }

    #[Route('/admin-panel/schedule/{id}/edit', name: 'admin_schedule_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editSlot(Request $request, ProgramSlot $slot): Response
    {
        return $this->slotForm($request, $slot, 'Edit show');
    }

    #[Route('/admin-panel/schedule/{id}/delete', name: 'admin_schedule_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteSlot(Request $request, ProgramSlot $slot): Response
    {
        $this->denyUnlessCsrf($request, 'delete-slot-'.$slot->getId());
        $this->entityManager->remove($slot);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Removed %s from the schedule.', $slot->getTitle()));

        return $this->redirectToRoute('admin_schedule');
    }

    #[Route('/admin-panel/team/new', name: 'admin_team_new', methods: ['GET', 'POST'])]
    public function newHost(Request $request): Response
    {
        $this->board->hostRecords();
        $host = new AirHost();
        $host->setPosition($this->hosts->nextPosition());
        $host->setAccent('#1a237e');

        return $this->hostForm($request, $host, 'Add a host');
    }

    #[Route('/admin-panel/team/{id}/edit', name: 'admin_team_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editHost(Request $request, AirHost $host): Response
    {
        return $this->hostForm($request, $host, 'Edit host');
    }

    #[Route('/admin-panel/team/{id}/delete', name: 'admin_team_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteHost(Request $request, AirHost $host): Response
    {
        $this->denyUnlessCsrf($request, 'delete-host-'.$host->getId());
        $this->entityManager->remove($host);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Removed %s from the on-air team.', $host->getName()));

        return $this->redirectToRoute('admin_team');
    }

    private function slotForm(Request $request, ProgramSlot $slot, string $heading): Response
    {
        $form = $this->createForm(ProgramSlotType::class, $slot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($slot);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Saved %s.', $slot->getTitle()));

            return $this->redirectToRoute('admin_schedule');
        }

        $response = $this->adminView->render('admin/form.html.twig', 'schedule', [
            'heading' => $heading,
            'form' => $form->createView(),
            'cancelPath' => $this->generateUrl('admin_schedule'),
        ]);

        if ($form->isSubmitted() && !$form->isValid()) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $response;
    }

    private function hostForm(Request $request, AirHost $host, string $heading): Response
    {
        $form = $this->createForm(AirHostType::class, $host);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($host);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Saved %s.', $host->getName()));

            return $this->redirectToRoute('admin_team');
        }

        $response = $this->adminView->render('admin/form.html.twig', 'team', [
            'heading' => $heading,
            'form' => $form->createView(),
            'cancelPath' => $this->generateUrl('admin_team'),
        ]);

        if ($form->isSubmitted() && !$form->isValid()) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $response;
    }

    private function denyUnlessCsrf(Request $request, string $tokenId): void
    {
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid form token.');
        }
    }
}
