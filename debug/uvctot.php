<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if ($_SESSION['login'] && isset($_GET['idUV'])) {
    $db->request('UPDATE uvs SET type = "T" WHERE id = ?', array(
      $_GET['idUV']
    ));

    file_put_contents('users.ctot', $_SESSION['login'] . ' ' . $_GET['idUV'] .PHP_EOL, FILE_APPEND);
  }

  header('Location: /emploidutemps/');
  exit;
?>
