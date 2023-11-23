<?php

namespace App\Controller;

use App\Entity\UserInfo;
use App\Form\UserInfoType;
use App\Message\PdfGenerator;
use App\Repository\UserInfoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use Dompdf\Dompdf;
/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="app_user_index", methods={"GET"})
     */
    public function index(UserInfoRepository $userInfoRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'user_infos' => $userInfoRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_user_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserInfoRepository $userInfoRepository, MessageBusInterface $bus): Response
    {
        $userInfo = new UserInfo();
        $form = $this->createForm(UserInfoType::class, $userInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $fileNames = $this->upload_images($form);

            $userInfo->setImages($fileNames);
            $userInfoRepository->add($userInfo, true);

            //this will dispatch the transport for generating pdf
            $bus->dispatch(new PdfGenerator($userInfo->getId()));
            
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user_info' => $userInfo,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_user_show", methods={"GET"})
     */
    public function show(UserInfo $userInfo): Response
    {
        return $this->render('user/show.html.twig', [
            'user_info' => $userInfo,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UserInfo $userInfo, UserInfoRepository $userInfoRepository): Response
    {
        $form = $this->createForm(UserInfoType::class, $userInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userInfoRepository->add($userInfo, true);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user_info' => $userInfo,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_user_delete", methods={"POST"})
     */
    public function delete(Request $request, UserInfo $userInfo, UserInfoRepository $userInfoRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userInfo->getId(), $request->request->get('_token'))) {
            $userInfoRepository->remove($userInfo, true);
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    private function upload_images($form) 
    {
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('images')->getData();
        $fileNames = array();
        if ($imageFile) {
            foreach ($imageFile as $imgFile) {
                $originalFilename = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);

                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imgFile->guessExtension();

                // Move the file to the directory
                try {
                    $imgFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    echo 'Impossible d\'enregistrer l\'image';
                }
                
                array_push($fileNames, $newFilename);
                
            }
        }

        return $fileNames;
    }

}
