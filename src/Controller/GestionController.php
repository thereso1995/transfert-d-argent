<?php
namespace App\Controller;
use App\Entity\User;
use App\Entity\Compte;
use App\Entity\Depot;
use App\Form\UserType;
use App\Form\CompteType;
use App\Form\DepotType;
use App\Entity\Entreprise;
use App\Form\EntrepriseType;
use App\Repository\UserRepository;
use App\Repository\CompteRepository;
use App\Repository\DepotRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\AbstractFOSsRetController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
/**
 * @Route("/api")
 */
class GestionController extends AbstractFOSRestController
{
    private $actif;
    private $message;
    private $statut;
    private $bloqueStr;
    private $compteStr;
    private $saTransfert;
    private $numeroCompte;
    
    public function __construct()
    {
        $this->actif="Actif";
        $this->message="message";
        $this->statut="statut";
        $this->bloqueStr='Bloqué';
        $this->compteStr='compte';
        $this->saTransfert="SA Transfert";
        $this->numero_compte= "numero_compte";
    }


/**
* @Route("/entreprise", name="enregistre", methods={"POST"})
*/
public function ajouPartenaire (Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder,ValidatorInterface $validator,SerializerInterface $serializer): Response
{

    $user = new User();
    $form = $this->createForm(UserType::class, $user);
    $data = $request->request->all();
    $form->submit($data);

    $user->setPassword($passwordEncoder->encodePassword($user, $data["password"]));
    $user->setTelephone(rand(770000000,779999999));
    $user->setNci(strval(rand(150000000,279999999)));
    $user->setStatut($this->actif);
    $user->setRoles(['ROLE_AdminPrincipal']);

    $entreprise = new Entreprise();
    $form = $this->createForm(EntrepriseType::class, $entreprise);
    $data = $request->request->all();
    $form->submit($data);

    $entreprise->setStatut($this->actif);
    $user->setEntreprise($entreprise);

    $compte= new Compte();
    $form = $this->createForm(CompteType::class, $compte);// liaison de notre formulaire avec l'objet de type depot
    $data=$request->request->all(); //conversion de notre element de la requette
    $form->submit($data);

    $compte->setNumeroCompte(date('y').date('m').' '.date('d').date('H').' '.date('i').date('s'));
    $compte->setEntreprise($entreprise);

    $entityManager = $this->getDoctrine()->getManager();
    

    $entityManager->persist($user);
    $entityManager->persist($entreprise);
    $entityManager->persist($compte);

    $entityManager->flush();
    $afficher = [
        $this->statut => 201,
        $this->message => 'Le partenaire '.$entreprise->getRaisonSociale().' ainsi que son admin principal ont bien été ajouté !! ',
       $this->compteStr =>'Le compte numéro '.$compte->getNumeroCompte().' lui a été assigné'
    ];
    return $this->handleView($this->view($afficher,Response::HTTP_OK));       
}

/**
    * @Route("/nouveau/depot", methods={"POST"})
    */

    public function depot (Request $request, ValidatorInterface $validator, UserInterface $Userconnecte,CompteRepository $repo, EntityManagerInterface $entityManager){

        $depot = new Depot();
        $form = $this->createForm(DepotType::class, $depot);
        $data = $request->request->all();
        if($compte=$repo->findOneBy([ $this->numero_compte=>$data[$this->compteStr]])){
            $data[$this->compteStr]=$compte->getId();//on lui donne directement l'id
            if($compte->getEntreprise()->getRaisonSociale()==$this->saTransfert){
                throw new HttpException(403,'On ne peut pas faire de depot dans le compte de SA Transfert!');
            }
        }
        else{
            throw new HttpException(404,'Ce numero de compte n\'existe pas!');
        }
        $form->submit($data);

        $depot->setDate(new \Datetime());
        $depot->setUser($Userconnecte);
        $compte=$depot->getCompte();
        $compte->setSolde($compte->getSolde()+$depot->getMontant());
        $entityManager->persist($compte);
        $entityManager->persist($depot);
        $entityManager->flush();
        $afficher = [
             $this->statut => 201,
             $this->message => 'Le depot a bien été effectué dans le compte '.$compte->getNumeroCompte()
        ];

        return $this->handleView($this->view($afficher,Response::HTTP_CREATED));

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
        $afficher = [ $this->statut=> 200, $this->message => $texte];
        return $this->handleView($this->view($afficher,Response::HTTP_OK));
    }
}