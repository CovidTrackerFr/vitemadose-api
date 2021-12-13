<html>
    <head>
        <title>Vitemadose API</title>
    </head>
    <body>

        <?php

        #Useful functions

        function validateDate($date, $format = 'Y-m-d H:i:s')
        {
            $d = DateTime::createFromFormat($format, $date);
            return $d && $d->format($format) == $date;
        }

        function check_get_parameters($department_list, $platform_list, $vaccine_list, $vaccination_motives){
            $isPlatformFilter=false;
            $isVaccineFilter=false;
            $isMotiveFilter=false;
            $isMinCreneauxFilter=false;

            if (!isset($_GET["department"]) OR !in_array($_GET["department"],$department_list)){
                http_response_code(400);
                echo json_encode("[ERROR] - Incorrect department or missing GET parameter department.");
                die();  
            }

            if (isset($_GET["platform"])){
                $isPlatformFilter=true;
                if (!is_array($_GET["platform"])){
                    http_response_code(400);
                    echo json_encode("[ERROR] - platform GET parameter should be of type Array.");
                    die();  
                }
                if (count(array_intersect(array_map('strtolower',$_GET["platform"]), $platform_list)) != count($_GET["platform"])){
                    http_response_code(400);
                    echo json_encode("[ERROR] - Unknown platform in platform GET parameter Array.");
                    die();  
                }

            }

            if (isset($_GET["vaccine"])){
                $isVaccineFilter=true;
                if (!is_array($_GET["vaccine"])){
                    http_response_code(400);
                    echo json_encode("[ERROR] - vaccine GET parameter should be of type Array.");
                    die();  
                }
                if (count(array_intersect(array_map('strtolower',$_GET["vaccine"]), array_map('strtolower',$vaccine_list))) != count($_GET["vaccine"])){
                    http_response_code(400);
                    echo json_encode("[ERROR] - Unknown vaccine in vaccine GET parameter Array.");
                    die();  
                }
            }

            if (isset($_GET["maxDate"])){
                if (!validateDate($_GET["maxDate"],'Y-m-d')){
                    http_response_code(400);
                    echo json_encode("[ERROR] - Invalid maxDate GET parameter value or format - only allowed format is YYYY-mm-dd.");
                    die();  
                }
            }

            if (isset($_GET["vaccinationMotive"])){
                $isMotiveFilter=true;
                if (!in_array(strtolower($_GET["vaccinationMotive"]),array_map("strtolower",$vaccination_motives))){
                    http_response_code(400);
                    echo json_encode("[ERROR] - Invalid vaccinationMotive GET parameter value.");
                    die();  
                }
            }

            if (isset($_GET["minCreneauxCount"])){
                $isMinCreneauxFilter=true;
                if ($_GET["minCreneauxCount"]<0 OR !is_numeric($_GET["minCreneauxCount"])){
                    http_response_code(400);
                    echo json_encode("[ERROR] - Invalid minCreneauxCount GET parameter value.");
                    die();  
                }
            }

            return array("isPlatformFilter" => $isPlatformFilter, "isVaccineFilter"=>$isVaccineFilter, "isMotiveFilter"=>$isMotiveFilter,"isMinCreneauxFilter"=>$isMinCreneauxFilter);
        }


        function filter_dep_centers($main_json, $selected_department){

            foreach (array_merge($main_json['centres_disponibles'],$main_json['centres_indisponibles']) as $centre){
                if ($centre["departement"]==$selected_department){
                    $filtered_data[]=$centre;
                }
            }

            return isset($filtered_data) ? $filtered_data : [];
        }



        function filter_platform_centers($dep_centers, $selected_platform){

            foreach ($dep_centers as $centre){
                if (in_array(strtolower($centre["plateforme"]),array_map('strtolower',$selected_platform))){
                    $filtered_data[]=$centre;
                }
            }

            return isset($filtered_data) ? $filtered_data : [];
        }

        function filter_vaccine_centers($platform_centers, $selected_vaccines){

            foreach ($platform_centers as $centre){
                if (array_intersect(array_map('strtolower',$centre["vaccine_type"]), array_map('strtolower',$selected_vaccines))){
                    $filtered_data[]=$centre;
                }
            }

            return isset($filtered_data) ? $filtered_data : [];
        }

        function get_creneaux($centers, $creneaux_json, $maxDate){

            foreach ($creneaux_json["creneaux_quotidiens"] as $day){
                if ($maxDate){
                    if (strtotime($day["date"])>strtotime($maxDate)){
                        continue;
                    }
                }
                foreach($day["creneaux_par_lieu"] as $centre){
                    $keep_center=false;
                    foreach($centre["creneaux_par_tag"] as $tag){ 
                        $creneaux_jour[$centre["lieu"]][$day["date"]][$tag["tag"]]=$tag["creneaux"];
                    }
                }
            }
            
            foreach($centers as $centre){
                if(isset($creneaux_jour[$centre["internal_id"]])){
                    $centre["creneaux"]=$creneaux_jour[$centre["internal_id"]];
                    $all=0;
                    $first_or_second_dose=0;
                    $third_dose=0;
                    foreach($centre["creneaux"] as $date){
                        $all += $date["all"];
                        $first_or_second_dose+= $date["first_or_second_dose"];
                        $third_dose += $date["third_dose"];
                    }
                    $centre["availabilities"]=Array("all"=>$all, "first_or_second_dose"=>$first_or_second_dose, "third_dose"=>$third_dose);
                    $filtered_data[]=$centre;
                }
            }

            return isset($filtered_data) ? $filtered_data : [];

        }


        function filter_motive($centers, $vaccination_motive, $minCreneauxCount){

            foreach ($centers as $center){
                if ($center["availabilities"][$vaccination_motive] > $minCreneauxCount){
                    $filtered_data[]=$center;
                }
            }
            return isset($filtered_data) ? $filtered_data : [];

        }

        header('Content-type: application/json');

        $main_json = json_decode(file_get_contents("http://vitemadose.gitlab.io/vitemadose/info_centres.json"),true);

        $platform_list=array("doctolib", "keldoc", "maiia", "mesoigner", "avecmondoc", "bimedoc", "mapharma","ordoclic","valwin");
        $department_list=array("01","02","03","04","05","06","07","08","09","10","11","12","13","14","15","16","17","18","19","20","21","22","23","24","25","26","27","28","29","30","31","32","33","34","35","36","37","38","39","40","41","42","43","44","45","46","47","48","49","50","51","52","53","54","55","56","57","58","59","60","61","62","63","64","65","66","67","68","69","70","71","72","73","74","75","76","77","78","79","80","81","82","83","84","85","86","87","88","89","90","91","92","93","94","95","971","972","973","974","975","976","984","986","987","988","2A","2B");
        $vaccine_list=array("pfizer-biontech","moderna","arnm","astrazeneca","janssen");    
        $vaccination_motives=array("first_or_second_dose","third_dose");    

        #On vérifie que l'ensemble des paramètres GET sont correctement saisis.
        $activeFilters = check_get_parameters($department_list, $platform_list, $vaccine_list,$vaccination_motives);

        #On récupère les créneaux pour le département en question.
        $creneaux_json=json_decode(file_get_contents("https://vitemadose.gitlab.io/vitemadose/{$_GET['department']}/creneaux-quotidiens.json"),true);

        $data=filter_dep_centers($main_json, $_GET["department"]);

        if ($activeFilters["isPlatformFilter"]=="true"){
            $data=filter_platform_centers($data, $_GET["platform"]);
        }

        if ($activeFilters["isVaccineFilter"]=="true"){
            $data=filter_vaccine_centers($data, $_GET["vaccine"]);
        }

        $data=get_creneaux($data, $creneaux_json, isset($_GET["maxDate"])?$_GET["maxDate"]:null);

        if ($activeFilters["isMotiveFilter"]=="true" or $activeFilters["isMinCreneauxFilter"]=="true"){
            $data=filter_motive($data, isset($_GET["vaccinationMotive"])?$_GET["vaccinationMotive"]:"all", isset($_GET["minCreneauxCount"]) ?$_GET["minCreneauxCount"] :0);
        }
        $filters=array("department"=>$_GET["department"],"maxDate"=>isset($_GET["maxDate"]) ?$_GET["maxDate"]:null,"selected_platforms" => isset($_GET["platform"]) ?array_map("strtolower",$_GET["platform"]) :array_map("strtolower",$platform_list), "selected_vaccines"=>isset($_GET["vaccine"]) ?array_map("strtolower",$_GET["vaccine"]) :array_map("strtolower",$vaccine_list), "selected_vaccination_motive" => isset($_GET["vaccinationMotive"]) ?Array($_GET["vaccinationMotive"]) : $vaccination_motives);
        $api["centers_count"]=count($data);
        $api["filters"]=$filters;
        $api["centres"]=$data;

        echo json_encode($api);

        ?>

    </body>
</html>
