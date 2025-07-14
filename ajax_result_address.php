<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 29.11.13
 * Time: 4:02
 * To change this template use File | Settings | File Templates.
 */
require_once "config.php";
$page = (intval($_GET['page'])>0)?intval($_GET['page']):0;
//обеспечивает постраничный вывод объектов недвижимости
$limit = $page*$perPageAjax;
//if ($_GET['show']==1){
    if ($_GET['a']){
        list($a,$b) = explode(',',$_GET['a']);
        $x = round(floatval($a),6);
        $y = round(floatval($b),6);
    }
    $s = intval($_GET['s']);
    $d = intval($_GET['d']);
    $h = intval($_GET['h']);
    $m = intval($_GET['m']);
    $f = $_GET['f'];
    if (!$f) {
        if ($s || $m || $d || $s) {

            $sql = "SELECT
                    distinct k_shn_street_id
                    FROM  k_streets_house_nums
                    WHERE 1=1".
                ($m?" AND k_shn_massive_id = ".$m:"").
                ($d?" AND k_shn_district_id = ".$d:"").
                ($s?" AND k_shn_street_id = ".$s:"")
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


            if ($h) {
                $sql = "SELECT
                        distinct k_shn_house_num
                        FROM  k_streets_house_nums
                        WHERE 1=1".
                    ($h?" AND k_shn_id = ".$h:"");
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
                $sql = "SELECT count(distinct k_shn_house_num)
                                    FROM k_streets_house_nums
                            WHERE k_shn_street_id IN (".($street_line).")".
                            ($house_line?" AND trim(k_shn_house_num) IN (".$house_line.")":"");
                //echo $sql.'<br>';
                $res = $mysqli->query($sql);
                $res->data_seek(0);
                $total = $res->fetch_row();
                echo '<p class=small_orgs_com>Всего совпадений: '.$total[0].'</p>';

                echo '<a onclick="$(\'#ol_addr\').toggle();" id="org_a" href="javascript:;">+</a>';
                echo "<br class='clear'>";
                echo "<div style='".($_GET['show']==1?'display:block':'display:none')."' id='ol_addr'>";

                if ($total[0]>0) {
                    $sql = "SELECT distinct
                            n.k_shn_house_num,
                            n.centerX,
                            n.centerY,
                            s.k_s_name as street,
                            m.k_tm_name as massive,
                            d.k_d_name as district
                            FROM k_streets_house_nums n
                            LEFT JOIN k_streets s ON s.k_s_id=n.k_shn_street_id
                            LEFT JOIN k_towns_massives m ON m.k_tm_id=n.k_shn_massive_id
                            LEFT JOIN k_districts d ON d.k_d_id=n.k_shn_district_id
                            WHERE n.k_shn_street_id IN (".($street_line).")".
                            ($house_line?" AND trim(n.k_shn_house_num) IN (".$house_line.")":"").
                            "
                            limit ".$limit.",".$perPageAjax
                    ;
                    //echo ''.$sql.'<br>';
                    $res = $mysqli->query($sql);
                    $res->data_seek(0);
                    $ids = array();
                    $i=$page*$perPageAjax;
                    $j=0;
                    $resStr = "";
                    while ($row = $res->fetch_assoc()) {
                        $i++;
                        $j++;
                        $resStr .= '<p class=small_orgs><b>'.$i.'.</b> Адрес: '
                            .$row['street'].', '.$row['k_shn_house_num'].'<br>'
                            .($row['district']?'Район: '.$row['district'].'<br>':'')
                            .($row['massive']?'Микрорайон: '.$row['massive'].'<br>':'')
                            .'<a onclick="showObject('.$row['centerX'].','.$row['centerY'].')" href="javascript:;">посмотреть на карте</a>'
                            .'</p>';
                    }
                    /*
                    echo '<p class=paginator>'.($page>0?'<a href="javascript:;" onclick="getResultAddress(location.search.slice(1),'.($page-1).')"><<</a>':'').
                        ($page>0?' || ':'')
                        .($j==$perPageAjax?'<a href="javascript:;" onclick="getResultAddress(location.search.slice(1),'.($page+1).')">>></a>':'').' ';
                    echo $resStr;
                    */
                    echo $resStr;
                    echo '<p class=paginator>'.($page>0?'<a href="javascript:;" onclick="getResultAddress(location.search.slice(1),'.($page-1).',1,1)"><img src="/images/black_arrow_left.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow_left.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow_left.png\');" class="left_arrow"></a>':'')
                        //.($page>0?' || ':'')
                        .($j==$perPageAjax?'<a href="javascript:;" onclick="getResultAddress(location.search.slice(1),'.($page+1).',1,1)"><img src="/images/black_arrow.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow.png\');" class="right_arrow"></a>':'').' ';
                    echo "</div>";

                }
            } else {
                echo '<p class=small_orgs>Ошибка! Неверный адрес.</p>';
            }
        } else {
            echo '<p class=small_orgs>Ошибка! Неверные параметры.</p>';
        }
    } else {


        $q = trim($f);
        $items = strip_string($q);
        //var_dump($items);
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

        //if (count($streets)>0){
        if (count($items)>0){

            $sql = "SELECT count( h.k_shn_house_num)
                        FROM k_streets_house_nums h
                        LEFT JOIN k_streets s ON h.k_shn_street_id=s.k_s_id
                        LEFT JOIN k_districts d ON h.k_shn_district_id=d.k_d_id
                        LEFT JOIN k_towns_massives m ON h.k_shn_massive_id=m.k_tm_id
                    WHERE s.k_s_name NOT like '%##%' ";
            for($i=0;$i<count($items);$i++) {
                $sql .= " AND concat(ifnull(d.k_d_name,' '),' ',ifnull(m.k_tm_name,' '),' ',s.k_s_name,' ',trim(h.k_shn_house_num)) like '%".trim($items[$i])."%'";
            }
            //echo $sql.'<br>';
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            $total = $res->fetch_row();
            echo '<p class=small_orgs_com>Всего совпадений: '.$total[0].'</p>';

            echo '<a onclick="$(\'#ol_addr\').toggle();" id="org_a" href="javascript:;">+</a>';
            echo "<br class='clear'>";
            echo "<div style='".($_GET['show']==1?'display:block':'display:none')."' id='ol_addr'>";

            if ($total[0]>0) {
                $maxPage = ($total[0]%$perPageAjax)>0?(($total[0]-($total[0]%$perPageAjax))/$perPageAjax):$total[0]/$perPageAjax-1;//потому что страницы от 0

                $sql = "SELECT DISTINCT
                            d.k_d_name as district,
                            m.k_tm_name as massive,
                            s.k_s_name as street,
                            h.k_shn_house_num as house,
                            h.centerX,
                            h.centerY,
                            yandex_status
                        FROM k_streets_house_nums h
                        LEFT JOIN k_streets s ON h.k_shn_street_id=s.k_s_id
                        LEFT JOIN k_districts d ON h.k_shn_district_id=d.k_d_id
                        LEFT JOIN k_towns_massives m ON h.k_shn_massive_id=m.k_tm_id
                    WHERE s.k_s_name NOT like '%##%' ";
                    for($i=0;$i<count($items);$i++) {
                        $sql .= " AND concat(ifnull(d.k_d_name,' '),' ',ifnull(m.k_tm_name,' '),' ',s.k_s_name,' ',trim(h.k_shn_house_num)) like '%".trim($items[$i])."%'";
                    }
                        $sql .=" order by s.k_s_name, h.k_shn_house_num asc
                        limit ".$limit.",".$perPageAjax
                ;
                //echo $sql.'<br>';
                $res = $mysqli->query($sql);
                $res->data_seek(0);
                $ids = array();
                $i=$page*$perPageAjax;
                $j=0;

                $resStr = "";

                while ($row = $res->fetch_assoc()) {
                    $i++;
                    $j++;
                    $resStr .= '<p class=small_orgs><b>'.$i.'.</b> Адрес: '
                        .$row['street'].', '.$row['house'].'<br>';
                    if ($row['massive']!=$row['district']) {
                        $resStr .= ($row['district']?'Район: '.$row['district'].'<br>':'')
                                   .($row['massive']?'Микрорайон: '.$row['massive'].'<br>':'');

                    } else {
                        $resStr .= $row['district'].'<br>';
                    }
                    $resStr .= '<a onclick="showObject('.$row['centerX'].','.$row['centerY'].')" href="javascript:;">посмотреть на карте</a> '//.$row['yandex_status']
                        .'</p>';
                }
                //echo $page.' : '.$maxPage.'<br>';
                //$link = "'".'m='.$m.'&d='.$d.'&h='.$h.'&s='.$s.'&a='.$a.'&f='.urlencode($f)."'";
                $link = "'".'a='.$a.'&f='.urlencode($f)."'";
                /*
                echo '<p class=paginator>'.($page>0?'<a href="javascript:;" onclick="getResultAddress('.$link.','.($page-1).')"><<</a>':'').
                    ($page>0?' || ':'')
                    .($page<$maxPage?'<a href="javascript:;" onclick="getResultAddress('.$link.','.($page+1).')">>></a>':'').' ';
                echo $resStr;
                */
                echo $resStr;
                echo '<p class=paginator>'.($page>0?'<a href="javascript:;" onclick="getResultAddress('.$link.','.($page-1).',1)"><img src="/images/black_arrow_left.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow_left.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow_left.png\');" class="left_arrow"></a>':'')
                    //.($page>0?' || ':'')
                    .($page<$maxPage?'<a href="javascript:;" onclick="getResultAddress('.$link.','.($page+1).',1)"><img src="/images/black_arrow.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow.png\');" class="right_arrow"></a>':'').' ';
                echo "</div>";

            }
        } else {
            echo '<p class=small_orgs>Ничего найдено не было</p>';
        }
    }
//}

