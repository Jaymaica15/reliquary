<?php

namespace App\Controller;

use App\Entity\Relic;
use App\Entity\Saint;
use App\Enum\RelicDegree;
use App\Enum\RelicStatus;
use App\Form\RelicType;
use App\Repository\RelicRepository;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/relic')]
final class RelicController extends AbstractController
{
    #[Route(name: 'app_relic_index', methods: ['GET'])]
    public function index(Request $request, RelicRepository $relicRepository, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');

        $pagination = $paginator->paginate(
            $relicRepository->findAllQuery($filter, $this->getUser()),
            $request->query->getInt('page', 1),
        );

        return $this->render('relic/index.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'relic_degrees' => RelicDegree::cases(),
        ]);
    }

    #[Route('/desktop', name: 'app_relic_desktop', methods: ['GET'])]
    public function desktopList(Request $request, RelicRepository $relicRepository, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');

        $pagination = $paginator->paginate(
            $relicRepository->findAllQuery($filter, $this->getUser()),
            $request->query->getInt('page', 1),
        );

        return $this->render('relic/_relic_list_desktop.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'relic_degrees' => RelicDegree::cases(),
        ]);
    }

    #[Route('/mobile', name: 'app_relic_mobile', methods: ['GET'])]
    public function mobileList(Request $request, RelicRepository $relicRepository, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');

        $pagination = $paginator->paginate(
            $relicRepository->findAllQuery($filter, $this->getUser()),
            $request->query->getInt('page', 1),
        );

        return $this->render('relic/_relic_list_mobile.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'relic_degrees' => RelicDegree::cases(),
        ]);
    }

    #[Route('/my-relics', name: 'app_my_relics', methods: ['GET'])]
    public function myRelics(Request $request, RelicRepository $relicRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $filter = $request->query->get('filter');

        $pagination = $paginator->paginate(
            $relicRepository->findByCreatorQuery($user, $filter),
            $request->query->getInt('page', 1),
        );

        return $this->render('relic/index.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'relic_degrees' => RelicDegree::cases(),
            'title' => 'My Relics'
        ]);
    }

    #[Route('/my-relics/desktop', name: 'app_my_relics_desktop', methods: ['GET'])]
    public function myRelicsDesktop(Request $request, RelicRepository $relicRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $filter = $request->query->get('filter');

        $pagination = $paginator->paginate(
            $relicRepository->findByCreatorQuery($user, $filter),
            $request->query->getInt('page', 1),
        );

        return $this->render('relic/_relic_list_desktop.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'relic_degrees' => RelicDegree::cases(),
            'title' => 'My Relics'
        ]);
    }

    #[Route('/my-relics/mobile', name: 'app_my_relics_mobile', methods: ['GET'])]
    public function myRelicsMobile(Request $request, RelicRepository $relicRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $filter = $request->query->get('filter');

        $pagination = $paginator->paginate(
            $relicRepository->findByCreatorQuery($user, $filter),
            $request->query->getInt('page', 1),
        );

        return $this->render('relic/_relic_list_mobile.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'relic_degrees' => RelicDegree::cases(),
            'title' => 'My Relics'
        ]);
    }

    #[Route('/new', name: 'app_relic_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ImageService $imageService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $relic = new Relic();
        $form = $this->createForm(RelicType::class, $relic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $relic->setCreator($this->getUser());

            // All relics start with PENDING status regardless of user role
            $relic->setStatus(RelicStatus::PENDING);

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $image = $imageService->createRelicImage($imageFile, $relic, $this->getUser());
                $relic->addImage($image);
            }

            $entityManager->persist($relic);
            $entityManager->flush();

            $this->addFlash('success', 'Relic submitted successfully and awaiting approval');

            return $this->redirectToRoute('app_relic_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('relic/new.html.twig', [
            'relic' => $relic,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_relic_show', methods: ['GET'])]
    public function show(Relic $relic, RelicRepository $relicRepository): Response
    {
        // Check if the user has permission to view this relic
        if (!$relicRepository->canViewRelic($relic, $this->getUser())) {
            throw $this->createAccessDeniedException('You do not have permission to view this relic.');
        }
        
        return $this->render('relic/show.html.twig', [
            'relic' => $relic,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_relic_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Relic $relic, EntityManagerInterface $entityManager, ImageService $imageService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(RelicType::class, $relic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image removal
            $imagesToRemove = $request->request->all('remove_images');
            if (!empty($imagesToRemove)) {
                foreach ($imagesToRemove as $imageId) {
                    $image = $entityManager->getRepository(\App\Entity\RelicImage::class)->find($imageId);
                    if ($image && $image->getRelic() === $relic) {
                        $imageService->deleteImage($image);
                        $relic->removeImage($image);
                        $entityManager->remove($image);
                    }
                }
            }

            // Handle new image upload
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $image = $imageService->createRelicImage($imageFile, $relic, $this->getUser());
                $relic->addImage($image);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Relic updated successfully');
            return $this->redirectToRoute('app_relic_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('relic/edit.html.twig', [
            'relic' => $relic,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_relic_delete', methods: ['POST'])]
    public function delete(Request $request, Relic $relic, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$relic->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($relic);
            $entityManager->flush();
            $this->addFlash('success', 'Relic deleted successfully');
        }

        return $this->redirectToRoute('app_relic_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/saint/{saint}/desktop', name: 'app_saint_relics_desktop', methods: ['GET'])]
    public function saintRelicsDesktop(Saint $saint, RelicRepository $relicRepository): Response
    {
        if (!$saint->isIncomplete()) {
            throw $this->createNotFoundException('Saint not found');
        }

        return $this->render('relic/_relic_list_desktop.html.twig', [
            'relics' => $relicRepository->findBySaintWithVisibility($saint->getId(), $this->getUser()),
        ]);
    }

    #[Route('/saint/{saint}/mobile', name: 'app_saint_relics_mobile', methods: ['GET'])]
    public function saintRelicsMobile(Saint $saint, RelicRepository $relicRepository): Response
    {
        if (!$saint->isIncomplete()) {
            throw $this->createNotFoundException('Saint not found');
        }
        
        return $this->render('relic/_relic_list_mobile.html.twig', [
            'relics' => $relicRepository->findBySaintWithVisibility($saint->getId(), $this->getUser()),
        ]);
    }

}
