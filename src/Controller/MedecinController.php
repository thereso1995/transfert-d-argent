<?php

namespace App\Controller;


use App\Entity\Medecin;
use App\Form\MedecinType;
use App\Repository\SpecialiteRepository;
use App\Entity\Specialite;
use App\Repository\MedecinRepository;
use App\Utils\MatriculeGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



class MedecinController extends AbstractController
{
    /**
     * @Route("/medecin", name="medecinshow")
     */
    public function showMedecin(MedecinRepository $medRepos)
    {
        $medecins = $medRepos -> findAll();
        return $this->render('medecin/index.html.twig', [
            'medecins' => $medecins
        ]);
    }
     /**
     * @Route("/medecin/new", name="medecin_new")
     */
    public function new(Request $request,MatriculeGenerator $mat_generator)
    {
        
        $medecin = new Medecin();
        $form =$this->createForm(MedecinType::class, $medecin);
        $form ->handleRequest($request);
        
        if($form ->isSubmitted() && $form->isValid()){
            $medecin->setMatricule($mat_generator->generate($medecin));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist ($medecin);
            
            $entityManager->flush();
            return $this->redirectToRoute('medecinshow');
        }
                        
        return $this->render('medecin/new.html.twig', [
            'form' => $form->createView(),
            
        ]);
    }

     /**
     * @Route("/medecin/new", name="medecin_new")
     * @Route("/medecin/edit/{id}", name="medecin_edit")
     */
    public function addUpdateMedecin(Medecin $medecin = null, Request $request, 
                     MedecinRepository $medRepos, MatriculeGenerator $mat_generator)
    {
      
      if (!$medecin) {
        $medecin = new Medecin();
      }
    $form = $this->createForm(MedecinType::class, $medecin);
    $form->handleRequest($request);
     if($form->isSubmitted() && $form->isValid()){
      //injection des donnees du formulaire dans la variable medecin
      $medecin = $form->getData();
      $this->addFlash('success', 'Medecin ajoute avec succes.');
      $medecin->setMatricule($mat_generator->generate($medecin));
      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->persist ($medecin);
         
         
         $entityManager->persist($medecin);
         
         $entityManager->flush(); 
         return $this->redirectToRoute('medecinshow');
        }
       
        return $this->render('medecin/new.html.twig', [
            'form' => $form->createView(),
            'editMedecin' => $medecin->getId() !== null,
            'medecin' => $medecin,
            'medecins' => 'medecin',
            'mainNavRegistration' => true,
            
      
        ]);
    }
    /**
     * @Route("/medecin/delete/{id}", name="medecin_delete")
     */
    public function deleteMedecin(Request $request, MedecinRepository $repo, $id)
    {
      $medecin= $repo->find($id);
      $form = $this->createForm(MedecinType::class, $medecin);
      $form->handleRequest($request);
      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->remove($medecin);
      $entityManager->flush();
      return $this->redirectToRoute('medecin_new');
    }
    /**
     * @Route("/service/specialites/", name="services.specialite")
     * @return JsonResponse
     */
  
    public function findSpecialiteofService(SpecialiteRepository $repo, Request $request)
    {
      
        $specialites = $repo->createQueryBuilder('s')
            ->andWhere('s.service = :serviceid')
            ->setParameter('serviceid', $request->query->get('id'))
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
       $tabspecialite=[];
       
       foreach ($specialites as  $specialite) {
             $tabspecialite[]= array(
               'id' => $specialite->getId(),
               'libelle' => $specialite->getLibelle()
             );
            
       }
     return new JsonResponse( $tabspecialite);
      
    }
}