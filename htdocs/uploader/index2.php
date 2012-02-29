<?
if($_POST["imgok"]){
  if (move_uploaded_file($_FILES['image']['tmp_name'], "../../userfiles/".$_FILES['image']['name']))
  {
    chmod("../../userfiles/".$_FILES['image']['name'],0777);
  }
  header("Location: ".$_SERVER["REQUEST_URI"],true,303);
}
?>
<html>
<head>
<title>Uploader</title>
<script type="text/javascript">

function SelectFile( fileUrl )
{
window.opener.document.avatarform.avatarcesta.value = fileUrl;
window.opener.document.avatarform.submit();
window.close() ;
}
</script>
</head>
<body>
<div style="width:100%;margin-top:0px;padding-top:5px;text-align:center;">
<form name="imageloader" enctype="multipart/form-data" method="post" action="<?echo $_SERVER["REQUEST_URI"];?>">
  <input type="file" name="image" accept="image/jpg">
  <input type="submit" name="imgok" value="Odeslat">
</form>
</div>
<ul style="list-style-type: none;">
<?
$slozka = dir("../../userfiles");
while($soubor=$slozka->read()) {
  if ($soubor=="." || $soubor=="..") continue;
  list($width, $height) = getimagesize("../../userfiles/".$soubor);
  if($width > $height)
  {
    $width = 90;
    $height = 60;
    $padd = 38;
  }else{
    $width = 60;
    $height = 90;
    $padd = 26;
  }
  ?>
  <li style="float:left;width:140px;">
  <a href="javascript:SelectFile('../../userfiles/<?echo $soubor;?>');" style="text-decoration:none;color:black;">
  <div style="background:url('../images/file_128.png');height:<?echo (128-$padd);?>;_height:128;width:128;text-align:center;padding-top:<?echo $padd;?>;border:none;">
    <img src="../../userfiles/<?echo $soubor;?>" width="<?echo $width;?>" height="<?echo $height;?>" style="border:solid 1px gray;">
  </div>
  <div style="width:128px;text-align:center;font-family:arial;font-size:12px;overflow:hidden;text-overflow:ellipsis;">
    <?echo $soubor;?>
  </div>
  </a>
  </li>
  <?
}
$slozka->close(); 
?>
</ul>
</body>
</html>
