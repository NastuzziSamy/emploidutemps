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

$colors = array('#7DC779', '#82A1CA', '#F2D41F', '#457293', '#AB7AC6', '#DF6F53', '#B0CEE9', '#AAAAAA', '#1C704E');
function getARandomColor() {
  return $GLOBALS['colors'][mt_rand(1, count($GLOBALS['colors'])) - 1];
}

$personne = include('off.php');
$days = ['LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'];
$nbr = count($personne);

foreach ($personne as $key => $elem) {
  $login = $elem['login'];

  try {
    $uvs = json_decode(file_get_contents("https://webapplis.utc.fr/Edt_ent_rest/myedt/result/?login=$login"));

    $s = array_unique(array_map(function ($uv) {
      return $uv->uv;
    }, $uvs));

    $c = count($s);
    echo "Get $key/$nbr: $login with $c uvs\n";
  } catch (Exception $e) {
    echo "Error for $login\n";
  }

  if (count($uvs)) {
    $ginger = json_decode(file_get_contents("3aGfOI8QewcmQNmn9jiNZdJyAETJ3vuCfKlpw/$login?key=$KEY"));


    $GLOBALS['db']->request('INSERT INTO students(login, surname, firstname, email, semester, uvs, nbrUV) VALUES(?, ?, ?, ?, "N/A", ?, ?)', [
    $login, $ginger->nom, $ginger->prenom, $ginger->mail, implode(', ', $s), count($s)
    ]);
  }

  foreach ($uvs as $uv) {
    $name = $uv->uv;

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

    $r = $GLOBALS['db']->request('SELECT * FROM uvs WHERE uv = ? AND type = ? AND uvs.group = ?', [
      $name, $type, $group,
    ]);

    if ($r->rowCount() === 0) {
      $GLOBALS['db']->request('INSERT INTO uvs(uv, type, group, day, begin, end, room, frequency, week) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $name, $type, $group, $day, $uv->begin, $uv->end, $room, $frequency, $week
      ]);

      $r = $GLOBALS['db']->request('SELECT * FROM uvs WHERE uv = ? AND type = ? AND uvs.group = ?', [
        $name, $type, $group,
      ]);

      $id = $r->fetch()['id'];

      $r = $GLOBALS['db']->request('SELECT * FROM uvs_colors WHERE uv = ?', [
        $name
      ]);

      if ($r->rowCount() === 0) {
        $GLOBALS['db']->request('INSERT INTO uvs_colors(uv, color) VALUES(?, ?)', [
          $name, getARandomColor()
        ]);
      }
    } else {
      $fetch = $r->fetch();
      $id = $fetch['id'];

      $GLOBALS['db']->request('UPDATE uvs SET nbrEtu = ? WHERE id = ?', [$fetch['nbrEtu'] + 1, $id]);
    }

    $GLOBALS['db']->request('INSERT INTO uvs_followed(idUV, login, color, enabled, exchanged) VALUES(?, ?, null, 1, 0)', [
      $id, $login
    ]);
  }
}

echo "Inserting rooms\n";
insertSalles();
