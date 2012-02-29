<?php

/** 
 * @author Clary
 * 
 * 
 */
class RatingModel {

  public function getHlasy($id)
  {
    return dibi::fetch("SELECT COUNT(*) AS hlasy FROM hodnoceni WHERE id_prod=%i",$id)->hlasy; 
  }
  
  public function getHodnoceni($id)
  {
    return dibi::fetch("SELECT AVG(hodnoceni) AS hodnoceni FROM hodnoceni WHERE id_prod=%i",$id)->hodnoceni;   
  }
  
  public function setHodnoceni($hodnoceni,$prod,$user)
  {
    $insert = array('id_prod'=>$prod,'id_user'=>$user);
    $res = dibi::fetch("SELECT hodnoceni FROM hodnoceni WHERE %and",$insert);
    
    if(!isset($res->hodnoceni))
    {
      $insert['hodnoceni'] = $hodnoceni;
      dibi::query("INSERT INTO hodnoceni",$insert);
    }
    else dibi::query("UPDATE hodnoceni SET hodnoceni=%s WHERE %and",$hodnoceni,$insert);
  }
}
