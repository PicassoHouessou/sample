<?php

namespace App\Controller\Admin;

use App\Controller\Referer;
use App\Entity\Project;
use App\Entity\ProjectCategory;
use App\Entity\ProjectStatus;
use App\Form\Project\ProjectAttachmentFileType;
use App\Form\Project\ProjectCategoriesType;
use App\Form\Project\ProjectContactType;
use App\Form\Project\ProjectCoverPhotoFileType;
use App\Form\Project\ProjectDescriptionType;
use App\Form\Project\ProjectEmailType;
use App\Form\Project\ProjectLogoFileType;
use App\Form\Project\ProjectSummaryType;
use App\Form\Project\ProjectTitleType;
use App\Form\Project\ProjectWebsiteType;
use App\Form\ProjectCategoryType;
use App\Form\ProjectStatusType;
use App\Repository\ProfessionRepository;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use App\Repository\ProjectStatusRepository;
use Doctrine\Persistence\ObjectManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    use Referer ;
    /**
     * @Route("/admin/project/pending-submission", name="app_admin_project_pending_submission")
     * @param Request $request
     * @param ProjectRepository $projectRepository
     * @param SessionInterface $session
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, ProjectRepository $projectRepository, SessionInterface $session, PaginatorInterface $paginator)
    {
        $data = $projectRepository->findBy(['isDraft'=> false, 'isEnabled' => false], ['createdAt' => 'DESC']);

        $pagination = $paginator->paginate($data,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 50)
        );

        return $this->render('admin/project/pending_submission.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * Pour l'affichage des projets publiés
     * @Route("/admin/project/post" , name="app_admin_project_post")
     * @param Request $request
     * @param ProjectRepository $projectRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function post(Request $request, ProjectRepository $projectRepository, PaginatorInterface $paginator)
    {
        $data = $projectRepository->findBy(['isEnabled' => true]);
        //$data = $projectRepository->findBy([], ['createdAt']);

        $pagination = $paginator->paginate($data,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 50)
        );

        return $this->render('admin/project/post.html.twig', [
            'pagination' => $pagination,
        ]);

    }

    /**
     * @Route("/admin/project/details/{slug}" , name="app_admin_project_details")
     */
    public function details(Request $request, SessionInterface $session, Project $project)
    {
        return $this->render('admin/project/details.html.twig', [
            'project' => $project
        ]);
    }

    public function inInvestment(Request $request, ProjectRepository $projectRepository)
    {
        return $this->render('admin/project/in_investment.html.twig');
    }

    /**
     * @Route("/admin/project/delete/{id}" , name="app_admin_project_delete")
     */
    public function delete(Project $project, ObjectManager $manager, SessionInterface $session)
    {
        $projectTitle = $project->getTitle();

        $manager->remove($project);
        $manager->flush();

        $this->addFlash('success', 'Le projet <' . $projectTitle . '> a été supprimé');

        return $this->redirectToRoute('app_admin_project_post');

    }

    /**
     * @Route("/admin/project/disable/{id}", name="app_admin_project_disable")
     * @param Project $project
     * @param ObjectManager $manager
     * @return RedirectResponse
     */
    public function disable(Project $project, ObjectManager $manager)
    {
        $isEnabled = $project->getIsEnabled();
        $isEnabled = ($isEnabled === true) ? false : true;

        $project->setIsEnabled($isEnabled);
        $manager->persist($project);
        $manager->flush();

       // $params = $this->getRefererParams();

        if ($isEnabled) {
            $this->addFlash('success', "Le projet est activé");

            return $this->redirectToRoute('app_admin_project_post') ;
/*
            return $this->redirect($this->generateUrl(
                $params['_route']
            ));
            */

        } else {
            $this->addFlash('success', "Le projet est désactivé");
            return $this->redirectToRoute('app_admin_project_post') ;
            /*
            return $this->redirect($this->generateUrl(
                $params['_route']
            ));
            */
        }
    }

    /**
     * @Route("/admin/project/category/add", name="app_admin_project_options_category_add")
     */
    public function addCategory(Request $request, ObjectManager $manager)
    {
        $projectCategory = new ProjectCategory();

        $form = $this->createForm(ProjectCategoryType::class, $projectCategory);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($projectCategory);
            $manager->flush();

            $this->addFlash('success', "La nouvelle categorie de projet a été bien créée");

            return $this->redirectToRoute('app_admin_project_options_category_all');
        }

        return $this->render('admin/project/project_category_add.html.twig', [
            'projectCategoryForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/project/category/all", name="app_admin_project_options_category_all")
     * @param Request $request
     * @param ProjectCategoryRepository $projectCategoryRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function allCategory(Request $request, ProjectCategoryRepository $projectCategoryRepository, PaginatorInterface $paginator)
    {
        $data = $projectCategoryRepository->findAll();

        $pagination = $paginator->paginate($data,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 50)
        );

        return $this->render('admin/project/project_category_all.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/admin/project/category/edit/{id}", name="app_admin_project_options_category_edit")
     * @param Request $request
     * @param ProjectCategory $projectCategory
     * @param ObjectManager $manager
     * @return RedirectResponse|Response
     */

    public function editCategory(Request $request, ProjectCategory $projectCategory, ObjectManager $manager)
    {
        $form = $this->createForm(ProjectCategoryType::class, $projectCategory);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($projectCategory);
            $manager->flush();

            $this->addFlash('success', "La catégorie de projet a été bien mis à jour");

            return $this->redirectToRoute('app_admin_project_options_category_all');
        }

        return $this->render('admin/project/project_category_edit.html.twig', [
            'projectCategoryForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/project/category/delete/{id}", name="app_admin_project_options_category_delete")
     * @param Request $request
     * @param ProjectCategory $projectCategory
     * @param ObjectManager $manager
     * @param SessionInterface $session
     * @return RedirectResponse
     */

    public function deleteCategory( ProjectCategory $projectCategory, ObjectManager $manager, SessionInterface $session)
    {
        $projectCategoryName = $projectCategory->getName();
        $manager->remove($projectCategory);
        $manager->flush();
        $this->addFlash('success', "The category " . $projectCategoryName . " was removed");

        return $this->redirectToRoute('app_admin_project_options_category_all') ;

        /*
        $params = $this->getRefererParams() ;


        return $this->redirect($this->generateUrl(
            $params['_route']
        ));
        */
    }

    /**
     * @Route("/admin/project/status/all", name="app_admin_project_options_status_all")
     * @param Request $request
     * @param ProjectStatusRepository $projectStatusRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function allStatus(Request $request, ProjectStatusRepository $projectStatusRepository, PaginatorInterface $paginator)
    {
        $data = $projectStatusRepository->findAll();

        $pagination = $paginator->paginate($data,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 50)
        );

        return $this->render('admin/project/project_status_all.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/admin/project/status/add", name="app_admin_project_options_status_add")
     * @param Request $request
     * @param ObjectManager $manager
     * @return RedirectResponse|Response
     */
    public function addStatus(Request $request, ObjectManager $manager)
    {
        $projectStatus = new ProjectStatus();

        $form = $this->createForm(ProjectStatusType::class, $projectStatus);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($projectStatus);
            $manager->flush();

            $this->addFlash('success', "Le nouveau status de projet a été créé avec success");

            return $this->redirectToRoute('app_admin_project_options_status_all');
        }

        return $this->render('admin/project/project_status_add.html.twig', [
            'projectStatusForm' => $form->createView()
        ]);
    }

    /**
     * Pour éditer le status d'un projet
     *
     * il s'agit d'activer ou de désactiver un projet
     * @Route("/admin/project/status/edit/{id}", name="app_admin_project_options_status_edit")
     * @param Request $request
     * @param ObjectManager $manager
     * @param ProjectStatus $projectStatus
     * @return RedirectResponse|Response
     */

    public function editStatus(Request $request, ObjectManager $manager, ProjectStatus $projectStatus)
    {
        $form = $this->createForm(ProjectStatusType::class, $projectStatus);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($projectStatus);
            $manager->flush();

            $this->addFlash('success', "Le status de projet a été bien mis à jour");

            return $this->redirectToRoute('app_admin_project_options_status_all');
        }
        return $this->render('admin/project/project_status_edit.html.twig', [
            'projectStatusForm' => $form->createview()
        ]);
    }

    /**
     * @Route("/admin/project/status/delete/{id}", name="app_admin_project_options_status_delete")
     */
    public function deleteStatus(ObjectManager $manager, ProjectStatus $projectStatus)
    {
        $projectStatusName = $projectStatus->getName();

        $manager->remove($projectStatus);
        $manager->flush();

        $this->addFlash('success', 'Project <' . $projectStatusName . '> are successfully removed');

        return $this->redirectToRoute('app_admin_project_post') ;
        /*
        $params = $this->getRefererParams();
        return $this->redirect($this->generateUrl(
            $params['_route']
        ));
        */
    }


    /**
     * Pour la modification d'un projet
     *
     * Modifie individuellement tous les paramètres d'un projet
     *
     * @Route("/admin/project/edit/{id}",name="app_admin_project_edit")
     * @param Request $request
     * @param Project $project
     * @param ObjectManager $manager
     * @return Response
     */
    public function editProject(Request $request, Project $project, ObjectManager $manager)
    {
        $formTitle = $this->createForm(ProjectTitleType::class, $project);
        $formStatus = $this->createForm(\App\Form\Project\ProjectStatusType::class, $project);
        $formEmail = $this->createForm(ProjectEmailType::class, $project);
        $formContact = $this->createForm(ProjectContactType::class, $project);
        $formWebsite = $this->createForm(ProjectWebsiteType::class, $project);
        $formCategories = $this->createForm(ProjectCategoriesType::class, $project);
        $formSummary = $this->createForm(ProjectSummaryType::class, $project);
        $formDescription = $this->createForm(ProjectDescriptionType::class, $project);
        $formLogoFile = $this->createForm(ProjectLogoFileType::class, $project);
        $formCoverPhotoFile = $this->createForm(ProjectCoverPhotoFileType::class, $project);
        $formAttachmentFile = $this->createForm(ProjectAttachmentFileType::class, $project);

        $formTitle->handleRequest($request);
        $formStatus->handleRequest($request);
        $formEmail->handleRequest($request);
        $formContact->handleRequest($request);
        $formWebsite->handleRequest($request);
        $formCategories->handleRequest($request);
        $formSummary->handleRequest($request);
        $formDescription->handleRequest($request);
        $formLogoFile->handleRequest($request);
        $formCoverPhotoFile->handleRequest($request);
        $formAttachmentFile->handleRequest($request);

        if ($formTitle->isSubmitted() && $formTitle->isValid()) {
            $manager->persist($project);
            $manager->flush();

        } else if ($formStatus->isSubmitted() && $formStatus->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formEmail->isSubmitted() && $formEmail->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formContact->isSubmitted() && $formContact->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formWebsite->isSubmitted() && $formWebsite->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formCategories->isSubmitted() && $formCategories->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formSummary->isSubmitted() && $formSummary->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formDescription->isSubmitted() && $formDescription->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formLogoFile->isSubmitted() && $formLogoFile->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formCoverPhotoFile->isSubmitted() && $formCoverPhotoFile->isValid()) {
            $manager->persist($project);
            $manager->flush();
        } else if ($formAttachmentFile->isSubmitted() && $formAttachmentFile->isValid()) {
            $manager->persist($project);
            $manager->flush();
        }

        return $this->render('admin/project/edit_project.html.twig', [
            'project' => $project,
            'formTitle' => $formTitle->createView(),
            'formStatus' => $formStatus->createView(),
            'formEmail' => $formEmail->createView(),
            'formContact' => $formContact->createView(),
            'formWebsite' => $formWebsite->createView(),
            'formCategories' => $formCategories->createView(),
            'formSummary' => $formSummary->createView(),
            'formDescription' => $formDescription->createView(),
            'formLogoFile' => $formLogoFile->createView(),
            'formCoverPhotoFile' => $formCoverPhotoFile->createView(),
            'formAttachmentFile' => $formAttachmentFile->createView(),
        ]);
    }

}