<?php
  include($_SERVER['DOCUMENT_ROOT'].'/ressources/class/class.bdd.php');
  include($_SERVER['DOCUMENT_ROOT'].'/ressources/class/class.curl.php');
  include($_SERVER['DOCUMENT_ROOT'].'/ressources/class/class.CAS.php');
  include($_SERVER['DOCUMENT_ROOT'].'/ressources/class/class.maj.php');

  $bdd = new BDD();
  $curl = new CURL(strpos($_SERVER['HTTP_HOST'],'utc') !== false);
  $curl->setCookies('MODCASID='.(isset($_GET['MODCASID']) && is_string($_GET['MODCASID']) ? $_GET['MODCASID'] : ''));

  if (MAJ::checkModcasid($curl)) {
    if (MAJ::update($curl))
      header('Location: /');
    else
      echo '<script type="text/javascript">function refresh() { window.location.href=window.location.href } setTimeout("refresh()", 250);</script>';
  }
  else
    echo 'Veuillez entrer votre MODCASID';

  exit;
?>
