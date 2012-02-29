<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Insert title here</title>
</head>
<body>
<?php
mysql_connect("mysql.nw.cz:3306", "218_mercatores", "mercatores");
mysql_select_db("218_mercatores");
/*
$res = mysql_query("SELECT B.id,V.cena FROM basket B JOIN variants V ON B.id_var=V.id WHERE B.price = 0 AND B.id_obj != 0");
while($info = mysql_fetch_array($res))
{
	mysql_query("UPDATE basket SET price=".$info['cena']." WHERE id=".$info['id']);
}*/
?>
</body>
</html>