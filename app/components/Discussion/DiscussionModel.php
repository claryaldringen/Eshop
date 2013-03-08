<?php

class DiscussionModel extends BaseModel{

	public function getMessages($product,$lang)
	{
		return dibi::query("SELECT D.id,datum,text,login AS user FROM diskuze D JOIN users U ON U.id=D.id_user WHERE id_prod=%i AND D.lang=%s ORDER BY D.id",$product,$lang)->fetchAll();
	}

	public function setMessage(NPresenter $presenter,$text,$prod,$mail,$lang)
	{
		$user = $presenter->user;
	  $sledovat = array('ne','ano');
	  $data = $user->getIdentity()->getData();
		dibi::query("INSERT INTO diskuze",array('id_user'=>$data['id'],'id_prod'=>$prod,'text'=>$text,'sledovat'=>$sledovat[$mail],'ip'=>$_SERVER['REMOTE_ADDR'],'lang'=>$lang));
	  $this->sendEmailNotice($presenter,$prod,$data['id'],$lang);
	}

	public function deleteMessage($id)
	{
		dibi::query("DELETE FROM diskuze WHERE id=%i",$id);
	}

	private function sendEmailNotice(NPresenter $presenter,$id,$user,$lang)
	{
		$mailsett = $this->context->params['mail'];
		$admins = explode(',',$mailsett->adminmails);
		if(!is_array($admins))$admins = array($mailsett->adminmails);

		$jmeno = dibi::fetch("SELECT jmeno_$lang AS jmeno,owner FROM products WHERE id=%i",$id)->jmeno;
		$result = dibi::query("SELECT jmeno,prijmeni,email FROM users WHERE id IN (SELECT id_user FROM diskuze WHERE id_user!=%i AND sledovat='ano' AND id_prod=%i AND lang=%s GROUP BY id_user)",$user,$id,$lang)->fetchAll();
		if(count($result))
		{
	    $mail = new NMail();
	    foreach($result as $info)
	    {
	      $mail->addTo($info->email,$info->jmeno.' '.$info->prijmeni);
	    }
	    foreach($admins as $admin)
	    {
	      $mail->addBcc($admin);
	    }

	    $template = new NFileTemplate();
		  $template->setFile(dirname(__FILE__).'/email.phtml');
		  $template->registerFilter(new NLatteFilter());
		  $template->link = $presenter->link('//this');

		  $mail->setFrom($mailsett->frommail,$mailsett->fromname);
		  $mail->setSubject($mailsett->diskuze.' '.$jmeno);
		  $mail->setHtmlBody($template);
		  $mail->send();
		}
	}
}
