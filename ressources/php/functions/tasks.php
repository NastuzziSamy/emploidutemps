<?php
function addTask($taskType, $info, $infosArrays, $side = NULL) {
  if ($infosArrays == array())
    return;

  if (isset($_GET['mode']) && $_GET['mode'] == 'organiser' && $taskType != 'calendar')
    $taskType = 'organize';

  // Création de la tâche
  $tasks = array( // Foutre l'id en arguement dans infos et assembler les tasks en fonction du même type/login
    'type' => $taskType,
    'info' => $info,
  );

  if ($side != NULL)
    $tasks['side'] = $side;

  // Préparation de toutes les tâches sous le même type/login/side
  $tasks['data'] = array();

  // Ajout de chaque tâche
  foreach ($infosArrays as $infosArray) {
    // Conversion de minutes en heures
    $exploded = explode(':', $infosArray['begin']);
    $begin = floatval(join('.', array($exploded[0], 100/60*$exploded[1])));
    $exploded = explode(':', $infosArray['end']);
    $end = floatval(join('.', array($exploded[0], 100/60*$exploded[1])));

    // Définition de la tâche
    $task = array(
      'id' => $taskType.'-'.($taskType == 'calendar' ? $infosArray['day'] : $info).'-'.(isset($infosArray['idUV']) ? $infosArray['idUV'] : (isset($infosArray['id']) ? $infosArray['id'] : (isset($infosArray['subject']) ? $infosArray['subject'] : ''))).(isset($_GET['mode']) && $_GET['mode'] == 'semaine' && ($taskType == 'uv' || $taskType == 'uv_followed') ? '-'.$infosArray['day'] : ''),
      'subject' => NULL,
      'note' => (isset($infosArray['note']) ? $infosArray['note'] : NULL),
      'location' => NULL,
      'day' => intval($infosArray['day']),
      'duration' => $end - $begin,
      'startTime' => $begin,
      'timeText' => ($end - $begin == 24 ? 'Journée' : $infosArray['begin'].'-'.$infosArray['end']),
      'bgColor' => (isset($infosArray['color']) ? $infosArray['color'] : (isset($infosArray['uvColor']) ? $infosArray['uvColor'] : getARandomColor())),
      'visio' => $infosArray['visio']
    );

    // Ajout des infos en fonction du type de tâche
    if ($taskType == 'uv_followed' || $taskType == 'received' || $taskType == 'sent' || $taskType == 'canceled') {
      $task['subject'] = $infosArray['uv'];
      $task['location'] = $infosArray['room'];
      $task['idUV'] = intval($infosArray['idUV']);
      $task['type'] = $infosArray['type'];
      $task['group'] = intval($infosArray['group']);
      $task['frequency'] = intval($infosArray['frequency']);
      $task['nbrEtu'] = intval($infosArray['nbrEtu']);
      if ($infosArray['week'] != '') {
        $task['note'] = 'Sem. '.$infosArray['week'];
        $task['week'] = $infosArray['week'];
      }

      if ($taskType == 'received' || $taskType == 'sent' || $taskType == 'canceled')
        $task['exchange'] = $infosArray['exchange'];
    }
    elseif ($taskType == 'uv') {
      $task['subject'] = $infosArray['uv'];
      $task['location'] = $infosArray['room'];
      $task['idUV'] = intval($infosArray['id']);
      $task['type'] = $infosArray['type'];
      $task['group'] = intval($infosArray['group']);
      $task['nbrEtu'] = intval($infosArray['nbrEtu']);
      $task['note'] = intval($infosArray['nbrEtu']).' étudiants';
      $task['frequency'] = intval($infosArray['frequency']);
      if ($infosArray['week'] != '')
        $task['week'] = $infosArray['week'];
    }
    elseif ($taskType == 'organize') {
      $task['subject'] = (isset($infosArray['subject']) ? $infosArray['subject'] : $infosArray['uv']);
      $task['location'] = isset($infosArray['room']) ? $infosArray['room'] : NULL;
    }
    elseif ($taskType == 'event' || $taskType == 'meeting') {
      $task['idEvent'] = $infosArray['idEvent'];
      $task['creator'] = $infosArray['creator'];
      $task['creator_asso'] = $infosArray['creator_asso'];
      $task['type'] = $infosArray['type'];
      $task['subject'] = $infosArray['subject'];
      $task['description'] = $infosArray['description'];
      $task['location'] = $infosArray['location'];
    }
    elseif ($taskType == 'room') {
      $task['subject'] = $infosArray['subject'];
      $task['description'] = $infosArray['location'];
      if ($task['timeText'] != 'Journée')
        $task['timeText'] = $infosArray['description'];
    }
    elseif ($taskType == 'calendar') {
      $task['subject'] = $infosArray['subject'];
      $task['description'] = $infosArray['description'];
      $task['location'] = $infosArray['location'];
      $task['bgColor'] = '#FFFFFF';
    }

    array_push($tasks['data'], $task);
  }

  array_push($GLOBALS['tasks'], $tasks);
}

// Affichage des cours d'une personne sur une semaine générale
function printUVsFollowed($login, $side = NULL, $enabled = 1, $exchanged = NULL) {
  $tasks = getUVsFollowed($login, $enabled, $exchanged);

  foreach ($tasks as $key => $task) {
    if ($enabled == 0 && $exchanged == 1)
      $tasks[$key]['color'] = '#FF0000';
    elseif ($enabled == 1 && $exchanged == 1)
      $tasks[$key]['color'] = '#00FF00';
    elseif ($task['color'] == NULL)
      $tasks[$key]['color'] = $task['uvColor'];

    if (isset($task['visio'])) {
      $tasks[$key]['note'] .= 'En distanciel la semaine '.$task['visio'];
    }
  }

  addTask('uv_followed', $login, $tasks, $side);
}

// Affichage de l'edt d'une UV sur une semaine générale
function printUV($uv, $side = NULL, $type = NULL) {
  $tasks = getUV($uv, $type);

  $query = $GLOBALS['db']->request(
    'SELECT uvs_followed.color
      FROM uvs, uvs_followed
      WHERE uvs.uv = ? AND uvs_followed.login = ? AND uvs.id = uvs_followed.idUV AND (? IS NULL OR uvs.type = ?)
      LIMIT 1',
    array($uv, $_SESSION['login'], $type, $type)
  );

  if ($query->rowCount() == 1) {
    $data = $query->fetch();
    if ($data['color'] != NULL)
      for ($i = 0; $i < count($tasks); $i++) {
        $tasks[$i]['color'] = $data['color'];
      }
  }

  addTask('uv', $uv, $tasks, $side);
}

// Affichage des salles libres sur une semaine générale
function printRoomTasks($gap) {
  $tasks = getRooms($gap);

  addTask('room', $gap, $tasks);
}

// classique des demandes d'échanges reçues
function printExchangesReceived($login, $option = NULL) {
  $exchanges = array();
  $tasks = array();

  if ($option == 'available') {
    $infos = getReceivedExchanges($login, NULL, NULL, 1, 0); // Actives
    if ($infos != array())
      $exchanges = array_merge($exchanges, $infos);
  }
  elseif ($option == 'accepted') {
    $infos = getReceivedExchanges($login, NULL, NULL, NULL, 1); // Echangées et annulées
    if ($infos != array())
      $exchanges = array_merge($exchanges, $infos);
  }
  elseif ($option == 'refused') {
    $infos = getReceivedExchanges($login, NULL, NULL, 0, 0); // Refusées
    if ($infos != array())
      $exchanges = array_merge($exchanges, $infos);
  }
  else {
    $infos = getReceivedExchanges($login); // Tout type
    if ($infos != array())
      $exchanges = array_merge($exchanges, $infos);
  }

  foreach ($exchanges as $infos) {
    $task = getUVInfosFromIdUV($infos['idUV']);
    $task['idUV'] = $infos['idUV'];
    $task['note'] = 'Reçue';
    $task['exchange'] = array_merge($infos, getUVInfosFromIdUV($infos['idUV2']));

    if ($infos['exchanged'] == '1') {
      $query = $GLOBALS['db']->request(
        'SELECT students.login, students.email
      FROM students, exchanges_sent
      WHERE students.login = exchanges_sent.login AND exchanges_sent.id = ?
      LIMIT 1',
        array($infos['idSent'])
      );

      if ($query->rowCount() == 1) {
        $exchangedStudent = $query->fetch();
        $task['exchange']['with'] = $exchangedStudent;
      }
    }

    if ($infos['available'] == '1' && $infos['exchanged'] == '1') {
      if (count(getCanceledExchanges($login, NULL, $infos['idExchange'])) == 1) {
        $data = getCanceledExchanges($login, NULL, $infos['idExchange']);
        $task['exchange'] = array_merge($task['exchange'], $data[0]);
      }
      if (count(getCanceledExchanges(NULL, NULL, $infos['idExchange'], 1, $login)) == 1) {
        $data = getCanceledExchanges(NULL, NULL, $infos['idExchange'], 1, $login);
        $task['exchange'] = array_merge($task['exchange'], $data[0]);
      }
    }

    array_push($tasks, $task);
  }

  addTask('received', $login, $tasks, 2);
}

// classique des demandes d'échanges reçues
function printExchangesSent($login, $option = NULL) {
  $exchanges = array();
  $tasks = array();

  if ($option == 'available') {
    $infos = getSentExchanges($login, NULL, NULL, 1, 0); // Actives
    if ($infos != array())
      $exchanges = array_merge($exchanges, $infos);
  }
  elseif ($option == 'accepted') {
    $infos = getSentExchanges($login, NULL, NULL, NULL, 1); // Echangées et annulées
    if ($infos != array())
      $exchanges = array_merge($exchanges, $infos);
  }
  elseif ($option == 'refused') {
    $infos = getSentExchanges($login, NULL, NULL, 0, 0); // Refusées
    if ($infos != array())
      $exchanges = array_merge($exchanges, $infos);
  }
  else {
    $infos = getSentExchanges($login); // Tout type
    if ($infos != array())
      $exchanges = array_merge($exchanges, $infos);
  }

  foreach ($exchanges as $infos) {
    $task = getUVInfosFromIdUV($infos['idUV2']);
    $task['idUV'] = $infos['idUV2'];
    $task['note'] = 'Envoyée';
    $task['exchange'] = array_merge($infos, getUVInfosFromIdUV($infos['idUV2']));

    if ($infos['exchanged'] == '1') {
      $query = $GLOBALS['db']->request(
        'SELECT students.login, students.email
      FROM students, exchanges_received
      WHERE students.login = exchanges_received.login AND exchanges_received.id = ?
      LIMIT 1',
        array($infos['idReceived'])
      );

      if ($query->rowCount() == 1) {
        $exchangedStudent = $query->fetch();
        $task['exchange']['with'] = $exchangedStudent;
      }
    }

    if ($infos['available'] == '1' && $infos['exchanged'] == '1') {
      if (count(getCanceledExchanges($login, NULL, $infos['idExchange'])) == 1) {
        $data = getCanceledExchanges($login, NULL, $infos['idExchange']);
        $task['exchange'] = array_merge($task['exchange'], $data[0]);
      }
      if (count(getCanceledExchanges(NULL, NULL, $infos['idExchange'], 1, $login)) == 1) {
        $data = getCanceledExchanges(NULL, NULL, $infos['idExchange'], 1, $login);
        $task['exchange'] = array_merge($task['exchange'], $data[0]);
      }
    }

    array_push($tasks, $task);
  }

  addTask('sent', $login, $tasks, 2);
}

// classique des demandes d'échanges reçues
function printExchangesCanceled($login, $option = NULL) {
  $tasks = array();

  if ($option == 'sent' || $option == NULL)
    $exchanges = getCanceledExchanges($login);
  else
    $exchanges = getCanceledExchanges(NULL, NULL, NULL, 1, $login);

  foreach ($exchanges as $infos) {
    if (count(getSentExchanges($login, NULL, $infos['idExchange'])) == 0) {
      $task = getUVInfosFromIdUV($infos['idUV']);
      $task['idUV'] = $infos['idUV'];
      $task['exchange'] = array_merge($infos, getUVInfosFromIdUV($infos['idUV2']));
    }
    else {
      $task = getUVInfosFromIdUV($infos['idUV2']);
      $task['idUV'] = $infos['idUV2'];
      $task['exchange'] = array_merge($infos, getUVInfosFromIdUV($infos['idUV']));
    }
    $task['exchange']['exchanged'] = TRUE;
    $task['note'] = $option == 'sent' ? 'Envoyée' : 'Reçue';

    array_push($tasks, $task);
  }

  addTask('canceled', $login, $tasks, 2);
}

function printManyTasks($elements, $week) {
  $GLOBALS['active'][$_SESSION['login']] = NULL;

  foreach ($elements as $element) {
    if (isAStudent($element))
      printWeek($element, $week, array('uv_followed', 'event', 'meeting'));
    else
      printWeek($element, $week, array('uv'));

    $GLOBALS['active'][$element] = NULL;
  }

  for ($i = 0; $i < count($GLOBALS['tasks']); $i++) {
    $GLOBALS['tasks'][$i]['type'] = 'organize';
    for ($j = 0; $j < count($GLOBALS['tasks'][$i]['data']); $j++) {
      $info = $GLOBALS['tasks'][$i]['info'];
      $data = array_keys($elements, $info);
      $color = $GLOBALS['colors'][$data[0] % count($GLOBALS['colors'])];
      $GLOBALS['tasks'][$i]['data'][$j]['bgColor'] = $color;
      $GLOBALS['active'][$info] = $color;
    }
  }
}

// classique des taches demandées en fonction d'une semaine précise
function printWeek($info, $week, $taskTypes = 'uv_followed') {
  $days = $GLOBALS['days'];

  if (is_string($taskTypes))
    $taskTypes = array($taskTypes);

  foreach($taskTypes as $taskType) {
    $tasks = array();

    if ($taskType == 'calendar')
      $tasksDay = $days;
    elseif ($taskType == 'event' || $taskType == 'meeting')
      $tasksDay = array();
    elseif ($taskType == 'room')
      $tasksDay = getRooms($info);
    elseif ($taskType == 'uv')
      $tasksDay = getUV($info);
    else
      $tasksDay = getUVsFollowed($info, 1);

    foreach ($days as $i => $day) {
      if ($taskType == 'event')
        $tasksDay = getEvents(NULL, NULL, NULL, NULL, $info, 'event', $day['date']);
      elseif ($taskType == 'meeting')
        $tasksDay = getEvents(NULL, NULL, NULL, NULL, $info, 'meeting', $day['date']);

      foreach ($tasksDay as $j => $taskDay) {
        if ($taskType == 'calendar') {
          if ($taskDay['subject'] == NULL || $taskDay['subject'] == '' || $i != $j)
            continue;

          $taskDay['begin'] = '00:00';
          $taskDay['end'] = '24:00';
          $taskDay['bgColor'] = '#000000';
        }
        elseif ($taskType == 'event' || $taskType == 'meeting') {
          if ($taskDay['creator_asso'] != NULL) {
            $data = json_decode(file_get_contents('http://assos.utc.fr/asso/'.$taskDay['creator_asso'].'/json'), TRUE);
            $assoInfos = $data['asso'][0];
            $taskDay['note'] = $assoInfos['name'];
            if ($taskDay['creator'] == $_SESSION['login'])
              $taskDay['note'] .= ' - Créer par moi-même';
            else {
              $studentInfos = getStudentInfos($taskDay['creator']);
              $taskDay['note'] .= ' - Invité par '.$studentInfos['firstname'].' '.$studentInfos['surname'];
            }
          }
          elseif ($taskDay['creator'] != NULL && $taskDay['creator'] != $_SESSION['login']) {
            $studentInfos = getStudentInfos($taskDay['creator']);
            $taskDay['note'] = 'Invité par '.$studentInfos['firstname'].' '.$studentInfos['surname'];
          }

          if ($taskDay['color'] == NULL)
            $taskDay['color'] = '#FFFFFF';
        }
        elseif ($taskDay['day'] != $day['day'])
          continue;
        elseif ($taskType == 'room') {
          if (!$day['C'] && !$day['D'])
            continue;
        }
        else {
          if (!$day[$taskDay['type']] || ($day['week'] === 'A' && $taskDay['week'] === 'B') || ($day['week'] === 'B' && $taskDay['week'] === 'A'))
            continue;
        }

        if (isset($taskDay['week']) && $taskDay['week'] != NULL)
         $taskDay['note'] = 'Cette semaine '.$taskDay['week'];

        if (isset($taskDay['visio'])) {
          if ($taskDay['visio'] === $day['week']) {
            $taskDay['note'] .= 'En distanciel cette semaine ' . $taskDay['visio'];
          } else if ($_GET['mode'] !== 'semaine') {
            $taskDay['note'] .= 'En distanciel semaine ' . $taskDay['visio'];
          }
        }

        $taskDay['week'] = NULL;
        $taskDay['frequency'] = 1;
        $taskDay['day'] = $i;
        array_push($tasks, $taskDay);
      }
    }

    addTask($taskType, $info, $tasks);
  }
}
