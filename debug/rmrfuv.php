<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if ($_SESSION['login'] && isset($_GET['uv'])) {
    $query = $GLOBALS['db']->request(
      'SELECT uvs_followed.id
        FROM uvs, uvs_followed
        WHERE uvs.uv = ? AND uvs_followed.login = ? AND uvs.id = uvs_followed.idUV',
      array(strtoupper($_GET['uv']), $_SESSION['login'])
    );

    if ($query->rowCount()) {
      $data = $query->fetch();
      $GLOBALS['db']->request('DELETE FROM uvs_followed WHERE id IN (?)', array(implode(', ', $data)));
    }
  }

  header('Location: /emploidutemps/');
  exit;
?>
