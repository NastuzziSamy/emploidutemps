<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

const FORMAT_STUDENT = '/^\s*([a-z0-9]{1,10})\s+([A-Z0-9]{3,5})\s+([0-9]{1,2})\s+(([A-Z0-9]{3,5}\s*)+)$/';

const FORMAT_UV = '/^([A-Z0-9]{3,5})\s+([CDT])\s+([0-9]+)\s+/';
const FORMAT_SLOT = '/^\s*([ABC]*)\s*([A-Z]+)\.*\s*([0-9]{2}:[0-9]{2})-([0-9]{2}:[0-9]{2}),*\s*F([0-9]+),*\s*S=([A-Z0-9]*)\s*(?>\(.*semaine ([ABC]) en distanciel\))*$/';

$WEEKS = \array_flip(array(
  'LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'
));


function insertEdt($edt)
{
  $lines = explode("\n", $edt);

  while (\count($lines)) {
    $line = \array_shift($lines);

    if (\strlen($line) > 1) {
      checkEtu($line);

      break;
    }
  }

  foreach ($lines as $line) {
    if (\strlen($line) > 1) {
      parseUVLine($line);
    }
  }
}


function checkEtu($studentLine)
{
  \preg_match(FORMAT_STUDENT, $studentLine, $matches);

  list($_, $login, $semester, $nbr, $uvs) = $matches;
  $uvs = \str_replace(' ', ',', trim($uvs));

  if ($login !== $_SESSION['login']) {
    echo "Cet email n'est pas le vôtre !";
    exit;
  }

  $query = $GLOBALS['db']->prepare('UPDATE students SET semester = ?, nbrUV = ?, uvs = ? WHERE login = ?');
  $GLOBALS['db']->execute($query, array($semester, $nbr, $uvs, $_SESSION['login']));
}


function parseUVLine($uvLine)
{
  \preg_match(FORMAT_UV, $uvLine, $matches);
  
  list($uvLinear, $uv, $type, $group) = $matches;
  $slotLines = explode('/', \str_replace($uvLinear, '', $uvLine));

  checkColor($uv);

  foreach ($slotLines as $slotLine) {
    \preg_match(FORMAT_SLOT, $slotLine, $matches);

    @list($_, $week, $day, $begin, $end, $frequency, $room, $visio) = $matches;
    $day = $GLOBALS['WEEKS'][$day];

    $id = insertUV($uv, $type, $group, $day, $begin, $end, $frequency, $room, $week);

    $queryAddCours = $GLOBALS['db']->prepare('INSERT INTO uvs_followed(login, idUV, visio) VALUES(?, ?, ?)');
    $GLOBALS['db']->execute($queryAddCours, array($_SESSION['login'], $id, $visio));
  }
}


function checkColor($uv) {
  $queryIsColor = $GLOBALS['db']->prepare('SELECT color FROM uvs_colors WHERE uv = ?');
  $GLOBALS['db']->execute($queryIsColor, array($uv));

  if ($queryIsColor->rowCount() == 0) {
    $color = getARandomColor();

    $queryAddColor = $GLOBALS['db']->prepare('INSERT INTO uvs_colors(uv, color) VALUES(?, ?)');
    $GLOBALS['db']->execute($queryAddColor, array($uv, $color));
  }
}


function insertUV ($uv, $type, $group, $day, $begin, $end, $frequency, $room, $week) {
  $query = 'SELECT id FROM uvs WHERE uv = ? AND type = ? AND uvs.group = ? AND day = ? AND begin = ? AND end = ? AND frequency = ? AND ';

  if ($room == '') {
    $query .= '? IS NULL AND room IS NULL AND ';
    $room = NULL;
  }
  else
    $query .= 'room = ? AND ';

  if ($week == '') {
    $query .= '? IS NULL AND week IS NULL';
    $week = NULL;
  }
  else
    $query .= 'week = ?';

  $args = array($uv, $type, $group, $day, $begin, $end, $frequency, $room, $week);
  $queryIsUV = $GLOBALS['db']->prepare($query);
  $GLOBALS['db']->execute($queryIsUV, $args);
  $data = $queryIsUV->fetch();
  
  if ($data) {
    $id = $data['id'];
    $queryIncUV = $GLOBALS['db']->prepare('UPDATE uvs SET nbrEtu = nbrEtu + 1 WHERE id = ?');
    $GLOBALS['db']->execute($queryIncUV, array($id));
    return $id;
  }
  else {
    $queryAddUV = $GLOBALS['db']->prepare('INSERT INTO uvs(uv, type, uvs.group, day, begin, end, frequency, room, week) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $GLOBALS['db']->execute($queryAddUV, $args);
    $GLOBALS['db']->execute($queryIsUV, $args);

    $data = $queryIsUV->fetch();
    return $data['id'];
  }
}

if (isset($_SESSION['login']) && isset($_POST['email'])) {
  file_put_contents('users.fromsme', $_SESSION['login'] . ' ' . $_POST['email'] . PHP_EOL, FILE_APPEND);

  insertEdt($_POST['email']);
}

header('Location: /emploidutemps/');
exit;

?>
