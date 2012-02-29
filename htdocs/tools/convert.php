<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" >
    <meta name="Distribution" content="Global" >
	</head>
  <body>
<?php
/**
 * 800x600 large	
 * 240x180 medium	
 * 52x39 mini
 */
/*
include '../libs/dibi/dibi/dibi.php';
include '../libs/Nette/Utils/Object.php';
include '../libs/Nette/Utils/Image.php';
include '../libs/Nette/Utils/String.php';

$errors = array();
$index = array(0=>0);
set_time_limit(180);

$options = array(
    'driver'   => 'mysql',
    'host'     => 'mysql.nw.cz:3306',
    'username' => '212_shop',
    'password' => 'shop',
    'database' => '212_shop',
		'charset'  => 'utf8'
);
dibi::connect($options);

mysql_connect("mysql.nw.cz:3306", "218_mercatores", "mercatores");
mysql_select_db("218_mercatores");
//ziskani kategorii
$res1 = mysql_query("SELECT id,jmeno_cs,jmeno_en,text_cs,text_en FROM categories");
while($info1 = mysql_fetch_assoc($res1))
{
  $oldId = $info1['id'];
  unset($info1['id']);
  $info1['link_cs'] = NString::webalize($info1['jmeno_cs']);
  $info1['link_en'] = NString::webalize($info1['jmeno_en']);
  dibi::query("INSERT INTO categories",$info1); 
  $index[$oldId] = dibi::getInsertId();
}
//vytvoreni stromu v kategoriich
$res1 = mysql_query("SELECT id,vlastnik FROM categories");
while($info1 = mysql_fetch_assoc($res1))
{
  dibi::query("UPDATE categories SET vlastnik=%i WHERE id=%i",$index[$info1['vlastnik']],$index[$info1['id']]);  
}

//ziskani produktu
$res1 = mysql_query("SELECT id,datum,jmeno_cs,jmeno_en,popis_cs,popis_en,show_cs,show_en,dodani,dph FROM products");
while($info1 = mysql_fetch_assoc($res1))
{
  
  $pid = $info1['id'];
  unset($info1['id']);
  $info1['link_cs'] = NString::webalize($info1['jmeno_cs']);
  $info1['link_en'] = NString::webalize($info1['jmeno_en']);
  $res2 = mysql_query("SELECT id_cat FROM sort WHERE id_prod=".$pid);
  $info2 = mysql_fetch_assoc($res2);
  $info1['owner'] = $index[$info2['id_cat']];
  if($info1['owner'] == NULL)$info1['owner'] = 0;
  dibi::query("INSERT INTO products",$info1);
  $newId = dibi::getInsertId();
  
  //vlastnosti
  $res2 = mysql_query("SELECT jmeno_cs,jmeno_en,prop_cs,prop_en FROM properties WHERE vlastnik=".$pid);
  while($info2 = mysql_fetch_assoc($res2))
  {
    $info2['vlastnik'] = $newId;
    dibi::query("INSERT INTO properties",$info2);
  }
  //varianty
  $res2 = mysql_query("SELECT id,jmeno_cs,jmeno_en,kus_cs,kus_en,sklad FROM variants WHERE vlastnik=".$pid);
  while($info2 = mysql_fetch_assoc($res2))
  {
    $info2['vlastnik'] = $newId;
    $res3 = mysql_query("SELECT cena FROM cenik WHERE id_var=".$info2['id']." ORDER BY datum DESC LIMIT 1");
    $info3 = mysql_fetch_assoc($res3);
    $info2['cena'] = $info3['cena'];
    $res3 = mysql_query("SELECT cena FROM cenik2 WHERE id_var=".$info2['id']." ORDER BY datum DESC LIMIT 1");
    $info3 = mysql_fetch_assoc($res3);
    $info2['ncena'] = (int)$info3['cena'];
    unset($info2['id']);
    dibi::query("INSERT INTO variants",$info2);
  }
  //obrazky
  $res2 = mysql_query("SELECT cesta,popis_cs,popis_en,razeni FROM images WHERE vlastnik=".$pid);
  while($info2 = mysql_fetch_assoc($res2))
  {
    $file = str_replace(' ', '%20', NString::fixEncoding($info2['cesta']));
    dibi::query("INSERT INTO images",array('popis_cs'=>$info2['popis_cs'],'popis_en'=>$info2['popis_en'],'vlastnik'=>$newId,'sort'=>$info2['razeni']));
    $newImageId = dibi::getInsertId();
    try{
    $image = NImage::fromFile('http://www.mercatores.cz/'.$file);
    $image->resize(800,600);
    $image->save('./images/uploaded/large'.$newImageId.'.jpg',100, NImage::JPEG);
    $image->resize(240,180);
    $image->save('./images/uploaded/medium'.$newImageId.'.jpg',100, NImage::JPEG);
    $image->resize(52,39);
    $image->save('./images/uploaded/mini'.$newImageId.'.jpg',100, NImage::JPEG);
    }catch(Exception $e){
      $errors[] = $e->getMessage();
      continue;
    }
  }
}
print_r($errors);