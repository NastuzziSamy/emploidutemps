<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');


function printEvent($startDay, $startHour, $endDay, $endHour, $summary, $description, $location, $alarm) {
  if ($startHour != NULL)
    $start = 'TZID="Europe/Paris":'.$startDay.'T'.$startHour;
  else
    $start = 'VALUE=DATE:'.$startDay;

  if ($endHour != NULL)
    $end = 'TZID="Europe/Paris":'.$endDay.'T'.$endHour;
  else
    $end = 'VALUE=DATE:'.$endDay;

  echo '
BEGIN:VEVENT
DTSTAMP:'.$GLOBALS['date'].'
UID:'.$GLOBALS['date'].'-'.$GLOBALS['id']++.'-'.$_SESSION['login'].'@assos.utc.fr
DTSTART;'.$start.'
DTEND;'.$end.'
SUMMARY:'.$summary.($description != NULL ? '
DESCRIPTION:'.$description.'' : '').($location != NULL ? '
LOCATION:'.$location.'' : '').($alarm != 0 ? '
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:'.$summary.($description != NULL ? ' - '.$description : '').'
TRIGGER:-P0DT'.floor($alarm / 60).'H'.($alarm % 60).'M0S
END:VALARM' : '').'
END:VEVENT';
}

  function startExport($name) {
    header("Content-type: text/calendar; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"".preg_replace("/[^A-Za-z0-9\_\-\.]/", '', $name).".ics\"");

    echo 'BEGIN:VCALENDAR
PRODID:Emploi d\'UTemps - SIMDE/UTC
VERSION:2.0
CALSCALE:GREGORIAN
X-WR-CALNAME:'.$_SESSION['email'].'
X-WR-TIMEZONE:Europe/Paris
BEGIN:VTIMEZONE
TZID:Europe/Paris
X-LIC-LOCATION:Europe/Paris
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE';
  }

$date = date('Ymd').'T'.date('His').'Z';
$id = 0;

if (isGetSet(array('mode', 'idEventFollowed')) && $_GET['mode'] == 'event') {
  $data = getEvents($_GET['idEventFollowed']);
  $event = $data[0];
  startExport($event['subject']);
  printEvent(str_replace('-', '', $event['date']), str_replace(':', '', $event['begin']).'00', str_replace('-', '', $event['date']), str_replace(':', '', $event['end']).'00', $event['subject'], $event['description'], $event['location'], 0);
}
elseif (isGetSet(array('mode')) && $_GET['mode'] == 'all') {
  $alarm = (isset($_GET['alarm']) && $_GET['alarm'] > 0 ? $_GET['alarm'] : 0); // 0 désactive l'alarme
  $begin = (isset($_GET['begin']) ? $_GET['begin'] : '0001-01-01');
  $end = (isset($_GET['end']) ? $_GET['end'] : '9999-12-31');

  $beginDate = new DateTime($begin);
  $endDate = new DateTime($end);
  if ($beginDate > $endDate) {
    header('Content-Type: application/json');
    returnJSON(array('error' => 'Impossible d\'exporter avec une date de fin finissant avant la date de début d\'export'));
  }

  startExport('Emploi du temps - '.$_SESSION['firstname'].' '.$_SESSION['surname']);
  // On organise nos UVs en fonction des jours de la semaine
  $tasks = array(
    getUVsFollowed($_SESSION['login'], 1, NULL, 0),
    getUVsFollowed($_SESSION['login'], 1, NULL, 1),
    getUVsFollowed($_SESSION['login'], 1, NULL, 2),
    getUVsFollowed($_SESSION['login'], 1, NULL, 3),
    getUVsFollowed($_SESSION['login'], 1, NULL, 4),
    getUVsFollowed($_SESSION['login'], 1, NULL, 5),
    getUVsFollowed($_SESSION['login'], 1, NULL, 6)  // Inutile mais bon haha au cas où
  );

  $infos = array(
    'subject' => NULL,
    'description' => NULL,
    'location' => NULL,
    'begin' => NULL,
    'end' => NULL
  );

  $query = $GLOBALS['db']->request(
    'SELECT * FROM uvs_days WHERE begin BETWEEN ? AND ? ORDER BY begin, end',
    array($begin, $end)
  );

  $days = $query->fetchAll();

  foreach ($days as $day) {
    $startDay = str_replace('-', '', $day['begin']);
    $endDay = str_replace('-', '', $day['end']);

    if ($day['C'] || $day['D'] || $day['T']) {
      foreach ($tasks[$day['day']] as $task) {
        if (!$day[$task['type']]) // On vérifie si on a cours/TD/TP ce jour
          continue;

        if ($day['week'] != NULL && $day['week'] != '' && $task['week'] != NULL && $task['week'] != '' && $task['frequency'] == 2 && $day['week'] != $task['week'])
          continue;

        $beginHour = str_replace(':', '', $task['begin']).'00';
        $endHour = str_replace(':', '', $task['end']).'00';
        $description = ($day['subject'] != NULL ? $day['subject'] : NULL);

        if ($task['type'] == 'C')
          $summary = 'Cours de '.$task['uv'];
        elseif ($task['type'] == 'D')
          $summary = 'TD de '.$task['uv'];
        elseif ($task['type'] == 'T') {
          $summary = 'TP de '.$task['uv'];
          if (($task['frequency'] == 2 && $task['week'] == NULL) || $task['frequency'] == 3) {
            $summary .= ' (semaine '.$day['week'].$day['number'].')';
            $description = ($description != NULL ? $description.'. ' : '').'TP à réaliser uniquement 1 fois toutes les '.$task['frequency'].' semaines. Consulter le planning de TPs '.$task['uv'].' pour aller aux bons TPs';
          }
          else {
            $description = ($description != NULL ? $description.'. ' : '').'Cette semaine '.$task['week'];
          }
        }
        else
          continue;

        if (isset($task['visio']) && $task['visio'] === $day['week']) {
          $summary .= ' (distanciel)';
          $description = ($description != NULL ? $description . '. ' : '') . 'A réaliser en distanciel';
        }

        printEvent($startDay, $beginHour, $endDay, $endHour, $summary, $description, $task['room'], $alarm);
      }
    }

    if ($day['subject'] != NULL) { // On ajoute les évènements en décallé. En fait, on est obligé de faire comme ça car certains évènements sont répétés plusieurs jours dans la BDD (et faut éviter ça à l'export)
      if ($infos['subject'] == $day['subject']) {
        $infos['end'] = $day['end'];
      }
      else {
        $temp = new DateTime($infos['end']);
        $temp->modify('+1 day');

        if ($infos['subject'] != NULL)
          printEvent($infos['begin'], NULL, $temp->format('Ymd'), NULL, $infos['subject'], $infos['description'], $infos['location'], 0);

        $infos = array(
          'subject' => $day['subject'],
          'description' => $day['description'],
          'location' => $day['location'],
          'begin' => $startDay,
          'end' => $day['end']
        );
      }
    }
  }

  $temp = new DateTime($infos['end']);
  $temp->modify('+1 day');
  printEvent($infos['begin'], NULL, $temp->format('Ymd'), NULL, $infos['subject'], $infos['description'], $infos['location'], 0);
}
else {
  header('Content-Type: application/json');
  returnJSON(array('error' => 'Pas de choix d\'export'));
}

echo '
END:VCALENDAR';
?>
