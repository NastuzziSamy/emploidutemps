<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if ($_SESSION['login'] && isset($_GET['idUV'])) {
    $GLOBALS['db']->request('DELETE FROM uvs_followed WHERE idUV = ? AND login = ?', [
      $_GET['idUV'], $_SESSION['login']
    ]);
  }

  header('Location: /emploidutemps/');
  exit;
?>
