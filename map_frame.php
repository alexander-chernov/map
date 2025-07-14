<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 19.11.13
 * Time: 2:18
 * To change this template use File | Settings | File Templates.
 */

session_start();

require_once "config.php";

$page = (intval($_GET['page'])>0)?intval($_GET['page']):0;
$limit = 0;//$page*$perPage;

$street = intval($_GET['s']);
$district = intval($_GET['d']);
$house = intval($_GET['h']);
$massive = intval($_GET['m']);
$object = intval($_GET['o']);
$show_street = ($_GET['street']=='on'?1:0);
$f = $_GET['f']; //search line
$a = $_GET['a']; //coordinates
$c = $_GET['c'];
$bs = $_GET['bs']; // BUS STOP
$rt = $_GET['rt']; // BUS ROUTES
$w = $_GET['w'];
$noadv = $_GET['noadv'];
$globalX = array();
$globalY = array();
if (!$w) {
    $w = 800;
}
$street_color = array(
    0 => '#e23321'
/*,
    1 => '#00FFFF',
    2 => '#800080',
    3 => '#FF00FF',
    4 => '#0000FF',
    5 => '#00FFFF',
    6 => '#008000',
    7 => '#00FF00',
    8 => '#808000',
    9 => '#FFFF00',
    10 => '#800000',
    11 => '#FF0000',
    12 => '#000000',
    13 => '#808080',
    14 => '#C0C0C0',
    15 => '#306',
    16 => '#309',
    17 => '#30C',
    18 => '#30F',
    19 => '#330',
    20 => '#333',
    21 => '#336',
    22 => '#339',
    23 => '#33C',
    24 => '#33F',
    25 => '#360',
*/
);


if ($show_street){ //показать улицу
    if ($c){
        $p = json_decode($c);
        //var_dump($p);
        //$blackList = array('томск','улица',"переулок","проспект","проезд","тупик","площадь","тракт","пос.","п.","с.","ст.");
        $blackList = array('томск',"пос.","п.","с.","ст.");
        $new_street = array();
        $final_street = '';
        foreach (strip_string($p->street) as $item){
            if (mb_strlen($item)>2 && !in_array($item,$blackList)){
                $new_street[] = mb_strtolower($item);
            }
        }
        if (!empty($p->street)) {
            $sqlD = "SELECT
                        asText(geometry) as geo_obj
                        ,s.k_s_name as street_name
                    FROM k_streets s
                    LEFT JOIN k_towns t ON t.k_t_id = s.k_s_town AND t.k_t_name = '".trim($p->locality)."'
                    LEFT JOIN map_streets m ON m.k_s_id = s.k_s_id
                    WHERE s.k_s_name NOT like '%##%'
                    AND asText(geometry) is not null ";
            foreach ($new_street as $street) {
                $sqlD .= " AND s.k_s_name like '%".trim($street)."%'";
            }
            //

                    //s.k_s_name like '%".trim($final_street)."%'";
            //echo $sqlD.'<br>';
            $resD = $mysqli->query($sqlD);
            $resD->data_seek(0);
            $ids = array();
            $street_count = 0;
            $street_line = array();
            $index=0;
            $index0=0;
            while ($rowD = $resD->fetch_assoc()) {
                $line = $rowD['street_name'];
                $line = str_replace("А","Автобус №",$line);
                $line = str_replace("Т","Троллейбус №",$line);
                $line = str_replace("М","Марштурка №",$line);
                $street_name[$index] = $line;
                //var_dump(preg_match("/MULTILINESTRING/i", $rowD['geo_obj']));
                if (preg_match("/MULTILINESTRING/i", $rowD['geo_obj'])) {
                    $line = str_replace("MULTILINESTRING","",$rowD['geo_obj']);
                    $line = str_replace("((","",$line);
                    $line = str_replace("))","",$line);
                    //var_dump($line);
                    $lines = explode("),(",$line);
                    //var_dump($lines);
                    foreach ($lines as $item) {

                        $points = explode(",",$item);
                        foreach ($points as $point) {
                            $coord = explode(" ",$point);
                            $ix = $coord[0];
                            $igrek = $coord[1];
                            $centerX = round(y2lat($igrek),6);
                            $centerY = round(x2lon($ix),6);
                            $street_polyline[$index][$index0][] = '['.$centerX.','.$centerY.']';
                            $globalX[] = $centerX;
                            $globalY[] = $centerY;
                        }
                        $index0++;
                    }
                } else {
                    $line = str_replace("LINESTRING","",$line);
                    $line = str_replace("(","",$line);
                    $line = str_replace(")","",$line);
                    $points = explode(",",$line);
                    foreach ($points as $point) {
                        $coord = explode(" ",$point);
                        $ix = $coord[0];
                        $igrek = $coord[1];
                        $centerX = round(y2lat($igrek),6);
                        $centerY = round(x2lon($ix),6);
                        $street_polyline[0][$index][] = '['.$centerX.','.$centerY.']';
                        $globalX[] = $centerX;
                        $globalY[] = $centerY;
                    }
                }
                $index++;
            }
        }
        //var_dump($street_polyline);
    }
    if ($rt) {

        $index = 0;
        $sqlD = "SELECT asText(s.geometry) as geo_obj
                    ,s.name
                    FROM map_routes s
                WHERE name = '".$rt."'
                GROUP BY s.name";
        //echo $sqlD.'<br>';
        $resD = $mysqli->query($sqlD);
        $resD->data_seek(0);
        $ids = array();
        $street_count = 0;
        $street_line = array();
        $index=0;
        $index0=0;
        while ($rowD = $resD->fetch_assoc()) {
            $line = $rowD['name'];
            $line = str_replace("А","Автобус №",$line);
            $line = str_replace("Т","Троллейбус №",$line);
            $line = str_replace("М","Марштурка №",$line);
            $street_name[$index] = $line;
            if (preg_match("/MULTILINESTRING/i", $rowD['geo_obj'])) {
                $line = str_replace("MULTILINESTRING","",$rowD['geo_obj']);
                $line = str_replace("((","",$line);
                $line = str_replace("))","",$line);
                //var_dump($line);
                $lines = explode("),(",$line);
                foreach ($lines as $item) {
                    $points = explode(",",$item);
                    foreach ($points as $point) {
                        $coord = explode(" ",$point);
                        $ix = $coord[0];
                        $igrek = $coord[1];
                        $centerX = round(y2lat($igrek),6);
                        $centerY = round(x2lon($ix),6);
                        $street_polyline[$index][$index0][] = '['.$centerX.','.$centerY.']';
                        $globalX[] = $centerX;
                        $globalY[] = $centerY;
                    }
                    $index0++;
                }
            } else {
                $line = str_replace("LINESTRING","",$line);
                $line = str_replace("(","",$line);
                $line = str_replace(")","",$line);
                $points = explode(",",$line);
                foreach ($points as $point) {
                    $coord = explode(" ",$point);
                    $ix = $coord[0];
                    $igrek = $coord[1];
                    $centerX = round(y2lat($igrek),6);
                    $centerY = round(x2lon($ix),6);
                    $street_polyline[0][$index][] = '['.$centerX.','.$centerY.']';
                    $globalX[] = $centerX;
                    $globalY[] = $centerY;
                }
            }
            $index++;
        }

        $index = 0;
        $sql = "SELECT DISTINCT fid,
                        routes,
                        s.name,
                        asText(geometry) as geo_obj
                    FROM map_station s
                WHERE 1=1
                    AND s.routes like '%".$rt."%'
                GROUP BY s.name    ";
        //echo $sql.'<br>';
        $res = $mysqli->query($sql);
        $res->data_seek(0);

        while ($row = $res->fetch_assoc()) {
            $line = str_replace("POINT(","",$row['geo_obj']);
            $line = str_replace(")","",$line);
            $coord = explode(" ",$line);
            $ix = $coord[0];
            $igrek = $coord[1];
            $centerX = round(y2lat($igrek),6);
            $centerY = round(x2lon($ix),6);
            $row['centerX'] = $centerX;
            $row['centerY'] = $centerY;

            $globalX[] = $row['centerX'];
            $globalY[] = $row['centerY'];
            $routes = explode(',',$row['routes']);
            $new_route_line = "";
            $i = 0;
            foreach ($routes as $line){
                $oline = trim($line);
                $line = trim($line);
                $line = str_replace("А","Автобус №",$line);
                $line = str_replace("Т","Троллейбус №",$line);
                $line = str_replace("М","Марштурка №",$line);
                if ($i!=0) {
                    $new_route_line .= ',&nbsp';
                }
                $new_route_line .= '<a onclick=showRoute("'.$oline.'") href=#>'.$line.'</a>';
                //$new_route_line .= $line.'<br>';
                $i++;
            }
            $p3 = '['.$row['centerX'].', '.$row['centerY'].']';
            $placeMarkStop[$index] = "
                var textBody = '<p class=address>'+
                            'Остановка: <b>".$row['name']."</b>'+
                            '</p>'+
                            '<p class=result>'+
                            'Маршруты:<br>".$new_route_line." '+
                            '</p>';
                var textCont =
                busStops[".$index."] = new ymaps.Placemark(".$p3.",{
                        balloonContentHeader: '<p class=address>".$row['name']."</p>',
                        balloonContent: '',
                        balloonContentBody: textBody
                    },{
                        preset: 'twirl#redIcon'
                    });
                ";
            $index++;
        }
    }
    //var_dump($street_polyline);
    //var_dump($street_name);
    //var_dump($placeMarkStop);


} else {
    if (($street || $district || $house || $massive) && !$f) {
        //по адресам
        $sql = "SELECT
                        s.k_s_name as street,
                        n.k_shn_street_id as street_id,
                        n.k_shn_id as address_id,
                        n.k_shn_house_num as house,
                        t.k_t_name as town,
                        m.k_tm_name as massive,
                        d.k_d_name as district,
                        n.centerX,
                        n.centerY,
                        count(n.k_shn_house_num) as count_address,
                        (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_id = n.k_shn_id) as addr_count,
                        (SELECT count(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = n.k_shn_id AND k_isf_end_date>now()) as realty_sell,
                        (SELECT count(distinct name) FROM base_org WHERE street_id = n.k_shn_street_id AND trim(house_num)=n.k_shn_house_num) as org_count
                FROM k_streets_house_nums n
                LEFT JOIN k_streets s ON s.k_s_id = n.k_shn_street_id
                LEFT JOIN k_towns t ON t.k_t_id = s.k_s_town
                LEFT JOIN k_towns_massives m ON m.k_tm_id=n.k_shn_massive_id
                LEFT JOIN k_districts d ON d.k_d_id=n.k_shn_district_id
                WHERE 1=1
                ";
        if (!empty($street)) {
            $sql .= " AND n.k_shn_street_id=".$street;
        }
        if (!empty($district)) {
            $sql .= " AND n.k_shn_district_id=".$district;
        }
        if (!empty($house)) {
            $sql .= " AND n.k_shn_id=".$house."";
        }
        if (!empty($massive)) {
            $sql .= " AND n.k_shn_massive_id=".$massive;
        }
        if (!empty($object)) {
            $sql .= " AND n.k_shn_object_id=".$object;
        }

        $sql .= "
                GROUP by n.k_shn_house_num
                limit ".$limit.",".$perPage;

        //echo $sql.'<br>';
        //die();

        $res = $mysqli->query($sql);
        $res->data_seek(0);
        $index = 0;
        $streetName = "";
        $houseName = "";
        $placeMarkAddress = array();

        while ($row = $res->fetch_assoc()) {
            $p1 = '';
            $ixes = array();
            $igreks = array();
            $ixes[] = $row['centerX'];
            $globalX[] = $row['centerX'];
            $igreks[] = $row['centerY'];
            $globalY[] = $row['centerY'];
            $p1 .=  '['.$row['centerX'].','.$row['centerY'].'],'."\n";
            $min_x = min($ixes);
            $max_x = max($ixes);
            $min_y = min($igreks);
            $max_y = max($igreks);
            $p2 = '['.round(($min_x+($max_x-$min_x)/2),6).', '.round(($min_y+($max_y-$min_y)/2),6).']';
            $aLink = round(($min_x+($max_x-$min_x)/2),6).','.round(($min_y+($max_y-$min_y)/2),6);

            $sLink = $row['street_id'];
            $hLink = $row['address_id'];
            $link = "\'a=".$aLink.'&s='.$sLink.'&h='.$hLink."\'";

            $placeMarkAddress[$index] = "
                addressItem[".$index."] = new ymaps.Placemark(".$p2.",{
                    balloonContentHeader: '<p class=address>".$row['town'].", ".$row['street'].", ".$row['house']."</p>',
                    balloonContent: '',
                    balloonContentBody: '<p class=result>'+
                                        'Адрес: <b>".$row['town'].", ".$row['street'].", ".$row['house']."</b>'+
                                        '</p>'+
                                        ".(($row['district'])?("'<p class=result>Район: <b>".$row['district']."</b></p>'+"):"")."
                                        ".(($row['massive'])?("'<p class=result>Микрорайон: <b>".$row['massive']."</b></p>'+"):"")."
                                        'Адресов:<a href=\'#\' onclick=\"showRightAddressByLink(".$link.",0)\">".$row['addr_count']."</a><br>'+
                                        'Предложений:<a href=\'#\' onclick=\"showRightRealtyByLink(".$link.",0)\">".$row['realty_sell']."</a><br>'+
                                        'Организаций:<a href=\'#\' onclick=\"showRightOrgsByLink(".$link.",0)\">".$row['org_count']."</a>'+
                                        '</p>'
                },{
                    preset: 'twirl#greenIcon'
                });
            ";
            $index++;
            $streetName = $row['street'];
            $houseName = $row['house'];
        }
        //по организациям
        if ($massive || $district || $street) {
            $sql = "SELECT
                    distinct k_shn_street_id
                    FROM  k_streets_house_nums
                    WHERE 1=1".
                ($massive?" AND k_shn_massive_id = ".$massive:"").
                ($district?" AND k_shn_district_id = ".$district:"").
                ($street?" AND k_shn_street_id = ".$street:"")
            ;
            //echo $sql.'<br>';
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            $streets = array();
            while ($row = $res->fetch_assoc()) {
                $streets[] = $row['k_shn_street_id'];
            }
            if (count($streets)>0) {
                $street_line = implode(",",$streets);
            }

        }
        if ($house) {
            $sql = "SELECT
                    distinct k_shn_house_num
                    FROM  k_streets_house_nums
                    WHERE 1=1".
                ($house?" AND k_shn_id = ".$house."":"");
            //echo $sql.'<br>';
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            $houses = array();
            while ($row = $res->fetch_assoc()) {
                $houses[] = "'".$row['k_shn_house_num']."'";
            }
            if (count($houses)>0) {
                $house_line = implode(",",$houses);
            }
        }
        if ($street_line || $house_line) {
            $sql = "SELECT
                        distinct o.name,
                        o.address,
                        o.site,
                        o.email,
                        o.phone1,
                        o.phone2,
                        o.phone3,
                        o.phone4,
                        s.k_s_name as street,
                        n.k_shn_id as address_id,
                        n.k_shn_house_num as house,
                        t.k_t_name as town,
                        m.k_tm_name as massive,
                        d.k_d_name as district,
                        n.centerX,
                        n.centerY,
                        count(n.k_shn_house_num) as count_address,
                        (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_id = n.k_shn_id) as addr_count,
                        (SELECT count(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = n.k_shn_id AND k_isf_end_date>now()) as realty_sell,
                        (SELECT count(distinct name) FROM base_org WHERE street_id = n.k_shn_street_id AND trim(house_num)=n.k_shn_house_num) as org_count
                    FROM base_org o
                    LEFT JOIN k_streets_house_nums n ON n.k_shn_street_id = o.street_id AND o.house_num=n.k_shn_house_num
                    LEFT JOIN k_streets s ON s.k_s_id = n.k_shn_street_id
                    LEFT JOIN k_towns t ON t.k_t_id = s.k_s_town
                    LEFT JOIN k_towns_massives m ON m.k_tm_id=n.k_shn_massive_id
                    LEFT JOIN k_districts d ON d.k_d_id=n.k_shn_district_id
                    WHERE street_id IN (".($street_line).")".
                    ($house_line?" AND trim(house_num) IN (".$house_line.")":"").
                    "
                    GROUP by n.k_shn_house_num
                    limit ".$limit.",".$perPage;
            //echo $sql.'<br>';
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            //$index = 0;
            $streetName = "";

            $houseName = "";
            $placeMarkOrg = array();
            while ($row = $res->fetch_assoc()) {
                $sLink = $row['street_id'];
                $hLink = $row['address_id'];
                $link = "\'a=".$aLink.'&s='.$sLink.'&h='.$hLink."\'";

                $placeMarkOrg[$index] = "
                    firmItem[".$index."] = new ymaps.Placemark(".$p2.",{
                        balloonContentHeader: '<p class=address>".$row['town'].", ".$row['street'].", ".$row['house']."</p>',
                        balloonContent: '',
                        balloonContentBody: '<p class=result>'+
                                            'Адрес: <b>".$row['town'].", ".$row['street'].", ".$row['house']."</b>'+
                                            '</p>'+
                                            ".(($row['district'])?("'<p class=result>Район: <b>".$row['district']."</b></p>'+"):"")."
                                            ".(($row['massive'])?("'<p class=result>Микрорайон: <b>".$row['massive']."</b></p>'+"):"")."
                                            'Адресов:<a href=\'#\' onclick=\"showRightAddressByLink(".$link.",0)\">".$row['addr_count']."</a><br>'+
                                            'Предложений:<a href=\'#\' onclick=\"showRightRealtyByLink(".$link.",0)\">".$row['realty_sell']."</a><br>'+
                                            'Организаций:<a href=\'#\' onclick=\"showRightOrgsByLink(".$link.",0)\">".$row['org_count']."</a>'+
                                            '</p>'
                    },{
                    preset: 'twirl#violetIcon'
                    });
                ";
                $index++;
                $streetName = $row['street'];
                $houseName = $row['house'];
            }
        } else {
            echo '<p class=small_orgs>Ошибка! Неверный адрес.</p>';
        }

    } elseif ($f) {
        $items = strip_string($f);
        //var_dump($items);
        //echo count($items).'<br>';
        if (count($items)==1) {
            $district_name = "";
            $sql = "SELECT k_d_name,centerX,centerY
                    FROM k_districts d
                    WHERE  ";
            $sql .= " k_d_name like '%".$items[0]."%' ";
            //echo $sql.'<br>';
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            while ($row = $res->fetch_assoc()) {
                $district_name = str_replace(" ","+",$row['k_d_name']);
                $centerX = $row['centerX'];
                $centerY = $row['centerY'];
                break;
            }
            //var_dump($district_name);
            if ($district_name) {
                /*
                $geoName = 'город+'.$default_locality.',+'. $district_name;
                $geoKode = YANDEX_GEO_LINK . $geoName;
                //echo $geoKode.'<br>';
                if ($curl = curl_init()) {
                    curl_setopt($curl, CURLOPT_URL, $geoKode);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    $out = curl_exec($curl);
                    curl_close($curl);
                }
                $ya_coord = json_decode($out, TRUE);
                foreach ($ya_coord['response']['GeoObjectCollection']['featureMember'] as $place) {
                    list($centerY,$centerX) = explode(" ",$place['GeoObject']['Point']['pos']);
                    break;
                    //echo $place['GeoObject']['description']." ".$place['GeoObject']['name']."<br>\n";
                    //var_dump($place['GeoObject']['Point']['pos']);
                }
                */
                $mapCenter = '['.$centerX.','.$centerY.']';
                $index = 0;
                $a = $centerX.','.$centerY;
            } else {
                $sql = "SELECT DISTINCT s.base_org_id, s.street_id  ".
                        " FROM search_index s ".
                        " WHERE  ";
                $sql .= " s.word like '".$items[0]."%' ";
                $res = $mysqli->query($sql);
                $res->data_seek(0);
                while ($row = $res->fetch_assoc()) {
                    //$res_arr[] = $row['word'];
                    if (!empty($row['base_org_id'])) {
                        $orgs[] = $row['base_org_id'];
                    }
                    if (!empty($row['street_id'])) {
                        $streets[] = $row['street_id'];
                    }
                }
            }
        } else {
            $j==0;
            $request_str = '';
            $revers = count($items)-1;
            $sqlA = '';
            for ($i=$revers;$i>=0;$i--){
                //echo $i.'. '.$items[$i].'<br>';
                if ($j==0){
                    $sqlA .= " AND s.word like '".$items[$i]."%' ";
                } else {
                    $sqlA .= " AND s.string like '%".$items[$i]."%' ";
                }
                $j++;
            }
            for ($i=0;$i<$revers;$i++){
                $res_arr[] = $items[$i];
            }
            $sql = "SELECT DISTINCT s.base_org_id, s.street_id  ".
                    " FROM search_index s ".
                    " WHERE 1=1 ";
            $sql .= $sqlA;
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['base_org_id'])) {
                    $orgs[] = $row['base_org_id'];
                }
                if (!empty($row['street_id'])) {
                    $streets[] = $row['street_id'];
                }
            }
        }

        if (count($items)>1 && count($res_arr)==0){
            $revers = count($items)-2;
            $sqlA = '';
            for ($i=$revers;$i>=0;$i--){
                //echo $i.'. '.$items[$i].'<br>';
                if ($j==0){
                    $sqlA .= " AND s.word like '".$items[$i]."%' ";
                } else {
                    $sqlA .= " AND s.string like '%".$items[$i]."%' ";
                }
                $j++;
            }
            for ($i=0;$i<$revers;$i++){
                $res_arr[] = $items[$i].' ';
            }
            $sql = "SELECT DISTINCT s.base_org_id, s.street_id ".
                    " FROM search_index s ".
                    " WHERE 1=1 ";
            $sql .= $sqlA;
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['base_org_id'])) {
                    $orgs[] = $row['base_org_id'];
                }
                if (!empty($row['street_id'])) {
                    $streets[] = $row['street_id'];
                }
            }
        }
        if (is_array($orgs)){
            $orgs = array_unique($orgs);
            if (count($orgs)>0) {
                $org_line = implode(",",$orgs);
            }
        }
        if (is_array($streets)) {
            $streets = array_unique($streets);
            if (count($streets)>0) {
                $street_line = implode(",",$streets);
            }
        }
        if (!$district_name) {
            if (count($orgs)>0 && is_array($orgs)){
                $sql = "SELECT DISTINCT
                        b.town, b.street, b.house_num as house, b.centerX, b.centerY, b.street_id,
                        (SELECT count(DISTINCT name) FROM base_org WHERE street_id = b.street_id AND trim(house_num)=trim(b.house_num)) as org_count
                        FROM base_org b
                        WHERE
                        ifnull(b.centerX,0)>0
                        AND ifnull(b.centerY,0)>0
                        AND id IN (".($org_line).")".
                        ($street_line?" AND street_id IN (".$street_line.")":"").
                        //
                        //(SELECT count(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = n.k_shn_id AND k_isf_end_date>now()) as realty_sell,
                        //LEFT JOIN k_streets_house_nums n ON n.k_shn_street_id = b.street_id AND n.k_shn_house_num=trim(b.house_num)

                        "   ".
                        " limit ".$limit.",".$perPageMap
                ;
                //echo $sql.'<br>';
                //die();
                $res = $mysqli->query($sql);
                $res->data_seek(0);
                $index=0;
                $placeMarkStreet = array();
                while ($row = $res->fetch_assoc()) {
                    $globalX[] = $row['centerX'];
                    $globalY[] = $row['centerY'];
                    $p3 = '['.$row['centerX'].', '.$row['centerY'].']';
                    $sqlTmp = "SELECT
                                    (SELECT count(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = n.k_shn_id AND k_isf_end_date>now()) as realty_sell,
                                    n.k_shn_id as address_id,
                                    (SELECT count(k_shn_id) FROM k_streets_house_nums WHERE k_shn_id = n.k_shn_id) as addr_count,
                                    m.k_tm_name as massive,
                                    d.k_d_name as district
                                FROM k_streets_house_nums n
                                LEFT JOIN k_towns_massives m ON m.k_tm_id=n.k_shn_massive_id
                                LEFT JOIN k_districts d ON d.k_d_id=n.k_shn_district_id
                                WHERE n.k_shn_street_id = ".$row['street_id']."
                                AND trim(n.k_shn_house_num)='".trim($row['house'])."'
                                limit ".$limit.",".$perPage;
                    $resTmp = $mysqli->query($sqlTmp);
                    $resTmp->data_seek(0);
                    $rowTmp = $resTmp->fetch_row();
                    $realty_sell = $rowTmp[0];
                    if (!$realty_sell){
                        $realty_sell = 0;
                    }
                    $aLink = $row['centerX'].','.$row['centerY'];
                    $hLink = $rowTmp[1];
                    $addrCount = $rowTmp[2];
                    $addrMassive = $rowTmp[3];
                    $addrDistrict = $rowTmp[4];
                    $sLink = $row['street_id'];
                    if ($hLink){
                        $link = "\'a=".$aLink.'&s='.$sLink.'&h='.$hLink."\'";
                    } else {
                        $fLink = str_replace(' ','+',($row['street'].' '.str_replace(" ","_",trim($row['house']))));
                        $link = "\'a=".$aLink.'&f='.$fLink."\'";
                    }
                    $sqlFoto = 'SELECT f.k_shp_url
                                FROM k_streets_house_nums n
                                LEFT JOIN k_street_house_photos f ON n.k_shn_id = f.k_shp_parent
                                WHERE n.k_shn_street_id = '.$row['street_id'].'
                                AND trim(n.k_shn_house_num)=\''.trim($row['house']).'\'';
                    //echo $sqlFoto.'<br>';
                    $resFoto = $mysqli->query($sqlFoto);
                    $resFoto->data_seek(0);
                    $foto_line = "";
                    while ($rowFoto = $resFoto->fetch_assoc()) {
                        if ($rowFoto['k_shp_url']) {
                            $foto_line .= '<a target=_blank  href="http://'._SERVER_ADDRESS.'/admin/images/addresses/'.$rowFoto['k_shp_url'].'"><img src="http://'._SERVER_ADDRESS.'/admin/images/addresses/1_'.$rowFoto['k_shp_url'].'" width=100></a>';
                        }
                    }
                    $placeMarkStreet[$index] = "
                    var textBody = '<p class=address>'+
                                'Адрес: <b>".$row['town'].", ".$row['street'].", ".$row['house']."</b>'+
                                '</p>'+
                                ".(($addrDistrict)?("'<p class=result>Район: <b>".$addrDistrict."</b></p>'+"):"")."
                                ".(($addrMassive)?("'<p class=result>Микрорайон: <b>".$addrMassive."</b></p>'+"):"")."
                                '<p class=result>'+
                                'Адресов:<a href=\'#\' onclick=\"showRightAddressByLink(".$link.",0)\">".$addrCount."</a><br>'+
                                'Предложений:<a href=\'#\' onclick=\"showRightRealtyByLink(".$link.",0)\">".$realty_sell."</a><br>'+
                                'Организаций:<a href=\'#\' onclick=\"showRightOrgsByLink(".$link.",0)\">".$row['org_count']."</a>'+
                                '".(($foto_line)?'<br>'.$foto_line:'')."'+
                                '</p>';
                    var textCont =
                    streetItem[".$index."] = new ymaps.Placemark(".$p3.",{
                            balloonContentHeader: '<p class=address>".$row['town'].", ".$row['street'].", ".$row['house']."</p>',
                            balloonContent: '',
                            balloonContentBody: textBody
                        },{
                            preset: 'twirl#blueIcon'
                        });
                    ";
                    $index++;
                }
            } else {
                $q = trim($f);
                $items = strip_string($q);
                $newItems = array();
                $blackList = array('улица',"переулок","проспект","проезд","тупик","площадь","тракт");
                foreach ($items as $item) {
                    $item = mb_strtolower($item);
                    if (!in_array($item,$blackList)){
                        if (strpos($item,"_")) {
                            $item = str_replace("_"," ",$item);
                        }
                        $newItems[] = $item;
                    }
                }
                $items = $newItems;
                if (count($items)>0){
                    $sql = "SELECT DISTINCT
                                d.k_d_name as district,
                                m.k_tm_name as massive,
                                s.k_s_name as street,
                                h.k_shn_street_id as street_id,
                                h.k_shn_house_num as house,
                                h.centerX,
                                h.centerY,
                                (SELECT count(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = h.k_shn_id AND k_isf_end_date>now()) as realty_sell,
                                (SELECT count(DISTINCT name) FROM base_org WHERE street_id = h.k_shn_street_id AND trim(house_num)=trim(h.k_shn_house_num)) as org_count,
                                t.k_t_name as town
                            FROM k_streets_house_nums h
                            LEFT JOIN k_streets s ON h.k_shn_street_id=s.k_s_id
                            LEFT JOIN k_districts d ON h.k_shn_district_id=d.k_d_id
                            LEFT JOIN k_towns_massives m ON h.k_shn_massive_id=m.k_tm_id
                            LEFT JOIN k_towns t ON t.k_t_id = m.k_tm_town_id AND t.k_t_id = d.k_d_town AND t.k_t_id = s.k_s_town

                        WHERE s.k_s_name NOT like '%##%'
                        AND ifnull(h.centerX,0)>0
                        AND ifnull(h.centerY,0)>0
                         ";
                    for($i=0;$i<count($items);$i++) {
                        $sql .= " AND concat(ifnull(d.k_d_name,' '),' ',ifnull(m.k_tm_name,' '),' ',s.k_s_name,' ',trim(h.k_shn_house_num)) like '%".trim($items[$i])."%'";
                    }
                    $sql .=" order by s.k_s_name, h.k_shn_house_num asc
                    limit ".$limit.",".$perPage;
                    $res = $mysqli->query($sql);
                    $res->data_seek(0);
                    $index=0;
                    $placeMarkSearch = array();
                    while ($row = $res->fetch_assoc()) {
                        $globalX[] = $row['centerX'];
                        $globalY[] = $row['centerY'];
                        $p3 = '['.$row['centerX'].', '.$row['centerY'].']';
                        $aLink = $row['centerX'].','.$row['centerY'];
                        $hLink = $row['realty_sell'];
                        $addrMassive = $row['massive'];
                        $addrDistrict = $row['district'];
                        $sLink = $row['street_id'];
                        if ($hLink){
                            $link = "\'a=".$aLink.'&s='.$sLink.'&h='.$hLink."\'";
                        } else {
                            $fLink = str_replace(' ','+',($row['street'].' '.str_replace(" ","_",trim($row['house']))));
                            $link = "\'a=".$aLink.'&f='.$fLink."\'";
                        }
                        $sqlFoto = 'SELECT f.k_shp_url
                                    FROM k_streets_house_nums n
                                    LEFT JOIN k_street_house_photos f ON n.k_shn_id = f.k_shp_parent
                                    WHERE n.k_shn_street_id = '.$row['street_id'].'
                                    AND trim(n.k_shn_house_num)=\''.trim($row['house']).'\'';
                        $resFoto = $mysqli->query($sqlFoto);
                        $resFoto->data_seek(0);
                        $foto_line = '';
                        while ($rowFoto = $resFoto->fetch_assoc()) {
                            if ($rowFoto['k_shp_url']) {
                                $foto_line .= '<a target=_blank  href="http://'._SERVER_ADDRESS.'/admin/images/addresses/'.$rowFoto['k_shp_url'].'"><img src="http://'._SERVER_ADDRESS.'/admin/images/addresses/1_'.$rowFoto['k_shp_url'].'" width=100></a>';
                            }

                        }

                        $placeMarkSearch[$index] = "
                        var textBody = '<p class=address>'+
                                    'Адрес: <b>".$row['town'].", ".$row['street'].", ".$row['house']."</b>'+
                                    '</p>'+
                                    ".(($addrDistrict)?("'<p class=result>Район: <b>".$addrDistrict."</b></p>'+"):"")."
                                    ".(($addrMassive)?("'<p class=result>Микрорайон: <b>".$addrMassive."</b></p>'+"):"")."
                                    '<p class=result>'+
                                    'Предложений:<a href=\'#\' onclick=\"showRightRealtyByLink(".$link.",0)\">".$row['realty_sell']."</a><br>'+
                                    'Организаций:<a href=\'#\' onclick=\"showRightOrgsByLink(".$link.",0)\">".$row['org_count']."</a>'+
                                    '".(($foto_line)?'<br>'.$foto_line:'')."'+
                                    '</p>';
                        var textCont =
                        searchItem[".$index."] = new ymaps.Placemark(".$p3.",{
                                balloonContentHeader: '<p class=address>".$row['town'].", ".$row['street'].", ".$row['house']."</p>',
                                balloonContent: '',
                                balloonContentBody: textBody
                            },{
                                preset: 'twirl#brownIcon'
                            });
                        ";
                        $index++;
                    }

                }

            }
                    //TODO: добавить вывод остановок на карту
                    $index = 0;
                    $sql = "SELECT DISTINCT fid,
                                routes,
                                s.name,
                                centerX,
                                centerY
                            FROM map_station s
                        WHERE ifnull(centerX,0)>0
                        AND ifnull(centerY,0)>0 ";
                        for($i=0;$i<count($items);$i++) {
                            $sql .= " AND s.name like '%".trim($items[$i])."%'";
                        }
                            $sql .=" GROUP BY s.name order by s.name asc
                            limit ".$limit.",".$perPage
                    ;
                    $res = $mysqli->query($sql);
                    //echo $sql.'<br>';
                    $res->data_seek(0);
                    $placeMarkStop = array();
                    while ($row = $res->fetch_assoc()) {
                        $globalX[] = $row['centerX'];
                        $globalY[] = $row['centerY'];
                        $routes = explode(',',$row['routes']);
                        $new_route_line = "";
                $i=0;
                        foreach ($routes as $line){
                    $oline = trim($line);
                            $line = trim($line);
                            $line = str_replace("А","Автобус №",$line);
                            $line = str_replace("Т","Троллейбус №",$line);
                            $line = str_replace("М","Марштурка №",$line);
                    if ($i!=0) {
                        $new_route_line .= ',&nbsp';
                    }
                    $new_route_line .= '<a onclick=showRoute("'.$oline.'") href=#>'.$line.'</a>';
                    $i++;
                    //$new_route_line .= $line.'<br>';
                }
                        $p3 = '['.$row['centerX'].', '.$row['centerY'].']';
                        $placeMarkStop[$index] = "
                        var textBody = '<p class=address>'+
                                    'Остановка: <b>".$row['name']."</b>'+
                                    '</p>'+
                                    '<p class=result>'+
                                    'Маршруты:<br>".$new_route_line." '+
                                    '</p>';
                        var textCont =
                        busStops[".$index."] = new ymaps.Placemark(".$p3.",{
                                balloonContentHeader: '<p class=address>".$row['name']."</p>',
                                balloonContent: '',
                                balloonContentBody: textBody
                            },{
                                preset: 'twirl#redIcon'
                            });
                        ";
                        $index++;
                    }

        }
    }
    // вывод остановок отдельно по ID
    if ($bs) {
        $index = 0;
        $sql = "SELECT DISTINCT fid,
                        routes,
                        s.name,
                        asText(geometry) as geo_obj
                    FROM map_station s
                WHERE fid = ".$bs;
        $res = $mysqli->query($sql);
        $placeMarkStop = array();
        while ($row = $res->fetch_assoc()) {
            $line = str_replace("POINT(","",$row['geo_obj']);
            $line = str_replace(")","",$line);
            $coord = explode(" ",$line);
            $ix = $coord[0];
            $igrek = $coord[1];
            $centerX = round(y2lat($igrek),6);
            $centerY = round(x2lon($ix),6);
            $row['centerX'] = $centerX;
            $row['centerY'] = $centerY;

            $globalX[] = $row['centerX'];
            $globalY[] = $row['centerY'];
            $routes = explode(',',$row['routes']);
            $new_route_line = "";
            $i = 0;
            foreach ($routes as $line){
                $oline = trim($line);
                $line = trim($line);
                $line = str_replace("А","Автобус №",$line);
                $line = str_replace("Т","Троллейбус №",$line);
                $line = str_replace("М","Марштурка №",$line);
                if ($i!=0) {
                    $new_route_line .= ',&nbsp';
                }
                $new_route_line .= '<a onclick=showRoute("'.$oline.'") href=#>'.$line.'</a>';
                $i++;
                //$new_route_line .= $line.'<br>';
            }
            $p3 = '['.$row['centerX'].', '.$row['centerY'].']';
            $placeMarkStop[$index] = "
                var textBody = '<p class=address>'+
                            'Остановка: <b>".$row['name']."</b>'+
                            '</p>'+
                            '<p class=result>'+
                            'Маршруты:<br>".$new_route_line." '+
                            '</p>';
                var textCont =
                busStops[".$index."] = new ymaps.Placemark(".$p3.",{
                        balloonContentHeader: '<p class=address>".$row['name']."</p>',
                        balloonContent: '',
                        balloonContentBody: textBody
                    },{
                        preset: 'twirl#blackIcon'
                    });
                ";
            $index++;
        }
    }

}
if (count($globalX)>0 && count($globalY)>0) {
    $min_x = min($globalX);
    $max_x = max($globalX);
    $min_y = min($globalY);
    $max_y = max($globalY);
    $mapCenter = '['.round(($min_x+($max_x-$min_x)/2),6).','.round(($min_y+($max_y-$min_y)/2),6).']';
}
//реклама
if ($noadv<>1 && $index!=0){
    $sqlB = 'SELECT url,img,centerX,centerY
                FROM banner';
    $resB = $mysqli->query($sqlB);
    $resB->data_seek(0);
    $advLine = '';
    $i=0;
    while ($rowB = $resB->fetch_assoc()) {
        //$advLine .= "\nmyMap.balloon.open([".$rowB['centerX'].", ".$rowB['centerY']."], '<a target=_blank href=\'".$rowB['url']."\'><img src=\'/banner/".$rowB['img']."\'></a>', {});\n";

        $advLine .= "
        var balloon".$i." = new ymaps.Balloon(myMap);
        balloon".$i.".options.setParent(myMap.options);
        balloon".$i.".open([".$rowB['centerX'].", ".$rowB['centerY']."]);
        balloon".$i.".setData({content: '<a target=_blank href=\'".$rowB['url']."\'><img src=\'/images/banner/".$rowB['img']."\'></a>'});
        ";
        $i++;
    }
}

?>
<!DOCTYPE HTML>
<html id="html">
<head>
<title>IP70</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link href="/css/jquery.autocomplete.css" rel="stylesheet"/>
<link href="/css/styles.css" rel="stylesheet"/>
<link rel="stylesheet" href="/css/lightbox.css" media="screen"/>
<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Karla:400,700">

<script src="http://api-maps.yandex.ru/2.0/?load=package.full,package.geoObjects,package.editor&lang=ru-RU" type="text/javascript"></script>

<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.10.2.js"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script src="/js/modernizr.custom.js"></script>
<script src="/js/lightbox-2.6.min.js"></script>



<script src="/js/frame_script.js" type="text/javascript"></script>


<script type="text/javascript">
ymaps.ready(init);
function init() {
    var myMap = new ymaps.Map("map", {
        <?php
        if ($a) {
            echo "center:[".$a."], // Tomsk - a\n";
        } elseif ($mapCenter) {
            echo "center:".$mapCenter.", // Tomsk - mapCenter\n";
        } else {
            echo "center:[56.496581,84.963502], // Tomsk\n";
        }
        ?>
        propagateEvents : true,
        <?php
        if ($index==0||$a||$c||$noadv==1) {
            echo "zoom: 15,";
        } else {
            echo "zoom: 12,";
        }
        ?>
        //zoom: 12,
        behaviors: ['default'<?=($_GET['frame']!=1?",'scrollZoom'":'')?>]
    });
    myMap.setType('yandex#map');//yandex#hybrid
    myMap.controls
        .add('zoomControl', { left: 5, top: 5 })
        .add('typeSelector')
        .add('mapTools', { left: 35, top: 5 })
        .add('scaleLine', { right: 10, bottom: 55 })
        .add(new ymaps.control.MiniMap({type: 'yandex#map'}, {zoomOffset: 3}))
        .add(new ymaps.control.TrafficControl({providerKey: 'traffic#actual'}))
    ;
    var objCount = "";
    myGeoObject = [];
    addressItem = [];
    busStops = [];
    firmItem = [];
    searchItem = [];
    streetItem = [];

<?php
    if ($advLine){
        echo $advLine;
    }

?>

    myMap.events.add('click', function (e) {
        //$('#error').append(myMap.balloon.calculatePixelPosition());
        myMap.balloon.close();
        var coords = e.get('coordPosition');
        ymaps.geocode(coords).then(function (res) {
            var names = [];
            var res_arr = {};
            var arr = {};
            var rrr = {};
            var kind, name;
            res.geoObjects.each(function (obj) {
                arr = obj.properties.get('metaDataProperty');
                kind = arr['GeocoderMetaData'].kind;
                name = obj.properties.get('name');
                res_arr[kind] = name;
                names.push(obj.properties.get('name'));
            });
            $.ajax({
                url: 'ajax_request.php?t=2&c=' + encodeURIComponent(JSON.stringify(res_arr))+'&a='+coords[0].toPrecision(6)+','+coords[1].toPrecision(6),
                timeout:3000,
                dataType: "json",                     // тип загружаемых данных
                success: function (data) { // вешаем свой обработчик на функцию success
                    var textBody = '<p class=address>Адрес: <b>' + names[2] + '</b>';
                    if (!data.house) {
                        textBody += '<br><b>' + names[0] + '</b> <a onclick="showStreetByLink(\''+encodeURIComponent(JSON.stringify(res_arr))+'&w=<?=$w?>\',0)" href="#">Посмотреть улицу на карте</a>';
                    } else {
                        textBody += '<br><b>' + names[0] + ' </b>';
                    }

                    if (data.district) {
                        textBody += '<br>Район: <b>' + data.district + ' <a href="#" onclick=\"showRightAddressByLink(\''+data.link+'&w=<?=$w?>\',0)\">' + data.district_count + '</a></b>';
                    }
                    if (data.massive) {
                        textBody += '<br>Микрорайон: <b>' + data.massive + ' <a href="#" onclick=\"showRightAddressByLink(\''+data.link+'&w=<?=$w?>\',0)\">' + data.massive_count + '</a></b>';
                    }
                    textBody += '</p>';
		            var link = 'a=' + coords[0].toPrecision(6) + ',' + coords[1].toPrecision(6) + data.link;
                    textBody += '<p class=result>';
                    if (!data.house) {
                        textBody += 'Адресов: <b><a href="#" onclick=\"showRightAddressByLink(\''+link+'&w=<?=$w?>\',0)\">' + data.street_count + '</a></b><br>';
                    }
                    textBody += 'Предложений: <b><a href="#" onclick=\"showRightRealtyByLink(\''+link+'&w=<?=$w?>\',0)\">' + data.count + '</a></b><br>'+
                                'Организаций: <b><a href="#" onclick=\"showRightOrgsByLink(\''+link+'&w=<?=$w?>\',0)\">' + data.count_f + '</b></b>';
                    if (data.count > 0 || data.count_f > 0) {
                        textBody += '<br><a id="closeBalloon" href="#">Посмотреть</a>';
                    }
                    if (data.foto) {
                        textBody += '<br>'+data.foto;
                    }
                    textBody = textBody + '</p>';
                    myMap.balloon.open(coords, {
                        contentHeader: ''
                        ,contentBody: textBody
                        //,contentFooter: [coords[0].toPrecision(8), coords[1].toPrecision(8)].join(', ')
                    });
                    $("#closeBalloon").click(function(){
                        showRightMapByLink("" + data.link + '&a=' + coords[0].toPrecision(6) + ',' + coords[1].toPrecision(6) + "&w=<?=$w?>",0);
                        myMap.balloon.close();
                     });
                },
                error: function (data) {
                    var textBody = '<p class=address>Ошибка 1</p>';
                    $('#error').append(textBody);
                }
            });
        });
    });
    <?php
    if (!$show_street){
       ?>
        myMap.events.add('contextmenu', function (e) {
            myMap.hint.show(e.get('coordPosition'), 'Данное событие недоступно');
        });
        <?php
    }
    if ($show_street){
    $tmp_name = '';
    $tmp_color = '#000';

            if (is_array($street_polyline)) {
                foreach ($street_polyline as $line) {
                    $ix = 0;
                    foreach ($line as $subline) {
                        if (!empty($street_name[$ix])) {
                            $tmp_name = $street_name[$ix];
                        }
                        if (!empty($street_color[$ix])) {
                            $tmp_color = $street_color[$ix];
                        }

                        $i = 0;
                        echo "\nvar streetLine".$ix." = new ymaps.Polyline([\n";
                        foreach ($subline as $point) {
                            if ($i!=0) {
                                echo ",";
                            }
                            echo $point;
                            $i++;
                        }
                        ?>
                        ], {
                                hintContent: "<?=$tmp_name?>",
                                    balloonContent: "<?=$tmp_name?>"
                            }, {
                                strokeColor: '<?=$tmp_color?>',
                                    strokeWidth: 6,
                                    opacity: 0.9,
                                    draggable: false
                            });
                    <?php
                    echo "myMap.geoObjects.add(streetLine".$ix.");\n";
                    $ix++;
                }

            }
        }
} else {
    if (count($placeMark)>0) {
        foreach ($placeMark as $k => $place) {
            echo $place."\n";
        }
    }
    if (count($placeMarkStop)>0) {
        foreach ($placeMarkStop as $k => $place) {
            echo $place."\n";
        }
    }
    if (count($placeMarkOrg)>0) {
        foreach ($placeMarkOrg as $k => $place) {
            echo $place."\n";
        }
    }
    if (count($placeMarkStreet)>0) {
        foreach ($placeMarkStreet as $k => $place) {
            echo $place."\n";
        }
    }
    if (count($placeMarkSearch)>0) {
        foreach ($placeMarkSearch as $k => $place) {
            echo $place."\n";
        }
    }
}
if (count($placeMarkStop)>0) {
    foreach ($placeMarkStop as $k => $place) {
        echo $place."\n";
    }
}

?>
    var firmCluster = new ymaps.Clusterer({
        clusterDisableClickZoom: true,
        preset: 'twirl#invertedVioletClusterIcons'
    });
    firmCluster.add(firmItem);

    var searchCluster = new ymaps.Clusterer({
        clusterDisableClickZoom: true,
        preset: 'twirl#invertedBrownClusterIcons'
    });
    searchCluster.add(searchItem);

    var streetCluster = new ymaps.Clusterer({
        clusterDisableClickZoom: true,
        preset: 'twirl#invertedBlueClusterIcons'
    });
    streetCluster.add(streetItem);

    var addressCluster = new ymaps.Clusterer({
        clusterDisableClickZoom: true,
        preset: 'twirl#invertedGreenClusterIcons'
    });
    addressCluster.add(addressItem);

    var busStopCluster = new ymaps.Clusterer({
        clusterDisableClickZoom: true,
        preset: 'twirl#invertedRedClusterIcons'
    });
    busStopCluster.add(busStops);

    myMap.geoObjects.add(addressCluster);
    myMap.geoObjects.add(busStopCluster);
    myMap.geoObjects.add(searchCluster);
    myMap.geoObjects.add(streetCluster);
    myMap.geoObjects.add(firmCluster);
    //myMap.geoObjects.add(streetLine);
    //streetLine.editor.startEditing();

    <?php
    if (intval($index)==0) {
        if ($a) {
        ?>

        myGeoObject[0] = new ymaps.GeoObject({
            geometry: {
                type: "Point",
                coordinates: [<?=$a?>]
            }
        }, {
            preset: 'twirl#greenStretchyIcon'
        });
        var addressCluster = new ymaps.Clusterer({clusterDisableClickZoom: true});
        addressCluster.add(myGeoObject);
        myMap.geoObjects.add(addressCluster);
        ymaps.geocode([<?=$a?>]).then(function (res) {
            var names = [];
            var res_arr = {};
            var arr = {};
            var rrr = {};
            var kind, name;
            res.geoObjects.each(function (obj) {
                arr = obj.properties.get('metaDataProperty');
                kind = arr['GeocoderMetaData'].kind;
                name = obj.properties.get('name');
                res_arr[kind] = name;
                names.push(obj.properties.get('name'));
                $.ajax({
                    url: 'ajax_request.php?t=2&c=' + encodeURIComponent(JSON.stringify(res_arr))+'&a=<?=$a?>',
                    timeout:3000,
                    dataType: "json",                     // тип загружаемых данных
                    success: function (data) { // вешаем свой обработчик на функцию success

                        var textBody = '<p class=address>Адрес: <b>' + names[2] + '</b>';
                        textBody += '<br><b>' + names[0] + ' </b>';
                        if (data.district) {
                            textBody += '<br>Район: <b>' + data.district + ' <a href="#" onclick=\"showRightAddressByLink(\''+data.link+'\',0)\">' + data.district_count + '</a></b>';
                        }
                        if (data.massive) {
                            textBody += '<br>Микрорайон: <b>' + data.massive + ' <a href="#" onclick=\"showRightAddressByLink(\''+data.link+'\',0)\">' + data.massive_count + '</a></b>';
                        }
                        textBody += '</p>';
    		            var link = 'a=<?=$a?>' + data.link;
                        textBody += '<p class=result>'+
                                    'Предложений: <b><a href="#" onclick=\"showRightRealtyByLink(\''+link+'\',0)\">' + data.count + '</a></b><br>'+
                                    'Организаций: <b><a href="#" onclick=\"showRightOrgsByLink(\''+link+'\',0)\">' + data.count_f + '</b></b>';
                        textBody = textBody + '</p>';
                        myGeoObject[0].properties.set({
                            clusterCaption: names[0],
                            iconContent: '',//names[0],
                            balloonContent: names[2] + ', ' + names[0],
                            balloonContentBody: textBody
                        });
                        /*
                        myMap.balloon.open(coords, {
                            contentHeader: ''
                            ,contentBody: textBody
                        });
                        */
                    },
                    error: function (data) {
                        var textBody = '<p class=address>Ошибка 1</p>';
                        $('#error').append(textBody);
                    }
                });
            });
        });

        <?php
        }
    }

    ?>
}
</script>
</head>
<body style="padding: 0px; margin: 0px;" id="body">
    <div id="map" style="clear:left; width:<?=$w?>px; height:475px;float:left;"></div>
</body>
</html>
