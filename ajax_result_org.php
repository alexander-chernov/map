<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 11.11.13
 * Time: 0:32
 * To change this template use File | Settings | File Templates.
 */
require_once "config.php";
$page = (intval($_GET['page'])>0)?intval($_GET['page']):0;
$limit = $page*$perPageAjax;
//обеспечивает постраничный вывод организаций
//if ($_GET['show']==1){
    if ($_GET['a']){
        $coords = $_GET['a'];
        list($a,$b) = explode(',',$coords);
        $x = round(floatval($a),6);
        $y = round(floatval($b),6);
    }
    $s = intval($_GET['s']);
    $d = intval($_GET['d']);
    $h = intval($_GET['h']);
    $m = intval($_GET['m']);
    $f = $_GET['f'];
    if (!$f) {
        if ($s) {
            if ($m || $d || $s) {
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

            }
            if ($h) {
                $sql = "SELECT
                        distinct k_shn_house_num
                        FROM  k_streets_house_nums
                        WHERE 1=1".
                    ($h?" AND k_shn_id = ".$h."":"");
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
                $sql = "SELECT count(distinct name,address)
                                    FROM base_org
                            WHERE street_id IN (".($street_line).")".
                    ($house_line?" AND trim(house_num) IN (".$house_line.")":"");
                //echo $sql.'<br>';
                $res = $mysqli->query($sql);
                $res->data_seek(0);
                $total = $res->fetch_row();
                echo '<p class=small_orgs_com>Всего совпадений: '.$total[0].'</p>';

                echo '<a onclick="$(\'#ol_org\').toggle();" id="org_a" href="javascript:;">+</a>';
                echo "<br class='clear'>";
                echo "<div style='".($_GET['show']==1?'display:block':'display:none')."' id='ol_org'>";

                if ($total[0]>0) {
                    $sql = "SELECT distinct
                            name, address, site, email, phone1, phone2, phone3, phone4, centerX, centerY
                            FROM base_org
                            WHERE street_id IN (".($street_line).")".
                    ($house_line?" AND trim(house_num) IN (".$house_line.")":"").
                        " order by name asc
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
                        $resStr .= '<p class=small_orgs><b>'.$i.'</b>. '.$row['name']
                            .($row['address']?'<br>'.$row['address']:'')
                            .($row['site']?'<br><a target=blank href="'.$row['site'].'">'.($row['site']).'</a>':'')

                            .($row['email']?'<br><a target=blank href="mailto:'.$row['email'].'">'.$row['email'].'</a>':'')
                            .($row['phone1']?'<br>тел.'.$row['phone1']:'')
                            .($row['phone2']?'<br>тел.'.$row['phone2']:'')
                            .($row['phone3']?'<br>тел.'.$row['phone3']:'')
                            .($row['phone4']?'<br>тел.'.$row['phone4']:'')
                            .(!$h?'<br><a onclick="showObject('.$row['centerX'].','.$row['centerY'].')" href="javascript:;">посмотреть на карте</a>':'')
                            .'</p>';
                    }
                    $link = 'm='.$m.'&d='.$d.'&h='.$h.'&s='.$s.'&a='.$a.'&f='.$f;
                    /*
                    echo '<p class=paginator>'.($page>0?'<a href="javascript:;" onclick="getResultOrg(\''.$link.'\','.($page-1).')"><<</a>':'').
                        ($page>0?' || ':'')
                        .($j==$perPageAjax?'<a href="javascript:;" onclick="getResultOrg(\''.$link.'\','.($page+1).')">>></a>':'').' ';
                    echo $resStr;
                    */
                    echo $resStr;
                    echo '<p class=paginator>'.($page>0?'<a href="javascript:;" onclick="getResultOrg(\''.$link.'\','.($page-1).',1)"><img src="/images/black_arrow_left.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow_left.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow_left.png\');" class="left_arrow"></a>':'')
                        //.($page>0?' || ':'')
                        .($page<$maxPage?'<a href="javascript:;" onclick="getResultOrg(\''.$link.'\','.($page+1).',1)"><img src="/images/black_arrow.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow.png\');" class="right_arrow"></a>':'').' ';
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
        //var_dump($newItems);
        if (count($items)==1) {
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
        //echo $sql.'<br>';
        //var_dump($res_arr);
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
                /*
                $word = $row['word'];
                if (is_array($word)) {
                    $res_arr[] = $word['word'];
                } else {
                    $res_arr[] = $word;
                }
                */
                if (!empty($row['base_org_id'])) {
                    $orgs[] = $row['base_org_id'];
                }
                if (!empty($row['street_id'])) {
                    $streets[] = $row['street_id'];
                }
            }
        }
        if (count($orgs)>0) {
            $org_line = implode(",",$orgs);
        }
        if (count($streets)>0) {
            $street_line = implode(",",$streets);
        }

        //echo $sql.'<br>';
        //var_dump($res_arr);
        if (count($orgs)>0){
            $sql = "SELECT count(distinct name,address)
                    FROM base_org
                    WHERE id IN (".($org_line).")".
                    ($street_line?" AND street_id IN (".$street_line.")":"");
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            $total = $res->fetch_row();
            echo '<p class=small_orgs_com>Всего совпадений: '.$total[0].'</p>';

            echo '<a onclick="$(\'#ol_org\').toggle();" id="org_a" href="javascript:;">+</a>';
            echo "<br class='clear'>";
            echo "<div style='".($_GET['show']==1?'display:block':'display:none')."' id='ol_org'>";

            if ($total[0]>0) {
                $maxPage = ($total[0]%$perPageAjax)>0?(($total[0]-($total[0]%$perPageAjax))/$perPageAjax):$total[0]/$perPageAjax-1;//потому что страницы от 0

                $sql = "SELECT DISTINCT
                        name, address, site, email, phone1, phone2, phone3, phone4,centerX,centerY
                        FROM base_org
                        WHERE id IN (".($org_line).")".
                        ($street_line?" AND street_id IN (".$street_line.")":"").
                        " order by name asc
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
                    $resStr .= '<p class=small_orgs><b>'.$i.'</b>. '.$row['name']
                        .($row['address']?'<br>'.$row['address']:'')
                        .($row['site']?'<br><a target=blank href="'.$row['site'].'">'.($row['site']).'</a>':'')

                        .($row['email']?'<br><a target=blank href="mailto:'.$row['email'].'">'.$row['email'].'</a>':'')
                        .($row['phone1']?'<br>тел.'.$row['phone1']:'')
                        .($row['phone2']?'<br>тел.'.$row['phone2']:'')
                        .($row['phone3']?'<br>тел.'.$row['phone3']:'')
                        .($row['phone4']?'<br>тел.'.$row['phone4']:'')
                        .(!$h?'<br><a onclick="showObject('.$row['centerX'].','.$row['centerY'].')" href="javascript:;">посмотреть на карте</a>':'')
                        .'</p></li>';
                }
                $resStr .= "";
                //echo $page.' : '.$maxPage.'<br>';
                //$link = "'".'m='.$m.'&d='.$d.'&h='.$h.'&s='.$s.'&a='.$a.'&f='.urlencode($f)."'";
                $link = "'".'a='.$a.'&f='.urlencode($f)."'";
                echo $resStr;
                echo '<p class=paginator>'.($page>0?'<a href="javascript:;" onclick="getResultOrg('.$link.','.($page-1).',1)"><img src="/images/black_arrow_left.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow_left.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow_left.png\');" class="left_arrow"></a>':'')
                    //.($page>0?' || ':'')
                    .($page<$maxPage?'<a href="javascript:;" onclick="getResultOrg('.$link.','.($page+1).',1)"><img src="/images/black_arrow.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow.png\');" class="right_arrow"></a>':'').' ';
                echo "</div>";
            }
        } else {
            echo '<p class=small_orgs>Ничего найдено не было</p>';
        }
    }
//}
