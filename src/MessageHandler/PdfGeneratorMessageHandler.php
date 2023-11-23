<?php
// src/MessageHandler/NewUserWelcomeEmailHandler.php
namespace App\MessageHandler;

use App\Entity\UserInfo;
use App\Form\UserInfoType;
use Doctrine\ORM\EntityManagerInterface;
use App\Message\PdfGenerator;
use Dompdf\Dompdf;
use Twig\Environment;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PdfGeneratorMessageHandler implements MessageHandlerInterface
{
    private $userInfoType;
    private $twig;
    private $entityManager;
    
    public function __construct(UserInfoType $UserInfoType, Environment $twig, EntityManagerInterface $entityManager) {
        $this->userInfoType = $UserInfoType;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    public function __invoke(PdfGenerator $pdfGenerator)
    {

        //call userinfo entity and query by ID pass by pdfgenerator
        $userInfo = $this->entityManager->getRepository(UserInfo::class)->find($pdfGenerator->getUserId());
    
        //do the pdf generation if user exist
        if ($userInfo) {

            $images = array();
            $image_data = json_decode($userInfo->getImages());

            foreach($image_data as $img_dt) {
                array_push($images, $this->imageToBase64('public/uploads/' . $img_dt));
            }

            $data = [
                'name'         => $userInfo->getName(),
                'description'  =>  $userInfo->getDescription(),
                'images'  => $images 
            ];

            $html =  $this->twig->render('user/pdf.html.twig', $data);
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->render();

            //upload the generated file in public/pdf
            $output = $dompdf->output();
            file_put_contents('public/pdf/user_' . $userInfo->getId() .  '.pdf', $output);

        }

    }

    //converts any characters, binary data, and even images or sound files into a readable string
    private function imageToBase64($path) {
        $path = $path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }
}