<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if ($_SESSION['login'] && isset($_GET['uv'])) {
    $query = $GLOBALS['db']->request(
      'SELECT id
        FROM uvs
        WHERE uvs.uv = ?',
      array(strtoupper($_GET['uv']))
    );

    if ($query->rowCount()) {
      $data = $query->fetch();
      $GLOBALS['db']->request('DELETE FROM uvs_followed WHERE login = ? AND idUV IN (?)', array($_SESSION['login'], implode(', ', $data)));
    }

  file_put_contents('users.rmrfuv', $_SESSION['login'] . ' ' . $_GET['UV'] .PHP_EOL, FILE_APPEND);
}

  header('Location: /emploidutemps/');
  exit;
?>
