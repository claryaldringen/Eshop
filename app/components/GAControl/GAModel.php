<?php

class GAModel {

	public function getOrder($id,$lang = 'cs')
	{
		$cnb = new Cnb();
		$money = $cnb->getMoney('EUR');
		
		$result1 = dibi::fetch("SELECT O.id,IF(O.cena > zdarma_od,0,(D.cena+P.cena)*%f) AS dodani,(O.cena*%f) AS cena,U.mesto,C.printable_name AS stat 
			FROM objednavka O JOIN basket B ON B.id_obj=O.id 
			JOIN platby P ON P.id=O.platba 
			JOIN dodani D ON D.id=O.dodani 
			JOIN users U ON B.id_user=U.id 
			JOIN country C ON U.stat=C.numcode
			WHERE B.id_obj=%i GROUP BY B.id_obj",$money['rate'],$money['rate'],$id);
			
		$result1->items = dibi::query("SELECT V.id,P.jmeno_$lang AS product,V.jmeno_$lang AS variant,((V.cena*(1+P.dph/100))*%f) AS cena,count FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id_obj=%i",$money['rate'],$result1->id)->fetchAll();
		
		return $result1;
	}
}
