<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/class/ginger.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/class/curl.php');

$curl = new CURL(strpos($_SERVER['HTTP_HOST'],'utc') !== false);

class UPDATE
{
  // DEPECRATED: On ne passe plus par le SME. 
  const tempDir = '/logs/';
  const edtDir = '/ressources/edt/';
  const format1 = '/^(.*)([T|D|C])([ |0-9]{1,2}) ([ |A|B])';
  const format2 = '([A-Z]+)\.*\s*([0-9]{2}:[0-9]{2})-([0-9]{2}:[0-9]{2}),F(.),S=(.{0,8}).*$/';
  const alignement = '\\1 \\2 \\3 \\5 \\6 \\7 \\9 \\8 \\4';

  // DEPECRATED: On ne passe plus par le SME. 
  public static function checkModcasid($curl)
  {
    $result = $curl->get('http://wwwetu.utc.fr/sme/'); // Vive le php 5
    return !empty($result);
  }

  public static function isUpdating () {
    return file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir.'update');
  }
  
  // DEPECRATED: On ne passe plus par le SME. 
  public static function checkUpdate ($curl) {
    if (!self::checkModcasid($curl))
      return FALSE;

    $logsDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir;
    $edtDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::edtDir;

    if (!file_exists($edtDir))
      return TRUE;

    $list = $curl->get('http://wwwetu.utc.fr/sme/EDT/');
    preg_match_all('/"([a-z]{4,16}.edt)"/', $list, $temp);
    $edts = $temp[0];
    $nbr = count($edts);
    if (empty($edts)) die('MODCASID erroné ou expiré..');

    if (iterator_count(new FilesystemIterator($edtDir, FilesystemIterator::SKIP_DOTS)) != $nbr)
      return TRUE;

    for ($i = 0; $i < 5; $i++) {
      $edt = str_replace('"', '', $edts[rand(0, $nbr)]);

      if (!file_exists($edtDir.$edt))
        return TRUE;

      $text = $curl->get('http://wwwetu.utc.fr/sme/EDT/'.$edt);
      if (empty($text)) die('MODCASID erroné ou expiré..');

      if (file_get_contents($edtDir.$edt) != $text) {
        return TRUE;
      }
    }

    file_put_contents($logsDir.'lastCheck', date('Y-m-d H:i:s'));
    return FALSE;
  }


  public static function tryToUpdate ($curl) {
    $file = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir.'login';
    $logsDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir;
    $edtDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::edtDir;

    if (!file_exists($logsDir.'update')) {
      if (!self::checkUpdate($curl)) {
        echo 'Aucune mise à jour disponible';
        exit;
      }

      file_put_contents($logsDir.'changelog.txt', PHP_EOL.'Mise à jour des emplois du temps: '.date('Y-m-d H:i:s'), FILE_APPEND);

      if (!file_exists($edtDir))
        mkdir($edtDir, 0777, true);
      else {
        array_map('unlink', glob("$edtDir/*.*"));
        rmdir($edtDir);
      }
      touch($logsDir.'update');
    }

    if (!self::getEdt($curl))
      return FALSE;

    if (self::insert()) {
      unlink($logsDir.'update');
      return TRUE;
    }
    else
      return FALSE;
/*
    if (!file_exists($file)) {
    }
    else {
      $updateFile = fopen($file, 'r');
      $info = explode(' ', fgets($updateFile));
      fclose($updateFile);

      if (time() - $info[0] > 2) {
        echo 'Une erreur a été détectée, la mise à jour a repris depuis la mise en db';
        if (self::insert()) {
          file_put_contents($logsDir.'changelog.txt', 'Update: '.date('Y-m-d H:i:s').PHP_EOL);
          unlink($logsDir.'update');
          return TRUE;
        }
      }
      else {
        echo 'Une mise à jour est déjà en cours';
      }
    }
*/
    return FALSE;
  }

  // DEPECRATED: On ne passe plus par le SME. 
  public static function getEdt ($curl) {
    $edtDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::edtDir;

    if (!file_exists($edtDir)) { mkdir($edtDir, 0777, true); }

    $list = $curl->get('http://wwwetu.utc.fr/sme/EDT/');
    preg_match_all('/"([a-z0-9]{4,16}.edt)"/', $list, $temp);
    $edts = $temp[0];
    if (empty($edts)) die('MODCASID erroné ou expiré..');

    $new = 0;
    foreach ($edts as $j => $edt) {
      $edt = str_replace('"', '', $edt);
      $login = str_replace('.edt', '', $edt);

      file_put_contents($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir.'update', time().' '.$login);

      if (!file_exists($edtDir.$edt)) {
        $text = $curl->get('http://wwwetu.utc.fr/sme/EDT/'.$edt);
        if (empty($text)) die('MODCASID erroné ou expiré..');
        file_put_contents($edtDir.$edt, $text);

        $new += 1;
        if ($new >= 25) {
          echo ($j+1), ' emplois du temps téléchargés sur ', count($edts);
          return FALSE;
        }
      }
    }

    if ($new != 0)
      echo 'Les '.count($edts).' emplois du temps ont tous été téléchargés';

    return TRUE;
  }


  public static function insert () {
    $dir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::edtDir;
    $file = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir.'login';

    if (file_exists($file)) {
      $data = explode(' ', file_get_contents($file));
      $login = $data[1].'.edt';
    }
    else {
      self::resetdb();
      touch($file);
      $login = NULL;
    }

//    if (is_dir($dir) && !!(new \FilesystemIterator($dir))->valid()) { Je sais plus pourquoi j'avais fait ça mais marche pas sur PHP 5 x)
    if (is_dir($dir)) {
      if ($handle = opendir($dir)) {
        readdir($handle); readdir($handle);

        $i = 1;
        $j = 0;
        while (false !== ($f = readdir($handle))) {
          $j++;

          if ($login != NULL) {
            if ($login == $f)
              $login = NULL;

            continue;
          }

          if ($i > 50) {
            echo $j - 1, ' emplois du temps ont déjà été sauvegardés';
            return FALSE;
          }
          else {
            self::insertEdt($dir.$f);
            $i++;
          }
        }

        self::insertSalles();

        unlink($file);
        closedir($handle);

        return TRUE;
      }
    }
    else {
      echo 'Les fichiers n\'ont pas été téléchargé... Mise à jour d\'insertion annulée';
      unlink($file);
      return FALSE;
    }
  }


  public static function insertEdt ($edt) {
    $edtFile = fopen($edt, 'r');
    if ($edtFile) {
        if (fgets($edtFile) !== false && ($line = fgets($edtFile)) !== false) {
	$totest = trim(str_replace(' ', '', str_replace('-', '', $line)));
	if (empty($totest)) {
		$line = fgets($edtFile);

	}
	  $etu = self::insertEtu($line);

          $login = self::setCurrentLogin($etu);
        }

        while (($line = fgets($edtFile)) !== false) {
          if (preg_match('/^ [A-Z0-9]{3,8} /', $line)) {
            self::parseLine($login, $line);
          }
        }

        fclose($edtFile);
    } else {
      echo 'Erreur d\'accès à l\'edt';
      return FALSE;
    }
  }


  public static function insertEtu ($lineFromEtu) {
    $infoFromLine = array_values(array_filter(explode(' ', preg_replace('/, /', '', preg_replace('/ ([A-Z0-9]{3,8}) /', '\\1,', $lineFromEtu)))));
    $infoFromLine[3] = substr($infoFromLine[3], 0, -1);

    try { $infoFromGinger = $GLOBALS['ginger']->getUser($infoFromLine[0]); }
    catch (Exception $e) {
      if (preg_match('/Non trouvé/', $e))
        $infoFromGinger = array('nom' => NULL, 'prenom' => NULL, 'mail' => $infoFromLine[0].'@etu.utc.fr');
      else {
        echo 'Une erreur a été détectée au sein de Ginger: ', $e;
	exit;
      }
    }

    $query = $GLOBALS['db']->prepare('INSERT INTO students(login, semester, nbrUV, uvs, surname, firstname, email) VALUES(?, ?, ?, ?, ?, ?, ?)');
    $GLOBALS['db']->execute($query, array_merge($infoFromLine, array($infoFromGinger['nom'], $infoFromGinger['prenom'], $infoFromGinger['mail'])));

    return $infoFromLine[0];
  }


  public static function insertColor($uv) {
    $queryIsColor = $GLOBALS['db']->prepare('SELECT color FROM uvs_colors WHERE uv = ?');

    $GLOBALS['db']->execute($queryIsColor, array($uv));

    if ($queryIsColor->rowCount() == 0) {
      $queryAddColor = $GLOBALS['db']->prepare('INSERT INTO uvs_colors(uv, color) VALUES(?, ?)');
      $color = getARandomColor();

      return $GLOBALS['db']->execute($queryAddColor, array($uv, $color));
    }

    return FALSE;
  }


  public static function insertUV ($elem) {
    $query = 'SELECT id FROM uvs WHERE uv = ? AND type = ? AND uvs.group = ? AND day = ? AND begin = ? AND end = ? AND ';
    $jours = array('LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE');

    foreach ($jours as $i => $jour) {
      if ($elem[3] == $jour) {
          $elem[3] = $i;
          break;
      }
    }

    if ($elem[6] == '') {
      $query .= '? IS NULL';
      $elem[6] = NULL;
    }
    else
      $query .= 'room = ?';

    $query .= ' AND frequency = ? AND ';

    if ($elem[8] == '') {
      $query .= '? IS NULL';
      $elem[8] = NULL;
    }
    else
      $query .= 'week = ?';

    $queryIsUV = $GLOBALS['db']->prepare($query);
    $GLOBALS['db']->execute($queryIsUV, $elem);
    $data = $queryIsUV->fetch();
    $id = $data['id'];

    if (empty($id)) {
      $queryAddUV = $GLOBALS['db']->prepare('INSERT INTO uvs(uv, type, uvs.group, day, begin, end, room, frequency, week) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $GLOBALS['db']->execute($queryAddUV, $elem);
      $GLOBALS['db']->execute($queryIsUV, $elem);

      $data = $queryIsUV->fetch();
      return $data['id'];
    }
    else {
      $queryIncUV = $GLOBALS['db']->prepare('UPDATE uvs SET nbrEtu = nbrEtu + 1 WHERE id = ?');
      $GLOBALS['db']->execute($queryIncUV, array($id));
      return $id;
    }
  }


  public static function insertCours ($login, $id) {
    $queryAddCours = $GLOBALS['db']->prepare('INSERT INTO uvs_followed(login, idUV) VALUES(?, ?)');
    $GLOBALS['db']->execute($queryAddCours, array($login, $id));
  }

  public static function insertSalle($salle, $type, $jour, $debut, $fin) {
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

  public static function insertSalles() {
    static::resetRooms();

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
            self::insertSalle($salle['room'], $salle['type'], $jour, $debutDispo, $info['begin']);
            $debutDispo = $info['end'];
          }

          self::insertSalle($salle['room'], $salle['type'], $jour, $infos[count($infos) - 1]['end'], $finDispo);
        }
      }
    }
  }

  // DEPECRATED: On ne passe plus par le SME. 
  public static function parseLine ($login, $lineToParse) {
    $elem = array_values(array_filter(explode(' ', preg_replace(self::format1.'\s*'.self::format2, self::alignement, $lineToParse))));

    if (!isset($elem[8])) {
      $elem[7] = $elem[6];
      $elem[6] = '';
      $elem[8] = '';
    }

    $elem[8] = substr($elem[8], 0, -1);

    self::insertColor($elem[0]);
    self::insertCours($login, self::insertUV($elem));

    if (preg_match('/\//', $lineToParse))
      self::parseLine($login, preg_replace(self::format1.'.*\/'.self::format2, self::alignement, $lineToParse));
  }

  // DEPECRATED: On ne passe plus par le SME. 
  public static function setCurrentLogin ($login) {
    $file = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir.'login';

    file_put_contents($file, time().' '.$login);
    return $login;
  }

  public static function resetdb()
  {
    $GLOBALS['db']->request('TRUNCATE TABLE events;
      TRUNCATE TABLE events_followed;
      TRUNCATE TABLE exchanges;
      TRUNCATE TABLE exchanges_canceled;
      TRUNCATE TABLE exchanges_received;
      TRUNCATE TABLE exchanges_sent;
      TRUNCATE TABLE exchanges_set;
      TRUNCATE TABLE students;
      TRUNCATE TABLE students_groups;
      TRUNCATE TABLE students_groups_subs;
      TRUNCATE TABLE students_groups_elements;
      TRUNCATE TABLE uvs;
      TRUNCATE TABLE uvs_followed;
      TRUNCATE TABLE uvs_colors;
      TRUNCATE TABLE uvs_rooms;', array());
  }

  public static function resetRooms()
  {
    $GLOBALS['db']->request('TRUNCATE TABLE uvs_rooms;', array());
  }
}
?>
