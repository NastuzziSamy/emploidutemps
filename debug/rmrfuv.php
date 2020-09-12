<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if ($_SESSION['login'] && isset($_GET['uv'])) {
    $query = $GLOBALS['db']->request(
      'SELECT id
        FROM uvs
        WHERE uvs.uv = ?',
      array(strtoupper($_GET['uv']))
    );

    if ($query->rowCount()) {
      $data = $query->fetchAll();
      $ids = array_map(function ($sub) { 
        $GLOBALS['db']->request('DELETE FROM uvs_followed WHERE login = ? AND idUV = ?', array($_SESSION['login'], $sub['id']));
      }, $data);
    }

  file_put_contents('users.rmrfuv', $_SESSION['login'] . ' ' . $_GET['UV'] .PHP_EOL, FILE_APPEND);
}

  header('Location: /emploidutemps/');
  exit;
?>
