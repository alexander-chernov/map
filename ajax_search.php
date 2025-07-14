<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 27.10.13
 * Time: 23:29
 * To change this template use File | Settings | File Templates.
 */
require_once "config.php";
$page = (intval($_GET['page'])>0)?intval($_GET['page']):0;
$limit = $page*$perPage;
//результаты автозаполнения для строки поиска по адресу
if ($_GET['term']){
    //$q = trim($_GET['term']);
    $f = trim($_GET['term']);
    if (strpos($f," ")) {
        preg_match_all ('/[a-zA-Zа-яА-Я0-9]+/u', $f, $items, PREG_PATTERN_ORDER);
        //preg_match_all ($pattern, $q, $items, PREG_PATTERN_ORDER);
        foreach ($items[0] as $item) {
            $res[] = $item;
	}
        $items = $res;
    } else {
        preg_match_all ('/[a-zA-Zа-яА-Я0-9]+/u', $f, $res, PREG_PATTERN_ORDER);
        $items = $res[0];
    }
    //echo var_export($items,true).'<br>';
    if (count($items)==1) {
        $sql = "SELECT DISTINCT s.word as id ".
                " FROM search_index s ".
                " WHERE  ";
        $sql .= " s.word like '".$items[0]."%' ";
        $sql .= " ORDER BY s.word asc ";
        $sql .= " limit ".$limit.",".$perPage." ";
        $res = $mysqli->query($sql);
        $res->data_seek(0);
        while ($row = $res->fetch_assoc()) {
            $res_arr[] = $row['id'];
        }
    } else {
        $pred_list = array();
        $now_list = array();
        $res_arr = array();
        $request_str = '';

        $j==0;
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
            $request_str .= $items[$i].' ';
        }

        //echo $sqlA.'<br>';
        $sql = "SELECT concat('".$request_str."',s.word) as id ".
                " FROM search_index s ".
                " WHERE 1=1 ";
        $sql .= $sqlA;
        $sql .= " limit ".$limit.",".$perPage." ";
        $res = $mysqli->query($sql);
        $res->data_seek(0);
        while ($row = $res->fetch_assoc()) {
            $res_arr[] = $row['id'];
        }
//TODO: в случае сложных запросов (из более, чем одного слова), возвращать в value не одно слово, а несколько, т.е. с предыдущимии. чтоб не делать автокомплит multiple

    }
    //echo $sql.'<br>';

    //var_dump($res_arr);
    //echo '<br>';
    //echo '<br>';
    //var_dump(array_unique($res_arr));

    $ua = array_unique($res_arr);
    foreach ($ua as $item){
        $result[]['id'] = $item;
        $result[]['label'] = $item;
        $result[]['value'] = $item;
    }

    //die();

}

$result = json_encode($ua);
echo $result;