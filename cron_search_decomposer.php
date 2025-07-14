<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 14.11.13
 * Time: 2:33
 * To change this template use File | Settings | File Templates.
 */
require_once "config.php";

$sql = 'TRUNCATE TABLE search_index';
$res = $mysqli->query($sql);


$sql = "SELECT
            distinct
            b.id,
            b.street,
            b.house_num,
            b.name,
            b.description,
            b.phone1,
            b.phone2,
            b.phone3,
            b.phone4,
            b.street_id,
            h.centerX as h_X,
            h.centerY as h_Y
        FROM base_org b
        LEFT JOIN k_streets_house_nums h ON h.k_shn_street_id = b.street_id AND trim(b.house_num) = trim(h.k_shn_house_num)
        ";
$res = $mysqli->query($sql);
$res->data_seek(0);


while ($row = $res->fetch_assoc()) {
    $res_str = array();
    $q = trim($row['name']);
    $blackList = array('томск');
    if ($q) {
        foreach (strip_string($q) as $item){
            if (mb_strlen($item)>2 && !in_array($item,$blackList)){
                echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
                $res_str[] = mb_strtolower($item);
                //$sqlInsert = "INSERT INTO search_index (word,base_org_id,street_id) VALUES ('".trim(mb_strtolower($item))."',".$row['id'].",".$row['street_id'].")";
                //$resIns = $mysqli->query($sqlInsert);
            }
        }
    }
    $q = trim($row['description']);
    $blackList = array('томск');
    if ($q) {
        foreach (strip_string($q) as $item){
            if (mb_strlen($item)>2 && !in_array($item,$blackList)){
                echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
                $res_str[] = mb_strtolower($item);
                //$sqlInsert = "INSERT INTO search_index (word,base_org_id,street_id) VALUES ('".trim(mb_strtolower($item))."',".$row['id'].",".$row['street_id'].")";
                //$resIns = $mysqli->query($sqlInsert);
            }
        }
    }
    $q = trim($row['street']);
    //$blackList = array('улица',"переулок","проспект","проезд","тупик","площадь","тракт");
    $blackList = array('томск','улица',"переулок","проспект","проезд","тупик","площадь","тракт","пос.","п.","с.","ст.","поселок","село","станция","деревня");
    if ($q) {
        foreach (strip_string($q) as $item){
            if (mb_strlen($item)>2 && !in_array($item,$blackList)){
                echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
                $res_str[] = mb_strtolower($item);
                //$sqlInsert = "INSERT INTO search_index (word,base_org_id,street_id) VALUES ('".trim(mb_strtolower($item))."',".$row['id'].",".$row['street_id'].")";
                //$resIns = $mysqli->query($sqlInsert);
            }
        }
    }
    $item = trim($row['house_num']);
    $blackList = array();
    if ($item) {
        if (mb_strlen($item)>0 && !in_array($item,$blackList)){
            echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
            $res_str[] = mb_strtolower($item);
            //$sqlInsert = "INSERT INTO search_index (word,base_org_id,street_id) VALUES ('".trim(mb_strtolower($item))."',".$row['id'].",".$row['street_id'].")";
            //$resIns = $mysqli->query($sqlInsert);
        }
    }
    $res_str = array_unique($res_str);
    foreach ($res_str as $word) {
        $sqlInsert = "INSERT INTO search_index (word,string,base_org_id,street_id) VALUES ('".$word."','".implode(" ",$res_str)."',".$row['id'].",".$row['street_id'].")";
        $resIns = $mysqli->query($sqlInsert);
    }
    $sqlUp = "UPDATE base_org SET centerX = '".$row['h_X']."', centerY = '".$row['h_Y']."' WHERE id = ".$row['id'];
    $resUp = $mysqli->query($sqlUp);
}
$res_str = array();
$sql = "SELECT
            d.k_d_name as district,
            m.k_tm_name as massive,
            s.k_s_name as street,
            h.k_shn_house_num as house,
            h.k_shn_street_id
        FROM k_streets_house_nums h
        LEFT JOIN k_streets s ON h.k_shn_street_id=s.k_s_id
        LEFT JOIN k_districts d ON h.k_shn_district_id=d.k_d_id
        LEFT JOIN k_towns_massives m ON h.k_shn_massive_id=m.k_tm_id
        WHERE s.k_s_name NOT like '%##%'
        ";
$res = $mysqli->query($sql);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
    $res_str = array();
    $q = trim($row['district']);
    $blackList = array('район','поселок',"микрорайон","город","станция","село","деревня");
    if ($q) {
        foreach (strip_string($q) as $item){
            if (mb_strlen($item)>2 && !in_array($item,$blackList)){
                echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
                $res_str[] = mb_strtolower($item);
            }
        }
    }
    $q = trim($row['massive']);
    if ($q) {
        foreach (strip_string($q) as $item){
            if (mb_strlen($item)>2 && !in_array($item,$blackList)){
                echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
                $res_str[] = mb_strtolower($item);
            }
        }
    }
    $q = trim($row['street']);
    $blackList = array('томск','улица',"переулок","проспект","проезд","тупик","площадь","тракт","пос.","п.","с.","ст.");
    if ($q) {
        foreach (strip_string($q) as $item){
            if (mb_strlen($item)>2 && !in_array($item,$blackList)){
                echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
                $res_str[] = mb_strtolower($item);
            }
        }
    }
    $item = trim($row['house']);
    $blackList = array();
    if ($item) {
        echo mb_strtolower($item).' '.mb_strlen($item).'<br>';
        if (mb_strlen($item)>0 && !in_array($item,$blackList)){
            $res_str[] = mb_strtolower($item);
        }
    }
    $res_str = array_unique($res_str);
    foreach ($res_str as $word) {
        $sqlInsert = "INSERT INTO search_index (word,string,street_id) VALUES ('".$word."','".implode(" ",$res_str)."',".$row['k_shn_street_id'].")";
        $resIns = $mysqli->query($sqlInsert);
    }

}
//var_dump($words);

