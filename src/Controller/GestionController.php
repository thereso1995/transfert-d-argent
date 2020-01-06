<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * @Route("/api")
 */

class GestionController extends AbstractFOSRestController
{

    private $actif;
    private $message;
    private $status;
    private $bloqueStr;
    public function __construct()
    {
        $this->actif="Actif";
        $this->message="message";
        $this->status="status";
        $this->bloqueStr='Bloqué';
    }


     /**
     * @Route("/list/utilisateur", name="list_utilisateur", methods={"GET"})
     */
    public function lister(UserRepository $UserRepository, SerializerInterface $serializer)
    {
        $user = $UserRepository->findAll();
        $data = $serializer->serialize($user, 'json');

        return new Response($data, 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
    * @Route("/bloque/user/{id}", name="bloque_user", methods={"GET"})
    */ 
    public function bloquer(UserInterface $Userconnecte,EntityManagerInterface $entityManager, User $user=null)
    {
        
        if(!$user){
            throw new HttpException(404,'Cet utilisateur n\'existe pas !');
        }
        if($user==$Userconnecte){
            throw new HttpException(403,'Impossible de se bloquer soit même !');
        }
        elseif($user->getId()==1){
            throw new HttpException(403,'Impossible de bloquer le super-admin principal !');
        }
        if($Userconnecte->getRoles()[0]=='ROLE_Admin' && $user->getRoles()[0]=='ROLE_AdminPrincipal'){
            throw new HttpException(403,'Impossible de bloquer l\' admin principal !');
        }
        
        if($user->getStatut() == $this->actif){
            $user->setStatut($this->bloqueStr);
            $texte=$this->bloqueStr;
        }
        else{
            $user->setStatut($this->actif);
            $texte= 'Débloqué';
        }
        $entityManager->persist($user);
        $entityManager->flush();
        $afficher = [ $this->status => 200, $this->message => $texte];
        return $this->handleView($this->view($afficher,Response::HTTP_OK));
    }

}