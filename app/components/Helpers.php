<?php

class MyHelpers
{
  	  
		public static function loader($helper)
    {
        $callback = callback(__CLASS__, $helper);
        if ($callback->isCallable()) {
            return $callback;
        }
    }
    
    public static function inWords($number)
    {
    	$number = (int)$number;
    	$konecneCislo = '';
    	$cislo = '';
    	$begin = true;

    	$cislice = array(
    		array('', 'jednosto', 'dvěstě', 'třista', 'čtyřista', 'pětset', 'šestset', 'sedmset', 'osmset', 'devětset'),
    		array('', '', 'dvacet', 'třicet', 'čtyřicet', 'padesát', 'šedesát', 'sedmdesát', 'osmdesát', 'devadesát'),
    		array('', 'jedentisíc', 'dvatisíce', 'třitisíce', 'čtyřitisíce', 'pěttisíc', 'šestisíc', 'sedmtisíc', 'osmtisíc', 'devěttisíc', 
    			'desettisíc', 'jedenácttisíc', 'dvanácttisíc', 'třinácttisíc', 'čtrnácttisíc', 'patnácttisíc', 'šestnácttisíc', 
    			'sedmnácettisíc', 'osmnácttisíc', 'devatenácttisíc'),
    		array('', 'jednosto', 'dvěstě', 'třista', 'čtyřista', 'pětset', 'šestset', 'sedmset', 'osmset', 'devětset'),
    		array('', '', 'dvacet', 'třicet', 'čtyřicet', 'padesát', 'šedesát', 'sedmdesát', 'osmdesát', 'devadesát'),
    		array('', 'jedna', 'dva', 'tři', 'čtyři', 'pět', 'šest', 'sedm', 'osm', 'devět', 'deset', 'jedenáct', 'dvanáct', 'třináct', 'čtrnáct', 'patnáct', 'šestnáct', 'sedmnácet', 'osmnáct', 'devatenáct')
    		);
    	
    	$number = sprintf('%06s', $number);
    	
    	for($start=0;$start<7;$start++)
    	{
    		$cislo .= substr($number, $start, 1);
    		if($cislo > 1 || in_array($start, array(0,2,3,5)))
    		{
    			$konecneCislo .= $cislice[$start][(int)$cislo];
    			$cislo = '';
    			$begin = false;
    		}
    	}
    	
    	return $konecneCislo;
    }
    
    public static function fillZeros($number, $count)
    {
    	return sprintf('%0'.$count.'s', (int)$number);
    }

    public static function fillNbsp($number, $count)
    {
    	return sprintf("%'-".$count.'s', $number);
    }
}
