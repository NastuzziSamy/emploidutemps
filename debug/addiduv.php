<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if ($_SESSION['login'] && isset($_GET['idUV'])) {
    $GLOBALS['db']->request('INSERT INTO uvs_followed(idUV, login, color, enabled, exchanged) VALUES(?, ?, null, 1, 0)', array(
      $_GET['idUV'], $_SESSION['login']
    ));
 
    file_put_contents('users.addiduv', $_SESSION['login'] . ' ' . $_GET['idUV'].PHP_EOL, FILE_APPEND);
  }

  header('Location: /emploidutemps/');
  exit;
?>
