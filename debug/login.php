<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/groups.php');

if ($_SESSION['login'] !== 'snastuzz') {
  echo 'Tu n\'as pas les droits';
  exit;
}

$_SESSION['admin'] = true;

// Permet de devenir quelqu'un d'autre
if ($_SESSION['admin'] && isset($_GET['login'])) {
  $login = $_GET['login'];
  $etuInfo = getStudentInfos($login);

  $_SESSION['login'] = $login;
  $_SESSION['email'] = $etuInfo['email'];
  $_SESSION['firstname'] = $etuInfo['firstname'];
  $_SESSION['surname'] = $etuInfo['surname'];
  $_SESSION['uvs'] = $etuInfo['uvs'];
  setGroups();
}

header('Location: /emploidutemps/');
exit;

?>
