<?php

/*
 * copyright 2021 Tomasz Leszczyński tomi0001@gmail.com
 */
namespace App\Http\Services;

use Illuminate\Http\Request;
use App\Models\User as MUser;
use App\Models\Mood as MoodModel;
use App\Models\Moods_action as MoodAction;
use App\Http\Services\Calendar;
use App\Models\Planned_drug;
use App\Models\Usee;
use App\Models\Substance;
use App\Models\Substances_group;
use App\Models\Group;
use App\Models\Substances_product;
use App\Models\Product as appProduct;
use App\Models\Users_description;
use App\Models\Description;
use App\Http\Services\Common;
use Hash;
use Auth;
use DB;
class Product {
    public $date;
    public $error = [];
    public function addNewPlanedArray(Request $request,$name) {
        for ($i=0;$i < count($request->get("idProducts"));$i++) {
            $Planned_drug = new Planned_drug;
            $Planned_drug->addNewPlanedArray( $request,$name,$i);
        }
    }
    public function addNewPlaned(Request $request) {
            $Planned_drug = new Planned_drug;
            $Planned_drug->addNewPlaned($request);
    }
    public function checkErrorNewPlaned(Request $request) {
        if ($request->get("namePlanedNew") == "") {
            array_push($this->error,"Musisz wpisac nazwę zaplanowanej dawki");
        }
        if ($request->get("portion") < 0  or  ( (string)(float) $request->get("portion") !== $request->get("portion") ))  {
             array_push($this->error,"porcja musi być dodatnią liczbą zmienno przcinkową");
         }
      
    }
    public function checkErrorAddSubstance(Request $request) {
         if (  !empty( $ifExist = Substance::ifExist($request->get("nameSubstance"),Auth::User()->id) ))  {
             array_push($this->error,"Już jest taka substancja");
         }
         if ($request->get("equivalent") < 0  or  ( (string)(float) $request->get("equivalent") !== $request->get("equivalent") ) and ($request->get("equivalent") != "") ) {
             array_push($this->error,"Równoważnik musi być dodatnią liczbą zmienno przcinkową");
         }
    }
    public function checkErrorEditSubstance(Request $request) {
         if (  ( $ifExist = Substance::checkIfNameSubstance($request->get("newName"),Auth::User()->id,$request->get("nameSubstance")) > 0 ))  {
             array_push($this->error,"Już jest taka substancja");
         }
         if ($request->get("equivalent") < 0  or  ( (string)(float) $request->get("equivalent") !== $request->get("equivalent") ) and ($request->get("equivalent") != "") ) {
             array_push($this->error,"Równoważnik musi być dodatnią liczbą zmienno przcinkową");
         }
    }
    public function sortWhereSubstance($listGroup,$listSubstance) {
        $arrayNew = [];
        $i = 0;
        $bool = false;
        foreach ($listGroup as $listGro) {
            foreach ($listSubstance as $listSub) {
                if ($listSub->id_groups == $listGro->id) {

                    $arrayNew[$i]["bool"] = true;
                    $arrayNew[$i]["nameGroup"] = $listGro->name;
                    $arrayNew[$i]["id"] = $listGro->id;
                    $bool = true;
                    break;
                }
            }
            if ($bool == false) {
                $arrayNew[$i]["bool"] = false;
                $arrayNew[$i]["nameGroup"] = $listGro->name;
                $arrayNew[$i]["id"] = $listGro->id;
                
            }
            $bool = false;
            $i++;
        }
        array_multisort($arrayNew,SORT_DESC);
        return $arrayNew;
    }
    public function sortWhereProduct($listSubstance,$listProduct) {
        $arrayNew = [];
        $i = 0;
        $bool = false;
        foreach ($listSubstance as $listSub) {
            foreach ($listProduct as $listPro) {
                if ($listPro->id_substances == $listSub->id) {

                    $arrayNew[$i]["bool"] = true;
                    $arrayNew[$i]["nameSub"] = $listSub->name;
                    $arrayNew[$i]["id"] = $listSub->id;
                    $arrayNew[$i]["dose"] = $listPro->doseProduct;
                    $arrayNew[$i]["Mg_Ug"] = $listPro->Mg_Ug;
                    
                    $bool = true;
                    break;
                }
            }
            if ($bool == false) {
                $arrayNew[$i]["bool"] = false;
                $arrayNew[$i]["nameSub"] = $listSub->name;
                $arrayNew[$i]["id"] = $listSub->id;
                $arrayNew[$i]["dose"] = "";
                $arrayNew[$i]["Mg_Ug"] = "";
                
            }
            $bool = false;
            $i++;
        }
        array_multisort($arrayNew,SORT_DESC);
        return $arrayNew;        
    }
    public function checkErrorAddProduct(Request $request) { 
        if (  !empty( $ifExist = appProduct::ifExist($request->get("nameProduct"),Auth::User()->id) ))  {
             array_push($this->error,"Już jest taki produkt");
         }
         if ($request->get("percent") < 0  or  ( (string)(float) $request->get("percent") !== $request->get("percent") ) and ($request->get("percent") != "") ) {
             array_push($this->error,"procent napoju alkoholowego musi być dodatnią liczbą zmienno przecinkową");
         }
         if ($request->get("price") < 0  or  ( (string)(float) $request->get("price") !== $request->get("price") ) and ($request->get("price") != "") ) {
             array_push($this->error,"cena produktu musi być dodatnią liczbą zmienno przecinkową");
         }
         if ($request->get("how") < 0  or  ( (string)(float) $request->get("how") !== $request->get("how") ) and ($request->get("how") != "") ) {
             array_push($this->error,"za ile musi być dodatnią liczbą zmienno przecinkową");
         }
         if ($request->get("how") == "" xor $request->get("price") == "") {
             array_push($this->error,"Pola Cana i za ile muszą być dwa puste albo dwa wypełnione");
         }
         if (( $request->get("type")< 1)  or ( (string)(int) $request->get("type") !== $request->get("type") ) ) {
             array_push($this->error,"uzupełnij typ porcji produktu");
         }
         if (!empty($request->get("howMg"))) {
            $this->searchMg($request->get("howMg"));
         }

    }
    public function checkErrorEditProduct(Request $request) {
        if (  !empty( $ifExist = appProduct::ifExistEdit($request->get("newName"),Auth::User()->id,$request->get("nameProduct")) ))  {
             array_push($this->error,"Już jest taki produkt");
         }
         if ($request->get("percent") < 0  or  ( (string)(float) $request->get("percent") !== $request->get("percent") ) and ($request->get("percent") != "") ) {
             array_push($this->error,"procent napoju alkoholowego musi być dodatnią liczbą zmienno przecinkową");
         }
         if ($request->get("price") < 0  or  ( (string)(float) $request->get("price") !== $request->get("price") ) and ($request->get("price") != "") ) {
             array_push($this->error,"cena produktu musi być dodatnią liczbą zmienno przecinkową");
         }
         if ($request->get("howMuch") < 0  or  ( (string)(float) $request->get("howMuch") !== $request->get("howMuch") ) and ($request->get("howMuch") != "") ) {
             array_push($this->error,"za ile musi być dodatnią liczbą zmienno przecinkową");
         }
         if ($request->get("howMuch") == "" xor $request->get("price") == "") {
             array_push($this->error,"Pola Cana i za ile muszą być dwa puste albo dwa wypełnione");
         }
         if (( $request->get("type")< 1)  or ( (string)(int) $request->get("type") !== $request->get("type") ) ) {
             array_push($this->error,"uzupełnij typ porcji produktu");
         }
         if (!empty($request->get("howMg2"))) {
            $this->searchMg($request->get("howMg2"));
         }        
    }
    private function searchMg(array $howMg) {
        for ($i=0;$i < count ($howMg);$i++) {
            if ($howMg[$i] < 0  or  ( (string)(float) $howMg[$i] !== $howMg[$i] ) and ($howMg[$i] != "") ) {
             array_push($this->error,"zawartośc mg musi być liczbą zmienno przecinkową");
            }
        }
    }
    public function addDrugs(Request $request,$date,$price) {
        $use = new Usee;
        $id = $use->addDrugs( $request,$date,$price);
        if ($request->get("description") != "") {
            $this->addDescription($request,$id,$date);
        }
        
    }
    public function updateProduct(Request $request,$price) {
        $Usee = new Usee;
        $date = $request->get("date") . " " . $request->get("time") . ":00";
        $Usee->updateProduct( $request,$price);
    }
    public function editNameGroup(Request $request) {
        $Group = new Group;
        $Group->editNameGroup($request);
    }
    public function addDescription(Request $request,$id,$date) {
        $Description = new Description;
        $idDescriptions = $Description->addDescription( $request,$date);
        $Users_description = new Users_description;
        $Users_description->addUserDescription( $request,$id,$date,$idDescriptions);
    }
    public function setDate(Request $request)  :bool {
        if ($request->get("date") == "" and $request->get("time") == "") {
            $this->date = date("Y-m-d H:i:s");
            return true;
        }
        else if ($request->get("date") == "" xor $request->get("time") == "") {
            return false;
        }
        else {
            $this->date = $request->get("date") . " " . $request->get("time")  . ":00";
            return true;
        }
    }
    public function sumPrice($dose,$name) {
        $product = new appProduct;
        $select = $product->where("id",$name)->first();
        if (($select->price  == "" and $select->how_much == "") or $select->how_much == 0) {
            return 0;
        }
        else {
            return ($dose / $select->how_much) * $select->price;
        }
    }
    

    public function addPlanedDose(Request $request,$date) {
       
        $list = $this->selectPlaned(Planned_drug::showName($request->get("namePlaned"))->name);
        foreach ($list as $list2) {
            $price = $this->sumPrice($list2->portion,$list2->id_products);
            $this->addDrugsPlaned($list2->id_products,$list2->portion * $request->get("dose"),$date,$price);
        }
    }
    public function selectPlaned(string $namePlaned) {
         $Planned_drug = new Planned_drug;
         $list = $Planned_drug->selectPlaned($namePlaned);
         return $list;
    }
    private function addDrugsPlaned($name,$dose,$date,$price) {
        $use = new Usee;
        $use->addDrugsPlaned($name,$dose,$date,$price);

        
    }
    public function deleteDrugs(int $id) {
        $Drugs = new Usee;
        $Drugs->deleteDrugs( $id);
    }
    public function removeDescriptionDrugs(int $id) {
        $Users_descriptionSelect = new Users_description;
        $idDescription = $Users_descriptionSelect->selectRaw("id_descriptions as id_descriptions")->where("id_usees",$id)->get();
        $Users_description = new Users_description;
        $Users_description->where("id_usees",$id)->delete();
        foreach ($idDescription as $list) {
            $Description = new Description;
            $Description->where("id",$list->id_descriptions)->delete();
        }
    }
    public function showDescriptions(int $id) {
        $Users_description = Users_description::showDescriptions($id);
        return $Users_description;
    }
    public function addNewGroup(Request $request) {
        $Group = new Group;
        $Group->addNewGroup($request);
    }
    public function deletePlaned(string $name) {
        $Planned_drug = new Planned_drug;
        $Planned_drug->deletePlaned( $name);
    }
    public function addNewSubstance(Request $request) {
        $Substance = new Substance;
        $id = $Substance->addNewSubstance($request);
        if (!empty($request->get("idGroup"))  ) {
            $this->addSubstanceGroup($request->get("idGroup"),$id);
        }
    }
    private function addSubstanceGroup( $group, int $idSubstance) {
        for ($i = 0;$i < count($group);$i++)  {
            $Substances_group = new Substances_group;
            $Substances_group->addSubstanceGroup( $group, $idSubstance,$i);
        }
    }
    public function addNewProduct(Request $request) {
        $Product = new appProduct;
        $id = $Product->addNewProduct($request);
        if (!empty($request->get("idSubstance2"))  ) {
            $this->addProductSubstance($request,$id);
        }
    }
    private function addProductSubstance(Request $request,int $idProduct) {
        for ($i = 0;$i < count($request->get("idSubstance2"));$i++)  {
            $Substances_product = new Substances_product;
            $Substances_product->addProductSubstance( $request, $idProduct,$i);
        }
    }
    public function resetSubstance(Request $request) {
        $Substances_group = new Substances_group;
        $Substances_group->where("id_substances",$request->get("nameSubstance"))->delete();
    }
    public function resetProduct(Request $request) {
        $Substances_product = new Substances_product;
        $Substances_product->resetProduct($request);
    }
    public function updateSubstanceGroupname(Request $request) {
        $Substance = new Substance;
        $Substance->updateSubstanceGroupname($request);
    }
    public function updateProductSubstancename(Request $request) {
        $Product = new appProduct;
        $Product->updateProductSubstancename($request);
    }
    public function updateSubstanceGroup(Request $request) {
        for ($i = 0;$i < count($request->get("idGroup"));$i++) {
            $Substances_group = new Substances_group;
            $Substances_group->updateSubstanceGroup($request,$i);
        }
    }
    public function updateProductSubstance(Request $request) {
        for ($i = 0;$i < count($request->get("idSubstance2"));$i++) {
            $Substances_product = new Substances_product;
            $Substances_product->updateProductSubstance($request,$i);
        }
    }
    public function sumAverageProduct(int $idProduct,int $id,$hourFrom,$hourTo,$idUsers) {
        $dateEnd = Usee::selectDateIdUsee($id,$idUsers);
        $hour = $this->setHour($hourFrom,$hourTo);
                
        $listArray = Usee::selectOldUsee($idProduct,$dateEnd->date,$idUsers,Auth::User()->start_day,$hour);
        $type = appProduct::selectTypeProduct($idProduct,$idUsers);
        if ($type->type_of_portion == 4 or $type->type_of_portion == 5) {
            return $this->sortAverageType4_5($listArray);
        }
        else {
            return $this->sortAverage($listArray);
        }
    }
    public function sumAverageSubstances(int $idSubstances,int $id,$hourFrom,$hourTo,$idUsers) {
        $dateEnd = Usee::selectDateIdUsee($id,$idUsers);
        $hour = $this->setHour($hourFrom,$hourTo);
        $listArray = Usee::selectOldUseeSubstances($idSubstances,$dateEnd->date,$idUsers,Auth::User()->start_day,$hour);
        $idProduct = appProduct::selectIdProduct($idSubstances);
        $type = appProduct::selectTypeProduct($idProduct->id,$idUsers);
        if ($type->type_of_portion == 4 or $type->type_of_portion == 5) {
            return $this->sortAverageType4_5($listArray);
        }
        else {
            return $this->sortAverage($listArray);
        }
    }
      private function setHour($hourFrom,$hourTo) {
        $hour  = Auth::User()->start_day;
        if (($hourFrom != "" and $hourTo != "") ) {
           
            $timeFrom = explode(":",$hourFrom);
            $timeTo = explode(":",$hourTo);
            $hourFrom = $this->sumHour($timeFrom,Auth::User()->start_day);
            $hourTo = $this->sumHour($timeTo,Auth::User()->start_day);
        }
        else if ($hourTo != "" ) {
            $timeTo = explode(":",$hourTo);
            $hourTo = $this->sumHour($timeTo,Auth::User()->start_day);
            $timeFrom = explode(":", Auth::User()->start_day . ":00:00");
            $hourFrom = $this->sumHour($timeFrom,Auth::User()->start_day);
        }
        else if ($hourFrom != "") {
            $timeFrom = explode(":",$hourFrom);
            $hourFrom = $this->sumHour($timeFrom,Auth::User()->start_day);
            $hourTmp = date(" H:i:s", strtotime("2020-02-10" . Auth::User()->start_day . ":00:00") - 1);
            $timeTo = explode(":",$hourTmp);
            $hourTo = $this->sumHour($timeTo,Auth::User()->start_day);

        }
        else {
            $timeFrom = explode(":", Auth::User()->start_day . ":00:00");
            $hourFrom = $this->sumHour($timeFrom,Auth::User()->start_day);
            $hourTmp = date(" H:i:s", strtotime("2020-02-10" . Auth::User()->start_day . ":00:00") - 1);
            $timeTo = explode(":",$hourTmp);
            $hourTo = $this->sumHour($timeTo,Auth::User()->start_day);
        }

        return array($hourFrom,$hourTo);
    }
    private function sumHour($hour,$startDay) {
        $sumHour = $hour[0] - $startDay;
        if ($sumHour < 0) {
            $sumHour = 24 + $sumHour;
        }
        if (strlen($sumHour) == 1) {
            $sumHour = "0" .$sumHour;
        }
        if (strlen($hour[1]) == 1) {
            $hour[1] = "0" . $hour[1];
        }

        return $sumHour . ":" .  $hour[1] . ":00";
    }
    private function sortAverageType4_5($arrayList) {
        $newArray = [];
        $j = 0;
        $bool = false;
        for ($i=0;$i < count($arrayList);$i++)  { 
            if ($i == 0) {
                $newArray[$j]["dateStart"] = $arrayList[$i]->dat;
                $newArray[$j]["portions"] = round(($arrayList[$i]->portions / $arrayList[$i]->how),2);
                $newArray[$j]["how"] = $arrayList[$i]->how;
                $newArray[$j]["type"] = $arrayList[$i]->type;
                $newArray[$j]["dateEnd"] = $arrayList[$i]->dat;
            }
        

            else if ((((strtotime($arrayList[$i-1]->dat) - strtotime($arrayList[$i]->dat)  > 146400 )   or ( ($arrayList[$i]->portions) != $arrayList[$i-1]->portions) ) or ( ($arrayList[$i]->how) != $arrayList[$i-1]->how) )      ) {
                   $newArray[$j]["dateStart"] = $arrayList[$i-1]->dat;
                   $j++;
                   $newArray[$j]["dateEnd"] = $arrayList[$i]->dat;

                   $newArray[$j]["portions"] = round(($arrayList[$i]->portions / $arrayList[$i]->how),2);
                   $newArray[$j]["how"] =  $arrayList[$i]->how;
                   $newArray[$j]["type"] = $arrayList[$i]->type;


             }
             if ($i == (count($arrayList) - 1)){
                   $newArray[$j]["dateStart"] = $arrayList[$i]->dat;
             }

            
       }
       return $newArray;
    }
    private function sortAverage( $arrayList) {
        $newArray = [];
        $j = 0;
     
        for ($i=0;$i < count($arrayList);$i++)  { 
            if ($i == 0) {
                $newArray[$j]["dateStart"] = $arrayList[$i]->dat;
                $newArray[$j]["portions"] = $arrayList[$i]->portions;
                $newArray[$j]["how"] = $arrayList[$i]->how;
                $newArray[$j]["type"] = $arrayList[$i]->type;
                $newArray[$j]["dateEnd"] = $arrayList[$i]->dat;
            }
        

            else if ((((strtotime($arrayList[$i-1]->dat) - strtotime($arrayList[$i]->dat)  > 146400 )   or ( ($arrayList[$i]->portions) != $arrayList[$i-1]->portions) ) or ( ($arrayList[$i]->how) != $arrayList[$i-1]->how) )      ) {
                   $newArray[$j]["dateStart"] = $arrayList[$i-1]->dat;
                   $j++;
                   $newArray[$j]["dateEnd"] = $arrayList[$i]->dat;

                   $newArray[$j]["portions"] = $arrayList[$i]->portions;
                   $newArray[$j]["how"] = $arrayList[$i]->how;
                   $newArray[$j]["type"] = $arrayList[$i]->type;


             }
             if ($i == (count($arrayList) - 1)){
                   $newArray[$j]["dateStart"] = $arrayList[$i]->dat;
             }

            
       }
       return $newArray;
  }
  public function checkErrorAverage($hourFrom,$hourTo) {
      
      if ($hourFrom != "" and $hourTo != "") {
          
          
          
         $hour = explode(":",$hourFrom);
         $tmp = $this->sumHour($hour,Auth::User()->start_day);
           $tmp2 = strtotime("2020-02-10" . $tmp);
           $hour2 = explode(":",$hourTo);
         $tmp3 = $this->sumHour($hour2,Auth::User()->start_day);
          $tmp4 =strtotime("2020-02-10" . $tmp3);
          $tmp5 = $tmp4 - $tmp2;
          if ($tmp5 <= 0) {
              array_push($this->error,"Błędna data");
          }

          
      }
  }
  
  public function sumEquivalent(int $id,$hourFrom,$hourTo,$idUsers) {
        $dateEnd = Usee::selectDateIdUsee($id,$idUsers);
        $hour = $this->setHour($hourFrom,$hourTo);
        $listArray = Usee::selectOldUseeEquivalent($dateEnd->date,$idUsers,Auth::User()->start_day,$hour);
        return $this->sortAverage($listArray);
  }
}
