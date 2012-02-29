<?php
class Authenticator extends BaseModel implements IAuthenticator
{

    public function authenticate(array $credentials)
    {
        $username = $credentials[self::USERNAME];
        $password = md5($credentials[self::PASSWORD]);

        // přečteme záznam o uživateli z databáze
        if(isset($credentials[2]) && $credentials[2])$row = dibi::fetch("SELECT *,CONCAT(jmeno,' ',prijmeni) AS realname FROM users WHERE logincookie=%s AND registrovan > 0", $credentials[2]);
        else{
        	$row = dibi::fetch("SELECT *,CONCAT(jmeno,' ',prijmeni) AS realname FROM users WHERE login=%s AND registrovan > 0", $username);

        	if (!$row) { // uživatel nenalezen?
            throw new NAuthenticationException("Uživatel '$username' nebyl nalezen.", self::IDENTITY_NOT_FOUND);
        	}

       	 	if ($row->heslo !== $password) { // hesla se neshodují?
            throw new NAuthenticationException("Špatné heslo.", self::INVALID_CREDENTIAL);
        	}
        }
 
				dibi::query("UPDATE users SET lastlogin=%i WHERE id=%i",time(),$row->id);
				return new NIdentity($row->realname,$row->registrovan,$row); // vrátíme identitu
    }

}