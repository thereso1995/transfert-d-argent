<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin/add", name="admin.service.show")
     */
    public function addService(ServiceRepository $repos)
    {
        $services =$repos ->findAll();
        return $this->render('admin/index.html.twig', [
            '$services' => $services,
        ]);
    }
     
    
}
