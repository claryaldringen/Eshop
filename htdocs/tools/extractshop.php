<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Insert title here</title>
</head>
<body>
<?php
mysql_connect("mysql.nw.cz:3306", "218_mercatores", "mercatores");
mysql_select_db("218_mercatores_old");

$res = mysql_query("SELECT SUM(count) AS pocet,id_var FROM basket WHERE id_obj!=0 GROUP BY id_var ORDER BY pocet DESC");
while($info = mysql_fetch_array($res))
{
	$res2 = mysql_query("SELECT V.jmeno_cs AS varianta,P.jmeno_cs AS produkt FROM variants V JOIN products P ON V.vlastnik=P.id WHERE V.id=".$info['id_var']);
	$info2 = mysql_fetch_array($res2);
	if($info2['produkt'])echo $info['pocet'].'x '.$info2['produkt'].' '.$info2['varianta'].'<br>';
}
?>
</body>
</html>