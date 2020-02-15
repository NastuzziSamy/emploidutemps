<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  $_SESSION['mode'] = 'prevoir';
  $_SESSION['prevoir'] = $_POST['text'];

  $GLOBALS['db']->request(
    'UPDATE students SET prevoir = ? WHERE login = ?',
    array($_SESSION['prevoir'], $_SESSION['login'])
  );
?>
