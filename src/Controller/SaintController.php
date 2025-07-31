<?php

namespace App\Controller;

use App\Entity\Saint;
use App\Form\SaintType;
use App\Repository\SaintRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/saint')]
final class SaintController extends AbstractController
{
    #[Route(name: 'app_saint_index', methods: ['GET'])]
    public function index(Request $request, SaintRepository $saintRepository, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');

        return $this->render('saint/index.html.twig', [
            'filter' => $filter,
            'title' => isset($searchTerm) ? 'Search Results for "' . $searchTerm . '"' : null,
            'canonical_statuses' => \App\Enum\CanonicalStatus::cases(),
        ]);
    }

    #[Route('/desktop', name: 'app_saint_desktop', methods: ['GET'])]
    public function desktopList(Request $request, SaintRepository $saintRepository, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');
        $searchTerm = $request->query->get('q');

        $pagination = $paginator->paginate(
            $saintRepository->findAllQuery($filter, $searchTerm),
            $request->query->getInt('page', 1),
        );

        return $this->render('saint/_saint_list_desktop.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
        ]);
    }

    #[Route('/mobile', name: 'app_saint_mobile', methods: ['GET'])]
    public function mobileList(Request $request, SaintRepository $saintRepository, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');
        $searchTerm = $request->query->get('q');

        $pagination = $paginator->paginate(
            $saintRepository->findAllQuery($filter, $searchTerm),
            $request->query->getInt('page', 1),
        );

        return $this->render('saint/_saint_list_mobile.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'canonical_statuses' => \App\Enum\CanonicalStatus::cases(),
        ]);
    }

    #[Route('/new', name: 'app_saint_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $saint = new Saint();
        $form = $this->createForm(SaintType::class, $saint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($saint);
            $entityManager->flush();

            $this->addFlash('success', 'Saint created successfully');
            return $this->redirectToRoute('app_saint_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('saint/new.html.twig', [
            'saint' => $saint,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_saint_show', methods: ['GET'])]
    public function show(Saint $saint): Response
    {
        if ($saint->isIncomplete()) {
            throw $this->createNotFoundException('Saint not found');
        }

        return $this->render('saint/show.html.twig', [
            'saint' => $saint,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_saint_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Saint $saint, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(SaintType::class, $saint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Saint updated successfully');
            return $this->redirectToRoute('app_saint_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('saint/edit.html.twig', [
            'saint' => $saint,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_saint_delete', methods: ['POST'])]
    public function delete(Request $request, Saint $saint, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$saint->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($saint);
            $entityManager->flush();
            $this->addFlash('success', 'Saint deleted successfully');
        }

        return $this->redirectToRoute('app_saint_index', [], Response::HTTP_SEE_OTHER);
    }
}
