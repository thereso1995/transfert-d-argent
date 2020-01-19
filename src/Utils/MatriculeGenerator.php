<?php

namespace App\Utils;

use App\Entity\Medecin;
use App\Repository\MedecinRepository;



class MatriculeGenerator{
   
    private $matricule;
   
    public function __construct(MedecinRepository $medecinRepo)
    {
        $lastMedecin = $medecinRepo->findOneBy([],['id'=>'desc']);
        if($lastMedecin != null){
            $lastId = $lastMedecin->getId();
            $this->matricule = sprintf("%'.05d\n", $lastId);
        }else{
            $this->matricule = sprintf("%'.05d\n",1);
        }
    }
    public function generate(Medecin $medecin){
        $index = "M";
        $service = $medecin->getService()->getLibelle();
       $number_of_word = (str_word_count($service, 1));
       if(count($number_of_word) >=2){
        foreach ($number_of_word as $k => $v) {
            $index.=strtoupper(substr($v,0,1));
           }  
           
       }else{
         $index.=strtoupper(substr($number_of_word[0],0,2));
       }
        return $index."-".$this->matricule;
    }
}
?>

