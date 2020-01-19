<?php

namespace App\Form;
use App\Entity\Medecin;
use App\Entity\Service;
use App\Entity\Specialite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\OptionsResolver\OptionsResolver; 

class MedecinType extends AbstractType
{
    
    
   
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            
            ->add('nom')
            ->add('prenom')
            -> add ('datenaiss' , BirthdayType::class , [
                'widget' => 'single_text',
            ])
            ->add('tel')
            ->add('email')
            ->add('service',EntityType::class,[
                'class'=>Service::class,
                'choice_label'=>"libelle",
            ])
            ->add('specialites',EntityType::class,[
                'class'=>Specialite::class,
                'choice_label'=>"libelle",
                'multiple'=>true,
                'by_reference'=>false,
            ])
        ;}
    
        public function configureOptions (OptionsResolver $resolver)
        {
            $resolver->setDefaults([
                'data_class' => Medecin::class,
            ]);
        }
    
   
  
    
   
    }