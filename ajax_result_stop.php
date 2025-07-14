<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 16.12.13
 * Time: 2:38
 * To change this template use File | Settings | File Templates.
 */
require_once "config.php";
$page = (intval($_GET['page'])>0)?intval($_GET['page']):0;
$limit = $page*$perPageAjax;
//обеспечивает постраничный вывод организаций
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

    if ($f) {
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
        if (count($items)>0){
            $sql = "SELECT count( DISTINCT s.name)
                        FROM map_station s
                    WHERE 1=1 ";
            for($i=0;$i<count($items);$i++) {
                $sql .= " AND s.name like '%".trim($items[$i])."%'";
            }
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            $total = $res->fetch_row();
            echo '<p class=small_orgs_com>Всего совпадений: '.$total[0].'</p>';

            echo '<a onclick="$(\'#ol_stp\').toggle();" id="org_a" href="javascript:;">+</a>';
            echo "<br class='clear'>";
            echo "<div style='".($_GET['show']==1?'display:block':'display:none')."' id='ol_stp'>";

            if ($total[0]>0) {
                $maxPage = ($total[0]%$perPageAjax)>0?(($total[0]-($total[0]%$perPageAjax))/$perPageAjax):$total[0]/$perPageAjax-1;//потому что страницы от 0

                $sql = "SELECT DISTINCT fid, routes,
                            s.name,
                            asText(geometry) as geo_obj
                        FROM map_station s
                    WHERE 1=1 ";
                    for($i=0;$i<count($items);$i++) {
                        $sql .= " AND s.name like '%".trim($items[$i])."%'";
                    }
                        $sql .=" GROUP BY s.name order by s.name asc

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
                    $line = str_replace("POINT(","",$row['geo_obj']);
                    $line = str_replace(")","",$line);
                    $coord = explode(" ",$line);
                    $ix = $coord[0];
                    $igrek = $coord[1];
                    $centerX = round(y2lat($igrek),6);
                    $centerY = round(x2lon($ix),6);
                    $row['centerX'] = $centerX;
                    $row['centerY'] = $centerY;


                    //$i++;
                    $j++;
                    $routes = explode(',',$row['routes']);
                    $new_route_line = "";
                    foreach ($routes as $line){
                        $oline = trim($line);
                        $line = trim($line);
                        $line = str_replace("А","Автобус №",$line);
                        $line = str_replace("Т","Троллейбус №",$line);
                        $line = str_replace("М","Марштурка №",$line);
                        if ($i!=0) {
                            $new_route_line .= ', ';
                        }
                        $new_route_line .= '<a onclick="showRoute(\''.$oline.'\')" href=# style="white-space:nowrap;">'.$line.'</a>';
                        $i++;
                    }
                    $resStr .= '<p class=small_orgs><b>'.$j.'.</b> '
                        .'Название: '.$row['name'].'<br>'
                        .'Маршруты: '.$new_route_line.' ';
                    $resStr .= '<br><a onclick="showObject('.$row['centerX'].','.$row['centerY'].',\'bs='.$row['fid'].'\')" href="javascript:;">посмотреть на карте</a> '//.$row['yandex_status']
                        .'</p>';
                    $i++;
                }
                //echo $page.' : '.$maxPage.'<br>';
                //$link = "'".'m='.$m.'&d='.$d.'&h='.$h.'&s='.$s.'&a='.$a.'&f='.urlencode($f)."'";
                $link = "'".'a='.$a.'&f='.urlencode($f)."'";
                echo $resStr;
                echo '<p class=paginator>'.($page>0?'<a href="javascript:;" onclick="getResultStops('.$link.','.($page-1).',1)"><img src="/images/black_arrow_left.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow_left.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow_left.png\');" class="left_arrow"></a>':'')
                    //.($page>0?' || ':'')
                    .($page<$maxPage?'<a href="javascript:;" onclick="getResultStops('.$link.','.($page+1).',1)"><img src="/images/black_arrow.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow.png\');" class="right_arrow"></a>':'').' ';
                echo "</div>";

            }

        } else {
            echo '<p class=small_orgs>Ничего найдено не было</p>';
        }
    }

//}