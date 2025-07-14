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
//результаты автозаполнения для строки поиска по организации
if ($_GET['q']){
    $q = trim($_GET['q']);

    $sql = "SELECT
                distinct
                o.town,
                o.street,
                o.house_num as house,
                o.name,
                o.centerX as X,
                o.centerY as Y
            FROM base_org o
            WHERE 1=1

            ";
    $sql .= " AND o.name like '".$q."%'";
    $sql .= " ORDER BY o.name ASC ";
    $sql .= "
            limit ".$limit.",".$perPage;
    //echo $sql;

}
$res = $mysqli->query($sql);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
    $res_arr[] = $row;
}
$link .= "&f=".urlencode(serialize($ids));
$result['link'] = $link;

$result = json_encode($res_arr);
echo $result;