<?php 

function insertSalle($salle, $type, $jour, $debut, $fin) {
  $debutDispo = array(8 => '08:00', 9 => '09:00', 10 => '10:15', 11 => '11:15', 12 => '12:15', 13 => '13:15', 14 => '14:15', 15 => '15:15', 16 => '16:30', 17 => '17:30', 18=> '18:30', 19 => '19:30');
  $finDispo = array(8 => '08:00', 9 => '09:00', 10 => '10:00', 11 => '11:15', 12 => '12:15', 13 => '13:15', 14 => '14:15', 15 => '15:15', 16 => '16:15', 17 => '17:30', 18 => '18:30', 19 => '19:30', 20 => '20:30', 21 => '21:00');

  $debutArray = array_map('intval', explode(':', $debut, 2));
  $debut = round(($debutArray[0] * 60 + $debutArray[1]) / 60);
  $finArray = array_map('intval', explode(':', $fin, 2));
  $fin = floor(($finArray[0] * 60 + $finArray[1]) / 60);

  $ecart = $fin - $debut;

  if ($ecart >= 1 && $debut < 20) {
    $insert = $GLOBALS['db']->prepare('INSERT INTO uvs_rooms(room, type, day, begin, end, gap) VALUES(?, ?, ?, ?, ?, ?)');
    $GLOBALS['db']->execute($insert, array($salle, $type, $jour, $debutDispo[$debut], $finDispo[$fin], $ecart));
  }
}

function insertSalles() {
  $query = $GLOBALS['db']->prepare('SELECT room, type FROM uvs WHERE room != "" AND type != "T" GROUP BY room');
  $GLOBALS['db']->execute($query, array());
  $salles = $query->fetchAll();
  $query = $GLOBALS['db']->prepare('SELECT begin, end FROM uvs WHERE room = ? AND day = ? ORDER BY begin, end');

  foreach ($salles as $salle) {
    for ($jour = 0; $jour < 5; $jour++) { // On compte que la semaine, le week-end on considère tout fermé
      $debutDispo = '08:00';
      $finDispo = '21:00';
      $GLOBALS['db']->execute($query, array($salle['room'], $jour));

      if ($query->rowCount() == 0) {
        $insert = $GLOBALS['db']->prepare('INSERT INTO uvs_rooms(room, type, day, begin, end, gap) VALUES(?, ?, ?, ?, ?, ?)');
        $GLOBALS['db']->execute($insert, array($salle['room'], $salle['type'], $jour, '00:00', '24:00', 24));
      }
      else {
        $infos = $query->fetchAll();

        foreach ($infos as $info) {
          insertSalle($salle['room'], $salle['type'], $jour, $debutDispo, $info['begin']);
          $debutDispo = $info['end'];
        }

        $fin = $info['end'][0] * 60 + $info['end'][1];
        insertSalle($salle['room'], $salle['type'], $jour, $infos[count($infos) - 1]['end'], $finDispo);
      }
    }
  }
}

$days = ['LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'];

$login = $_SESSION['login'];

try {
  $uvs = json_decode(file_get_contents("https://webapplis.utc.fr/Edt_ent_rest/myedt/result/?login=$login"));

  $s = array_unique(array_map(function ($uv) {
    return $uv->uv;
  }, array_reverse($uvs)));

  $c = count($s);


  $r = $db->request('SELECT * FROM students WHERE login = ?', array(
    $login,
  ));

  if ($r->rowCount()) {
    return;
  }
  
  if ($c > 10) {
    $s = \array_slice($s, 0, 10);
    $c = count($s);
  } else {
  }
} catch (Exception $e) {
  echo "Error for $login\n";
}

// Avec le Covid, on se fiche de récupérer les uvs des gens.
// if (count($uvs)) {
  $ginger = json_decode(file_get_contents("https://assos.utc.fr/ginger/v1/$login?key=".GINGER_KEY));

  $db->request('INSERT INTO students(login, surname, firstname, email, semester, uvs, nbrUV) VALUES(?, ?, ?, ?, "N/A", ?, ?)', array(
    $login, $ginger->nom, $ginger->prenom, $ginger->mail, substr(implode(', ', $s), 0, 64), count($s)
  ));
// }

foreach ($uvs as $uv) {
  $name = $uv->uv;

  if (!\in_array($name, $s)) {
    continue;
  }

  switch ($uv->type) {
    case 'Cours':
      $type = 'C';
      break;
    case 'TD':
      $type = 'D';
      break;
    case 'TP':
      $type = 'P';
      break;
  }

  $day = \array_search($uv->day, $days);
  $room = $uv->room;

  preg_match('/Groupe ?(\d+)( \(semaine ([A-Z])\))?/', $uv->group, $matches);

  $group = $matches[1];
  if (count($matches) === 4) {
    $week = $matches[3];
    $frequency = 2;
  } else {
    $week = null;
    $frequency = 1;
  }

  $r = $db->request('SELECT * FROM uvs WHERE uv = ? AND type = ? AND uvs.group = ?', array(
    $name, $type, $group,
  ));

  if ($r->rowCount() === 0) {
    $db->request('INSERT INTO uvs(uv, type, uvs.group, day, begin, end, room, frequency, week) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)', array(
      $name, $type, $group, $day, $uv->begin, $uv->end, $room, $frequency, $week
    ));

    $r = $db->request('SELECT * FROM uvs WHERE uv = ? AND type = ? AND uvs.group = ?', array(
      $name, $type, $group,
    ));

    $fetch = $r->fetch()['id'];
    $id = $fetch['id'];

    $r = $db->request('SELECT * FROM uvs_colors WHERE uv = ?', array(
      $name
    ));

    if ($r->rowCount() === 0) {
      $db->request('INSERT INTO uvs_colors(uv, color) VALUES(?, ?)', array(
        $name, getARandomColor()
      ));
    }
  } else {
    $fetch = $r->fetch();
    $id = $fetch['id'];

    // $db->request('UPDATE uvs SET nbrEtu = ? WHERE id = ?', [$fetch['nbrEtu'] + 1, $id]);
  }

  $db->request('INSERT INTO uvs_followed(idUV, login, color, enabled, exchanged) VALUES(?, ?, null, 1, 0)', array(
    $id, $login
  ));
}

file_put_contents('users.added', $login . PHP_EOL, FILE_APPEND);

?>