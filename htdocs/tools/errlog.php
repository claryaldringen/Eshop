<?php

$slozka = dir('../../log/');
	while($soubor = $slozka->read()) 
	{
  	if ($soubor=="." || $soubor=="..") continue;
  	$include = $soubor;
	}
$slozka->close();		

include '../../log/'.$include;
