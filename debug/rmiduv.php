<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if ($_SESSION['login'] && isset($_GET['idUV'])) {
    $GLOBALS['db']->request('DELETE FROM uvs_followed WHERE idUV = ? AND login = ?', array(
      $_GET['idUV'], $_SESSION['login']
    ));

  file_put_contents('users.rmiduv', $_SESSION['login'] . ' ' . $_GET['idUV'].PHP_EOL, FILE_APPEND);
}

  header('Location: /emploidutemps/');
  exit;
?>
