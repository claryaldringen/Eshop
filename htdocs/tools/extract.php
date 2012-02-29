<html>
  <head>
    <meta name="Description" content="" >
    <meta name="Keywords" content="" >
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" >
    <meta name="Distribution" content="Global" >
    <meta name="Author" content="Softweb.cz - www.softweb.cz" >
    <meta name="Robots" content="all, index, follow" >
		<meta name="google-site-verification" content="lWMcmVi37gM16Yw8n7IOCZhlgCgcu8oAfkTpm0Ha-AU">
    <title></title>
		<link rel="stylesheet" media="screen,projection,tv" href="{$basePath}/css/style.css" type="text/css">
		<link rel="stylesheet" type="text/css" href="{$basePath}/css/jquery.lightbox-0.5.css" media="screen" />
		
		<script src="{$basePath}/js/jquery.js" type="text/javascript"></script>
		<script src="{$basePath}/js/jquery.livequery.js" type="text/javascript"></script>
		<script src="{$basePath}/js/jquery.editinplace.0.4.js" type="text/javascript"></script>
		<script src="{$basePath}/js/jquery.nette.js" type="text/javascript"></script>
		<script src="{$basePath}/js/jquery-ui.js" type="text/javascript"></script>
		<script src="{$basePath}/js/netteForms.js" type="text/javascript"></script>
		<script src="{$basePath}/js/jquery.lightbox-0.5.js" type="text/javascript" ></script>
		<script src="{$basePath}/ckeditor/ckeditor.js" type="text/javascript"></script>
		<!--[if IE]> 
		<style type="text/css">
			select{ background:white; color:black !important;height:20px; }
		</style>
		<![endif]-->
  </head>
<?php
require_once '../../libs/dibi/dibi/dibi.php';

class Extract
{
	public function __construct()
	{
		dibi::connect(array(
		'host' => "mysql.nw.cz:3306", 
		'username' => "218_mercatores", 
		'password' => "mercatores",
		'database' => "218_mercatores",
		'charset' => "utf8",
		'driver' => "mysql"
		));
	}

	private function getProducts()
	{
		return dibi::query("SELECT * FROM products WHERE status=%s AND popis_en IS NOT NULL",'ok')->fetchAll();
	}
	
	private function getProperties($owner)
	{
		return dibi::query("SELECT * FROM properties WHERE vlastnik=%i",$owner)->fetchAll();	
	}
	
	private function getVariants($owner)
	{
		return dibi::query("SELECT * FROM variants WHERE vlastnik=%i",$owner)->fetchAll();	
	}
	
	public function getHtml()
	{
		foreach($this->getProducts() as $product)	
		{
			echo '<h3>'.$product->jmeno_cs.'</h3>';
			foreach($this->getProperties($product->id) as $prop)
			{
				echo $prop->jmeno_cs.': '.$prop->prop_cs.'<br>';
			}
			echo '<p>'.$product->popis_cs.'</p>';
			foreach($this->getVariants($product->id) as $prop)
			{
				if($prop->jmeno_cs)echo $prop->jmeno_cs.' 100 Kč/'.$prop->kus_cs.'<br>';
			}
			
			echo '<hr>';
		}
	}
	
}

$extract = new Extract();
$extract->getHtml();