<?php

class MailModel extends BaseModel{

	public function setMail($co,$stav)
	{
		$cnb = new Cnb($this->context->params['tempDir']);
		$model = $this->getInstanceOf('ProductModel');
		$config = (object)$this->context->params['mail'];
		$result = dibi::fetch("SELECT * FROM objednavka WHERE id=%i",$co);
		$user = dibi::fetch("SELECT U.* FROM  users U JOIN basket B ON U.id=B.id_user WHERE B.id_obj=%i",$result->id);
		$platba = dibi::fetch("SELECT D.email_".$result->lang." AS email,D.emailsub_".$result->lang." AS emailsub,cena FROM dodaniplatba D JOIN platby P ON P.id=D.platba WHERE dodani=%i AND platba=%i",$result->dodani, $result->platba);
		$dodani = dibi::fetch("SELECT email_".$result->lang." AS email,emailsub_".$result->lang." AS emailsub,cena FROM dodani WHERE id=%i",$result->dodani);
		if($stav == 'prijato')$html = $platba;
		elseif($stav != 'vyrizeno')$html = $dodani;
		$co = array('{cislo}','{datum}','{polozky}','{cena}','{expedice}','{jmeno}','{ulice}','{mesto}','{psc}','{email}','{platba}','{postovne}','{platbaapostovne}');
		$template = new NFileTemplate($this->context->params['appDir'] . '/templates/items.phtml');
		$template->registerFilter(new NLatteFilter());
		$template->registerHelper('currency',array($cnb,'format'));
		$template->mena = $user->mena;
		$template->items = $model->getBasketDetail($user->id,$result->lang,$result->id);
		$template->basket = $model->getBasket($user->id,$result->id);

		$template->specs = array();
		foreach($template->items as $item)
		{
			if($item->type == 'special')$template->specs[] = $model->getSpecialFromOrder($item->id_var,$result->lang);
		}

		$cim = array($result->id,NTemplateHelpers::date($result->datum,'%d.%m.%Y'),$template,$cnb->format($result->cena,'CZK',$user->mena),$result->expedice,$user->jmeno.' '.$user->prijmeni,$user->ulice,$user->mesto,$user->psc,$user->email,$cnb->format($platba->cena,'CZK',$user->mena),$cnb->format($dodani->cena,'CZK',$user->mena));
		$html->email = str_replace($co,$cim,$html->email);
		$subject = str_replace($co,$cim,$html->emailsub);

		$mail = new NMail();
		$mail->setSubject($subject);
		$mail->addTo($user->email,$user->jmeno.' '.$user->prijmeni);
		$mail->setFrom($config->frommail,$config->fromname);
		$mail->setHtmlBody($html->email);
		$mail->send();

		$mail = new NMail();
		$mail->setSubject($subject);
		$mail->addTo($config->bccmail);
		$mail->setFrom($user->email,$user->jmeno.' '.$user->prijmeni);
		$mail->setHtmlBody($html->email);
		$mail->send();
	}

	public function sendMessage($data)
	{
		$config = (object)$this->context->params['mail'];

		$mail = new NMail();
		$mail->setSubject('Vzkaz z kontaktního formuláře');
		$mail->addTo($config->bccmail);
		$mail->setFrom($data['email']);
		$mail->setBody($data['message']);
		$mail->send();
	}

	public function setEmails(NPresenter $presenter,$subject,$message)
	{
		$config = (object)$this->context->params['mail'];

		$template = new NFileTemplate(APP_DIR.'/templates/Mail/spam.phtml');
		$template->registerFilter(new NLatteFilter());
		$message = str_replace(array_keys($this->getProductAliases($presenter)), $this->getProductAliases($presenter), $message);
		$template->message = $message;

		$result = dibi::query("SELECT id,jmeno,prijmeni,email FROM users WHERE news=1 AND lang=%s",$presenter->lang)->fetchAll();
		foreach($result as $info)
		{
			$template->link = $presenter->link('//noSpam!',array('id'=>$info->id));

			$mail = new NMail();
			$mail->setSubject($subject);
			$mail->setFrom($config->frommail,$config->fromname);
			$mail->addTo($info->email,$info->jmeno.' '.$info->prijmeni);
			$mail->setHtmlBody($template);
			$mail->send();
		}
	}

	private function getProductAliases(NPresenter $presenter)
	{
		$model = $this->getInstanceOf('KategorieModel');
		$lang = $presenter->lang;
		$ret = array();

		$result = dibi::query("SELECT P.id,P.owner,P.popis_$lang AS popis,P.jmeno_$lang AS jmeno,P.link_$lang AS link,V.cena FROM variants V JOIN products P ON V.vlastnik=P.id WHERE P.owner > 0 GROUP BY P.id")->fetchAll();
		foreach($result as $info)
		{
			$template = new NFileTemplate(APP_DIR.'/templates/Mail/item.phtml');
			$template->registerFilter(new NLatteFilter());
			$template->jmeno = $info->jmeno;
			$template->image = dibi::fetch("SELECT id FROM images WHERE vlastnik=%i ORDER BY sort,id LIMIT 1",$info->id)->id;
			$template->popis = $info->popis;
			$path =	substr($model->getPathFromId($info->owner,$lang),0,-1);
			$template->link = $presenter->link('//Frontend:detail',array('path'=>$path,'produkt'=>$info->link));
			$ret['{item'.$info->id.'}'] = $template;
		}
		return $ret;
	}

	public function sendZaplaceno($order,$cena,$castka)
	{
		$res = dibi::fetch("SELECT CONCAT(jmeno,' ',prijmeni) AS name, email FROM users WHERE id IN (SELECT id_user FROM basket WHERE id_obj=%i)",$order);
		$config = (object)$this->context->params['mail'];

		$mail = new NMail();
		$mail->setSubject('Objednávka č. '.$order.' byla zaplacena');
		$mail->setFrom($res->email,$res->name);
		$mail->addTo($config->bccmail);
		$mail->setBody('Bylo zaplaceno '.$castka.' Kč z ceny '.$cena.' Kč');
		$mail->send();
	}

	public function outOfStock($data)
	{
		$config = (object)$this->context->params['mail'];

		$template = new NFileTemplate(APP_DIR.'/templates/Mail/outofsotck.phtml');
		$template->registerFilter(new NLatteFilter());
		$template->items = $data;

		$mail = new NMail();
		$mail->setSubject('Na skladě docházejí některé položky');
		$mail->setFrom($config->bccmail);
		$mail->addTo($config->bccmail);
		$mail->setHtmlBody($template);
		$mail->send();
	}
}
