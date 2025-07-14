<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 30.09.13
 * Time: 6:25
 * To change this template use File | Settings | File Templates.
 */
require_once "config.php";
$page = (intval($_GET['page'])>0)?intval($_GET['page']):0;
$limit = $page*$perPage;
//поиск по адресу по адресам, базе недвижимости и базе организаций

if ($_GET['a']){
    list($a,$b) = explode(',',$_GET['a']);
    $x = round(floatval($a),6);
    $y = round(floatval($b),6);
}


if ($_GET['c']){
    $p = json_decode($_GET['c']);
    //var_dump($p);
    /*********************************** BUILDINGS ******************************************/
    $house_words = explode(",",$p->house);
    $last_house_word = $house_words[count($house_words)-1];
    //$street_words = explode(" ",$p->street);
    $blackList = array('томск','улица',"переулок","проспект","проезд","тупик","площадь","тракт","пос.","п.","с.","ст.");
    $new_street = array();
    $final_street = '';
    /*
    if (in_array($street_words[0],array("улица","переулок","проспект","проезд","тупик","площадь","тракт"))) {
        $i = 0;
        $new_street = array();
        $last_word = '';
        $final_street = '';
        foreach ($street_words as $word) {
            if ($i == 0) {
                $last_word = trim($word);
            } else {
                $new_street[] = trim($word);
            }
            $i++;
        }
        $final_street = implode(" ",$new_street).' '.$last_word;
    } else {
        $final_street = $p->street;
    }
    */
    foreach (strip_string($p->street) as $item){
        if (mb_strlen($item)>2 && !in_array($item,$blackList)){
            //echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
            $new_street[] = mb_strtolower($item);
        }
    }
    //$final_street = implode("%','%",$new_street);

    if (!empty($p->street)) {
        $sqlD = "SELECT s.k_s_id as id,
                s.k_s_name as name,
                (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_street_id =  s.k_s_id) as street_count
                FROM k_streets s
                LEFT JOIN k_towns t ON t.k_t_id = s.k_s_town AND t.k_t_name = '".trim($p->locality)."'
                WHERE s.k_s_name NOT like '%##%' ";
                foreach ($new_street as $street) {
                    $sqlD .= " AND s.k_s_name like '%".trim($street)."%'";
                }

                //s.k_s_name like '%".trim($final_street)."%'";
        //echo $sqlD.'<br>';
        $resD = $mysqli->query($sqlD);
        $resD->data_seek(0);
        $ids = array();
        $street_count = 0;
        while ($rowD = $resD->fetch_assoc()) {
            $ids[] = $rowD['id'];
            $street_count += $rowD['street_count'];
            $street_name = $rowD['name'];
        }
        if (count($ids)>0){
            $street_line = implode(',',$ids);
        }

        if (!empty($p->district)) {
            $p->district = trim(str_replace(array("микрорайон","район"),"",$p->district));
            $sqlD = "SELECT d.k_d_id as id,
                    d.k_d_name as name,
                    (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_district_id = d.k_d_id) as district_count
                    FROM k_districts d
                    LEFT JOIN k_towns t ON t.k_t_id = d.k_d_town AND t.k_t_name = '".trim($p->locality)."'
                    WHERE k_d_name like '%".$p->district."%'";
            //echo $sqlD.'<br>';
            $resD = $mysqli->query($sqlD);
            $resD->data_seek(0);
            $ids = array();
            $district_count = 0;
            while ($rowD = $resD->fetch_assoc()) {
                $ids[] = $rowD['id'];
                $district_count += $rowD['district_count'];
                $district_name = $rowD['name'];
            }
            if (count($ids)>0){
                $district_line = implode(',',$ids);
            }

            $sqlM = "SELECT m.k_tm_id as id,
                    m.k_tm_name as name,
                    (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_massive_id = m.k_tm_id) as massive_count
                    FROM k_towns_massives m
                    LEFT JOIN k_towns t ON t.k_t_id = m.k_tm_town_id AND t.k_t_name = '".trim($p->locality)."'
                    WHERE k_tm_name like '%".$p->district."%'";
            //echo $sqlM.'<br>';
            $resM = $mysqli->query($sqlM);
            $resM->data_seek(0);
            $ids = array();
            $massive_count = 0;
            while ($rowM = $resM->fetch_assoc()) {
                $ids[] = $rowM['id'];
                $massive_count += $row['massive_count'];
                $massive_name = $rowM['name'];
            }
            if (count($ids)>0){
                $massive_line = implode(',',$ids);
            }
        }
        //var_dump($massive_line);
        //echo $street_line.'<br>';
        $sqlD = "SELECT
                        k_shn_id as id
                    FROM k_streets_house_nums h
                    WHERE 1=1";
        if ($street_line) {
            $sqlD .= " AND k_shn_street_id IN (".$street_line.") ";
        }
        if(!empty($last_house_word)) {
            $sqlD .= " AND trim(h.k_shn_house_num) = '".trim($last_house_word)."'";
        }
        if ($district_line){
            $sqlD .= ' AND h.k_shn_district_id IN ('.$district_line.') ';
        }
        if ($massive_line){
            $sqlD .= ' AND h.k_shn_massive_id IN ('.$massive_line.') ';
        }

        //echo $sqlD.'<br>';
        $resD = $mysqli->query($sqlD);
        $resD->data_seek(0);
        $ids = array();
        while ($rowD = $resD->fetch_assoc()) {
            $ids[] = $rowD['id'];
        }
        if (count($ids)>0){
            $id_line = implode(',',$ids);
        } else {
            $id_line = 0;
        }

        $sql = "SELECT
                max(k_s_id) as k_s_id,
                max(k_shn_id) as k_shn_id,
                max(k_d_id) as k_d_id,
                max(k_tm_id) as k_tm_id,
                '".$district_name."' as district,
                '".$massive_name."' as massive,
                '".$district_count."'  as district_count,
                '".$massive_count."' as massive_count,
                '".$street_count."' as street_count,
                0 as address_count,
                (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_street_id = h.k_shn_street_id) as street_count,
                (SELECT count(k_isf_id) FROM k_immovables_sell WHERE k_isf_address IN (".$id_line.") AND k_isf_end_date>now()) as realty_sell,
                (SELECT count(distinct name) FROM base_org WHERE 1=1 AND street_id IN (".$street_line.") ".(!empty($last_house_word)?"AND trim(house_num)=h.k_shn_house_num":'').") as org_count
            FROM k_streets_house_nums h
            LEFT JOIN k_districts d ON d.k_d_id = h.k_shn_district_id
            LEFT JOIN k_towns_massives m ON m.k_tm_id = h.k_shn_massive_id
            LEFT JOIN k_streets s ON s.k_s_id=h.k_shn_street_id
            LEFT JOIN k_towns t ON s.k_s_town = t.k_t_id
            WHERE t.k_t_name = '".trim($p->locality)."' ";
        if ($street_line) {
            $sql .= " AND k_shn_street_id IN (".$street_line.")";
        }
        if (!empty($last_house_word)) {
            $sql .= " AND trim(h.k_shn_house_num) = '".trim($last_house_word)."' ";
        }
        if (!$street_line && $district_line){
            $sql .= ' AND h.k_shn_district_id IN ('.$district_line.') ';
        }
        if (!$street_line && $massive_line){
            //var_dump($massive_line);
            $sql .= ' AND h.k_shn_massive_id IN ('.$massive_line.') ';
        }
        $sql .= " GROUP BY t.k_t_id ";
    } else {
        if (!empty($p->district)) {
            //получаем id района или микрорайона и по ним делаем выборку
            $p->district = trim(str_replace(array("микрорайон","район"),"",$p->district));
            $sqlD = "SELECT d.k_d_id as id,
                    d.k_d_name as name,
                    (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_district_id = d.k_d_id) as district_count
                    FROM k_districts d
                    LEFT JOIN k_towns t ON t.k_t_id = d.k_d_town AND t.k_t_name = '".trim($p->locality)."'
                    WHERE k_d_name like '%".$p->district."%'";
            //echo $sqlD.'<br>';
            $resD = $mysqli->query($sqlD);
            $resD->data_seek(0);
            $ids = array();
            $district_count = 0;
            while ($rowD = $resD->fetch_assoc()) {
                $ids[] = $rowD['id'];
                $district_count += $rowD['district_count'];
                $district_name = $rowD['name'];
            }
            if (count($ids)>0){
                $district_line = implode(',',$ids);
            } else {
                $district_line = 0;
            }

            $sqlM = "SELECT m.k_tm_id as id,
                    m.k_tm_name as name,
                    (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_massive_id = m.k_tm_id) as massive_count
                    FROM k_towns_massives m
                    LEFT JOIN k_towns t ON t.k_t_id = m.k_tm_town_id AND t.k_t_name = '".trim($p->locality)."'
                    WHERE k_tm_name like '%".$p->district."%'";
            //echo $sqlM.'<br>';
            $resM = $mysqli->query($sqlM);
            $resM->data_seek(0);
            $ids = array();
            $massive_count = 0;
            while ($rowM = $resM->fetch_assoc()) {
                $ids[] = $rowM['id'];
                $massive_count += $row['massive_count'];
                $massive_name = $rowM['name'];
            }
            if (count($ids)>0){
                $massive_line = implode(',',$ids);
            }
            //var_dump($massive_line);

            $sql = "SELECT
                    max(k_s_id) as k_s_id,
                    max(k_shn_id) as k_shn_id,
                    max(k_d_id) as k_d_id,
                    max(k_tm_id) as k_tm_id,
                    '".$district_name."' as district,
                    '".$massive_name."' as massive,
                    '".$district_count."'  as district_count,
                    '".$massive_count."' as massive_count,
                    count(k_shn_id) as address_count,
                    '".$street_count."' as street_count,
                    (SELECT count(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = h.k_shn_id AND k_isf_end_date>now()) as realty_sell,
                    (SELECT count(distinct name) FROM base_org WHERE street_id = h.k_shn_street_id) as org_count
                FROM k_streets_house_nums h
                LEFT JOIN k_districts d ON d.k_d_id = h.k_shn_district_id
                LEFT JOIN k_towns_massives m ON m.k_tm_id = h.k_shn_massive_id
                LEFT JOIN k_streets s ON s.k_s_id=h.k_shn_street_id
                LEFT JOIN k_towns t ON s.k_s_town = t.k_t_id
                WHERE t.k_t_name = '".trim($p->locality)."'";
            if ($district_line){
                $sql .= ' AND h.k_shn_district_id IN ('.$district_line.') ';
            }
            if ($massive_line){
                $sql .= ' AND h.k_shn_massive_id IN ('.$massive_line.') ';
            }
            $sql .= " GROUP BY t.k_t_id ";
        }
    }



    //echo $sql.'<br>';
    //die();
    $street = "";
    $houses = "";
    $district = "";
    $streets = array();
    $houses = array();
    $districts = array();
    $district = "";
    $foto_line = '';
    $massives = array();
    $massive = "";
    $res = $mysqli->query($sql);
    $res->data_seek(0);
    $ids[] = array();;
    while ($row = $res->fetch_assoc()) {
        $sqlFoto = 'SELECT k_shp_url
                    FROM k_street_house_photos
                    WHERE k_shp_parent = '.$row['k_shn_id'];
        $resFoto = $mysqli->query($sqlFoto);
        $resFoto->data_seek(0);
        while ($rowFoto = $resFoto->fetch_assoc()) {
            $foto_line .= '<a data-lightbox="ip70" target=_blank href="http://'._SERVER_ADDRESS.'/admin/images/addresses/'.$rowFoto['k_shp_url'].'"><img src="http://'._SERVER_ADDRESS.'/admin/images/addresses/1_'.$rowFoto['k_shp_url'].'" width=100></a>';
        }

        if (!in_array($row['k_s_id'],$streets)){
            $streets[] = $row['k_s_id']; //street
        }
        if (!in_array($row['k_shn_id'],$houses)){
            $houses[] = $row['k_shn_id'];//houses
        }
        if (!in_array($row['k_d_id'],$districts)){
            $districts[] = $row['k_d_id'];//k_shn_id
        }
        if (!in_array($row['k_tm_id'],$massives)){
            $massives[] = $row['k_tm_id'];//massive
        }
        $district = $row['district'];
        $massive = $row['massive'];
        $realty = $row['realty_sell'];
        $district_count = $row['district_count'];
        $massive_count = $row['massive_count'];
        $street_count = $row['street_count'];
        $orgs = $row['org_count'];
        $ids[] = $row['k_s_id'];
        $address_count = $row['address_count'];

    }

//var_dump($streets);

    $link = "";
    $p = 0;
    if ($massive_count==1){
        $link .= "&m=".$massives[0];
    } else {
        $p=1;
    }
    if ($district_count==1){
        $link .= "&d=".$districts[0];
    } else {
        $p=1;
    }
    if ($street_count==1){
        $link .= "&s=".$streets[0];
    } else {
        $p=1;
    }
    if ($address_count==1){
        $link .= "&h=".$houses[0];
    } else {
        $p=1;
    }
    if ($p==1){
        $link .= "&f=".urlencode(implode(" ",$new_street).', '.$last_house_word);
    }
    if (count($foto)>0){
        $foto_line = implode(',',$ids);
    }

}


$result['link'] = $link;
$result['center'] = $_GET['a'];
$result['X'] = $x;
$result['Y'] = $y;
$result['district'] = $district;
$result['massive'] = $massive;
$result['district_count'] = $district_count;
$result['massive_count'] = $massive_count;
$result['street_count'] = $street_count;
$result['count'] = (int)$realty;
$result['count_f'] = (int)$orgs;
$result['count_address'] = $address_count?$address_count:(count($ids)-2);
$result['house'] = $last_house_word;
$result['foto'] = $foto_line;
$result1 = json_encode($result);
echo $result1;
