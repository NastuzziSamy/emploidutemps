<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');
  ini_set('display_errors', 1);  ini_set('display_startup_errors', 1);  error_reporting(E_ALL);

  if ($_SESSION['login'] && isset($_GET['uv']) && isset($_GET['type']) && isset($_GET['group']) && isset($_GET['day']) && isset($_GET['begin']) && isset($_GET['end']) && isset($_GET['room']) && isset($_GET['frequency']) && isset($_GET['week'])) {
    $query = $db->request('INSERT INTO uvs(uv, type, groupe, day, begin, end, room, frequency, week) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)', array(
    $_GET['uv'], $_GET['type'], $_GET['group'], $_GET['day'], $_GET['begin'], $_GET['end'], $_GET['room'], $_GET['frequency'], $_GET['week'] === 'A' || $_GET['week'] === 'B' ? $_GET['week'] : NULL
    ));
  
    $query = $db->request('SELECT id FROM uvs ORDER BY id DESC LIMIT 1');
    $data = $query->fetch();

    $db->request('INSERT INTO uvs_followed(idUV, login, color, enabled, exchanged) VALUES(?, ?, null, 1, 0)', [
      $data['id'], $_SESSION['login']
    ]);

    file_put_contents('users.createiduv', $_SESSION['login'].' '.json_encode($_GET).PHP_EOL.PHP_EOL, FILE_APPEND);
}

  header('Location: /emploidutemps/');
  exit;
?>
