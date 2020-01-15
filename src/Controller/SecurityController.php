<?php
namespace App\Controller;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\ProfilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\AbstractFOSsRetController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
/**
 * @Route("/api")
 */
class SecurityController extends AbstractFOSRestController
{
    private $actif;
    private $message;
    private $statut;
    
    
    public function __construct()
    {
        $this->actif="Actif";
        $this->message="message";
        $this->statut="statut";
    }
     /**
     * @Route("/inscription", name="inscription", methods={"POST"})
     */
     public function inscriptionUser(Request $request,EntityManagerInterface $entityManager,UserPasswordEncoderInterface $encoder, UserInterface $Userconnecte, ProfilRepository $repoProfil,  ValidatorInterface $validator){
         #####################----------Début traitement formulaire et envoie des données----------#####################
            
         $user=new User();
         $form = $this->createForm(UserType::class,$user);
        $data = $request->request->all();
        $form->submit($data);
          #####################-----------Fin traitement formulaire et envoie des données-----------#####################
        
        #####################----------------Début controle de saissie des profils----------------#####################
            
        $idProfil = $user->getProfil();# recuperer via le formulaire
        if(!$profil=$repoProfil->find($idProfil)){
            throw new HttpException(404,'Ce profil n\'existe pas !');
        }
        #####################-----------------Fin controle de saissie des profils-----------------#####################
        #####################----------------Début gestion des roles pouvant ajouter -------------#####################
        $roleUserConnecte[]=$Userconnecte->getRoles()[0];# on le met dans un tableau pour le comparer aux roles (qui sont des tableaux), le [1] est le role user par defaut
        $libelle=$profil->getLibelle();
        $roles=['ROLE_'.$libelle];
        $this->validationRole($roles,$roleUserConnecte);
        $user->setRoles($roles);
     
       #####################-------------------------Fin gestion des roles ---------------------#####################
        #####################------------------Début finalisation de l'inscription----------------#####################
               
        $user->setStatut($this->actif);
        
        $hash=$encoder->encodePassword($user, $user->getPassword());
        $user->setPassword($hash);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->handleView($this->view([$this->statut=>'Enregistrer'],Response::HTTP_CREATED));
    }
    /**
     * @Route("/userConnecte", name="userConnecte", methods={"GET"})
     */
    public function userConnecte(SerializerInterface $serializer,UserInterface $userConnecte){
        $data = $serializer->serialize($userConnecte,'json',[ 'groups' => ['list-user']]);
        return new Response($data,200);
    }
    public function validationRole($roles,$roleUserConnecte){
        $roleSupAdmi=['ROLE_SuperAdmin'];
        $roleCaissier=['ROLE_Caissier'];
        $roleAdmiPrinc=['ROLE_AdminPrincipal'];
        $roleAdmi=['ROLE_Admin'];
        $user=['ROLE_User'];
        if($roles==$roleAdmiPrinc){
            throw new HttpException(403,'Impossible de créer ce type d\'utilisateur sans créer un nouveau partenaire');
        }
        elseif($roles == $roleSupAdmi  && $roleUserConnecte != $roleSupAdmi   ||
               $roles == $roleCaissier && $roleUserConnecte != $roleSupAdmi   ||
               $roles == $roleAdmi     && $roleUserConnecte != $roleAdmiPrinc ||
               $roles == $user  && $roleUserConnecte != $roleAdmiPrinc && $roleUserConnecte != $roleAdmi
        ){//Vérifier que son profil lui permet de l'ajouter
             throw new HttpException(403,'Votre profil ne vous permet pas de créer ce type d\'utilisateur');
        }
    }
    /**
     * @Route("/login_check", name="login", methods={"POST"})
     */
    public function login(Request $request)
    {
        $user = $this->getUser();
        return $this->json([
            'username' => $user->getUsername(),
            'roles' => $user->getRoles()
        ]);
    }
    /**
     * @Route("/list/user", name="list_user", methods={"GET"})
     */
    public function lister(UserRepository $UserRepository, SerializerInterface $serializer)
    {
        $user = $UserRepository->findAll();
        $data = $serializer->serialize($user, 'json');
        return new Response($data, 200, [
            'content-Type' => 'application/json'
        ]);
    }
}