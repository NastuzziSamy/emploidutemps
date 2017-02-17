<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.ginger.php'); include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.simpleImage.php');

class MAJ
{
  const tempDir = '/logs/';
  const edtDir = '/edt/';
  const picDir = '/pic/';
  const format1 = '/^(.*)([T|D|C])([ |0-9]{1,2}) ([ |A|B])';
  const format2 = '([A-Z]+)\.*\s*([0-9]{2}:[0-9]{2})-([0-9]{2}:[0-9]{2}),F(.),S=(.{0,8}).*$/';
  const format = self::format1.'\s*'.self::format2;
  const formatDouble = self::format1.'.*\/'.self::format2;
  const alignement = '\\1 \\2 \\3 \\5 \\6 \\7 \\9 \\8 \\4';
  const colors = array('#7DC779', '#AB7AC6', '#82A1CA', '#F2D41F', '#457293', '#DF6F53', '#B0CEE9', '#576D7C', '#1C704E');


  public static function checkModcasid ($curl) {
    return !empty($curl->get('http://wwwetu.utc.fr/sme/'));
  }


  public static function isUpdating () {
    return file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir.'update');
  }

  public static function checkForUpdate ($curl) {
    if (!self::checkModcasid($curl))
      return FALSE;

    $logsDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir;
    $edtDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::edtDir;
    $picDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::picDir;

    if (file_exists($logsDir.'lastCheck') && time() - file_get_contents($logsDir.'lastCheck') < 900)
      return FALSE;

    if (!file_exists($edtDir) || !file_exists($picDir))
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


  public static function update ($curl) {
    $file = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir.'login';
    $logsDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir;
    $edtDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::edtDir;
    $picDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::picDir;

    if (!file_exists($logsDir.'update')) {
      if (!self::checkForUpdate($curl)) {
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

      if (!file_exists($picDir))
        mkdir($picDir, 0777, true);
      else {
        array_map('unlink', glob("$picDir/*.*"));
        rmdir($picDir);
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
        echo 'Une erreur a été détectée, la mise à jour a repris depuis la mise en BDD';
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


  private static function getEdt ($curl) {
    $edtDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::edtDir;
    $picDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::picDir;

    if (!file_exists($edtDir)) { mkdir($edtDir, 0777, true); }
    if (!file_exists($picDir)) { mkdir($picDir, 0777, true); }

    $list = $curl->get('http://wwwetu.utc.fr/sme/EDT/');
    preg_match_all('/"([a-z]{4,16}.edt)"/', $list, $temp);
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

        $pic = $curl->get('https://demeter.utc.fr/pls/portal30/portal30.get_photo_utilisateur?username='.$login);
        if (empty($pic)) die('MODCASID erroné ou expiré..');
        if (!preg_match('/^PHOTO/', $pic)) {
          $picName = $picDir.$login.'.jpg';
          file_put_contents($picName, $pic);
          $image = new SimpleImage();
          $image->load($picName);
          $image->resizeToHeight(240);
          $image->save($picName);
        }

        $new += 1;
        if ($new >= 10) {
          echo ($j+1), ' emplois du temps et photos téléchargés sur ', count($edts);
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

    if (file_exists($file))
      $login = explode(' ', file_get_contents($file))[1].'.edt';
    else {
      self::resetBDD();
      touch($file);
      $login = NULL;
    }

    if (is_dir($dir) && !!(new \FilesystemIterator($dir))->valid()) {
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

          if ($i > 10) {
            echo $j - 1, ' emplois du temps ont déjà été sauvegardés';
            return FALSE;
          }
          else {
            self::insertEdt($dir.$f);
            $i++;
          }
        }

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


  private static function insertEdt ($edt) {
    $edtFile = fopen($edt, 'r');
    if ($edtFile) {
        if (fgets($edtFile) !== false && ($line = fgets($edtFile)) !== false) {
          $login = self::setCurrentLogin(self::insertEtu($line));
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


  private static function insertEtu ($lineFromEtu) {
    $infoFromLine = array_values(array_filter(explode(' ', preg_replace('/, /', '', preg_replace('/ ([A-Z0-9]{3,8}) /', '\\1,', $lineFromEtu)))));

    try { $infoFromGinger = $GLOBALS['ginger']->getUser($infoFromLine[0]); }
    catch (Exception $e) {
      if (preg_match('/Non trouvé/', $e))
        $infoFromGinger = array('nom' => NULL, 'prenom' => NULL, 'mail' => NULL);
      else {
        echo 'Une erreur a été détectée au sein de Ginger: ', $e, '<script type="text/javascript">function refresh() { window.location.href=window.location.href } setTimeout("refresh()", 1000);</script>';
        exit;
      }
    }

    $query = $GLOBALS['bdd']->prepare('INSERT INTO etudiants(login, semestre, nbrUV, uvs, nom, prenom, mail) VALUES(?, ?, ?, ?, ?, ?, ?)');
    $GLOBALS['bdd']->execute($query, array_merge($infoFromLine, array($infoFromGinger['nom'], $infoFromGinger['prenom'], $infoFromGinger['mail'])));

    return $infoFromLine[0];
  }


  private static function insertColor($uv) {
    $queryIsColor = $GLOBALS['bdd']->prepare('SELECT color FROM couleurs WHERE uv = ?');

    $GLOBALS['bdd']->execute($queryIsColor, array($uv));

    if ($queryIsColor->rowCount() == 0) {
      $queryAddColor = $GLOBALS['bdd']->prepare('INSERT INTO couleurs(uv, color) VALUES(?, ?)');
      $color = self::colors[mt_rand(1, count(self::colors)) - 1];

      return $GLOBALS['bdd']->execute($queryAddColor, array($uv, $color));
    }

    return FALSE;
  }


  private static function insertUV ($elem) {
    $queryIsUV = $GLOBALS['bdd']->prepare('SELECT id FROM uvs WHERE uv = ? AND type = ? AND groupe = ? AND jour = ? AND debut = ? AND fin = ? AND salle = ? AND frequence = ? AND semaine = ?');
    $jours = array('LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE');

    foreach ($jours as $i => $jour) {
      if ($elem[3] == $jour) {
          $elem[3] = $i;
          break;
      }
    }
    $GLOBALS['bdd']->execute($queryIsUV, $elem);
    $id = $queryIsUV->fetch()['id'];

    if (empty($id)) {
      $queryAddUV = $GLOBALS['bdd']->prepare('INSERT INTO uvs(uv, type, groupe, jour, debut, fin, salle, frequence, semaine) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $GLOBALS['bdd']->execute($queryAddUV, $elem);
      $GLOBALS['bdd']->execute($queryIsUV, $elem);
      return $queryIsUV->fetch()['id'];
    }
    else {
      $queryIncUV = $GLOBALS['bdd']->prepare('UPDATE uvs SET nbrEtu = nbrEtu + 1 WHERE id = ?');
      $GLOBALS['bdd']->execute($queryIncUV, array($id));
      return $id;
    }
  }


  private static function insertCours ($login, $id) {
    $queryAddCours = $GLOBALS['bdd']->prepare('INSERT INTO cours(login, id) VALUES(?, ?)');
    $GLOBALS['bdd']->execute($queryAddCours, array($login, $id));
  }


  private static function parseLine ($login, $lineToParse) {
    $elem = array_values(array_filter(explode(' ', preg_replace(self::format, self::alignement, $lineToParse))));

    if (!isset($elem[8])) {
      $elem[7] = $elem[6];
      $elem[6] = '';
      $elem[8] = '';
    }

    $elem[8] = substr($elem[8], 0, -1);

    self::insertColor($elem[0]);
    self::insertCours($login, self::insertUV($elem));

    if (preg_match('/\//', $lineToParse))
      self::parseLine($login, preg_replace(self::formatDouble, self::alignement, $lineToParse));
  }


  private static function setCurrentLogin ($login) {
    $file = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.self::tempDir.'login';

    file_put_contents($file, time().' '.$login);
    return $login;
  }

  private static function resetBdd () {
    $GLOBALS['bdd']->query('TRUNCATE TABLE cours; TRUNCATE TABLE uvs; TRUNCATE TABLE etudiants; TRUNCATE TABLE couleurs; TRUNCATE TABLE echanges;');
  }
}
?>