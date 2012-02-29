<?php
/*
class MailParser {
	
	private function open($mailbox, $username, $password)
	{
		echo 'neco';
		
		return $imap;	
	}
	
	public function getMails($mailbox, $username, $password)
	{
		var_dump(array('neco'));
		$imap = $this->open($mailbox, $username, $password);
		var_dump($imap);
		//$mails = imap_sort($imap, SORTARRIVAL, 1);
		var_dump($mails);
		
	}
}

echo 'neco';
$mailparser = new MailParser();
echo 'neco';
$mailparser->getMails('{localhost}', 'shop@mercatores.cz', 'mercatores');
*/

/*$user= "fio@nodus.cz";
$pass= "heslo";
$host = "mail.nodus.cz:143/imap/notls";*/

$user= "shop@mercatores.cz";
$pass= "mercatores";
$host = "mail.nw.cz:143/imap/notls";

$mbox = imap_open ("{".$host."}", $user, $pass);
if(!$mbox) echo imap_last_error(); 
echo 'jsem tam';
