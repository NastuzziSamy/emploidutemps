<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  if (!$_SESSION['admin']) {
    echo 'Tu n\'as pas le droit d\'accéder à cette page.';
    exit;
  }

  if (isset($_GET['noWeek']) && $_GET['noWeek']) {
    $_GET['day'] = NULL;
    $_GET['week'] = NULL;
    $_GET['number'] = NULL;
  }

  if (!isset($_GET['isEvent']) || !$_GET['isEvent']) {
    $_GET['subject'] = NULL;
    $_GET['description'] = NULL;
    $_GET['location'] = NULL;
  }

  if (isset($_GET['end'])) {
    $date = new DateTime($_GET['end']);
    $date->modify('+1 day');

    if (isset($_GET['day']) && $_GET['day'] == '5') {
      $date->modify('+1 day');
    }

    $day = $date->format('Y-m-d');
  }
?>

<form method='get'>
  <input name='begin' type='date' <?php echo isset($_GET['begin']) ? 'value="'.$day.'"' : ''; ?> placeholder="Début: YYYY-MM-DD" />
  <input name='end' type='date' <?php echo isset($_GET['end']) ? 'value="'.$day.'"' : ''; ?> placeholder="Fin: YYYY-MM-DD" />
  <br /><br />
  Y a-t-il ?
  <input type='checkbox' name='C' id="C" CHECKED/><label for="C"> Cours</label>
  <input type='checkbox' name='D' id="D" CHECKED/><label for="D"> TD</label>
  <input type='checkbox' name='T' id="T" CHECKED/><label for="T"> TP</label>
  <br /><br />
  <input type='checkbox' name='noWeek'/> Pas de semaine définie (inutile de remplir si pas défini)
  <br />
  <input name='day' <?php echo isset($_GET['day']) ? 'value="'.(($_GET['day'] + 1) == 6 ? 0 : ($_GET['day'] + 1)).'"' : ''; ?> placeholder="jour de la semaine: 0-6" />
  <input name='week' <?php echo isset($_GET['week']) ? 'value="'.$_GET['week'].'"' : ''; ?> placeholder="Semaine A ou B" />
  <input name='number' <?php echo isset($_GET['number']) ? 'value="'.$_GET['number'].'"' : ''; ?> placeholder="Semaine numéro" />
  <br /><br />
  <input type='checkbox' name='isEvent'/> Evènement (cocher et remplir que si défini)
  <br />
  <input name='subject' <?php echo isset($_GET['subject']) ? 'value="'.$_GET['subject'].'"' : ''; ?> placeholder="Sujet" />
  <input name='description' <?php echo isset($_GET['description']) ? 'value="'.$_GET['description'].'"' : ''; ?> placeholder="Description" />
  <input name='location' <?php echo isset($_GET['location']) ? 'value="'.$_GET['location'].'"' : ''; ?> placeholder="Localisation" />
  <br /><br />
  <input type='submit' />
</form>

<?php
  if (isset($_GET['begin']) && isset($_GET['end'])) {
    $query = $db->request(
      'SELECT begin FROM uvs_days WHERE begin = ?',
      array($_GET['begin'])
    );

    if ($query->rowCount() != 0) {
      echo 'Erreur: Jour déjà présent';
      exit;
    }

    $c = (int) (isset($_GET['C']) && $_GET['C']);
    $d = (int) (isset($_GET['D']) && $_GET['D']);
    $t = (int) (isset($_GET['T']) && $_GET['T']);

    if (empty($_GET['subject']))
      $_GET['subject'] = NULL;
    if (empty($_GET['description']))
      $_GET['description'] = NULL;
    if (empty($_GET['location']))
      $_GET['location'] = NULL;

    $db->request(
      'INSERT INTO uvs_days(begin, end, day, week, number, C, D, T, subject, description, location) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
      array($_GET['begin'], $_GET['end'], $_GET['day'], $_GET['week'], $_GET['number'], $c, $d, $t, $_GET['subject'], $_GET['description'], $_GET['location'])
    );

    echo 'Info: Jour ajouté avec succès';
  }
?>
