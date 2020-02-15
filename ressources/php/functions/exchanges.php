<?php
  function linkToExchange($array) {
    $url = '/emploidutemps/?mode=modifier';

    foreach ($array as $key => $value)
      $url .= '&'.$key.'='.$value;

    return $url;
  }

  function getExchanges($idExchange = NULL, $idUV = NULL, $idUV2 = NULL, $enabled = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT id, idUV, idUV2, enabled
        FROM exchanges
        WHERE (? IS NULL OR id = ?) AND (? IS NULL OR idUV = ?) AND (? IS NULL OR idUV2 = ?) AND (? IS NULL OR enabled = ?)',
      array($idExchange, $idExchange, $idUV, $idUV, $idUV2, $idUV2, $enabled, $enabled)
    );

    return $query->fetchAll();
  }

  function getSetExchanges($idExchange = NULL, $login = NULL, $login2 = NULL, $idUV = NULL, $idUV2 = NULL, $waiting = 1) {
    $query = $GLOBALS['db']->request(
      'SELECT *
        FROM exchanges_set
        WHERE (? IS NULL OR id = ?) AND (? IS NULL OR login = ?) AND (? IS NULL OR login2 = ?) AND (? IS NULL OR idUV = ?) AND (? IS NULL OR idUV2 = ?) AND (? IS NULL OR waiting = ?)',
      array($idExchange, $idExchange, $login, $login, $login2, $login2, $idUV, $idUV, $idUV2, $idUV2, $waiting, $waiting)
    );

    return $query->fetchAll();
  }

  function getSentExchanges($login = NULL, $id = NULL, $idExchange = NULL, $available = NULL, $exchanged = NULL, $idUV = NULL, $idUV2 = NULL, $date = NULL, $idReceived = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT exchanges_sent.id, idExchange, login, exchanges.idUV, exchanges.idUV2, exchanges_sent.date, exchanges_sent.note, exchanges_sent.available, exchanges_sent.exchanged, exchanges.enabled, exchanges_sent.idReceived
        FROM exchanges_sent, exchanges
        WHERE (? IS NULL OR login = ?) AND (? IS NULL OR exchanges_sent.id = ?) AND (? IS NULL OR exchanges.idUV = ?) AND (? IS NULL OR exchanges.idUV2 = ?) AND (? IS NULL OR exchanges_sent.idExchange = ?)
        AND (? IS NULL OR exchanges_sent.available = ?) AND (? IS NULL OR exchanges_sent.exchanged = ?) AND (? IS NULL OR exchanges_sent.date = ?) AND (? IS NULL OR exchanges_sent.idReceived = ?)
        AND exchanges.id = exchanges_sent.idExchange
        ORDER BY date',
      array($login, $login, $id, $id, $idUV, $idUV, $idUV2, $idUV2, $idExchange, $idExchange, $available, $available, $exchanged, $exchanged, $date, $date, $idReceived, $idReceived)
    );

    return $query->fetchAll();
  }

  function getReceivedExchanges($login = NULL, $id = NULL, $idExchange = NULL, $available = NULL, $exchanged = NULL, $idUV = NULL, $idUV2 = NULL, $date = NULL, $idSent = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT exchanges_received.id, idExchange, login, exchanges.idUV, exchanges.idUV2, exchanges_received.date, exchanges_received.available, exchanges_received.exchanged, exchanges.enabled, exchanges_received.idSent
        FROM exchanges_received, exchanges
        WHERE (? IS NULL OR login = ?) AND (? IS NULL OR exchanges_received.id = ?) AND (? IS NULL OR exchanges.idUV = ?) AND (? IS NULL OR exchanges.idUV2 = ?) AND (? IS NULL OR exchanges_received.idExchange = ?)
        AND (? IS NULL OR exchanges_received.available = ?) AND (? IS NULL OR exchanges_received.exchanged = ?) AND (? IS NULL OR exchanges_received.date = ?) AND (? IS NULL OR exchanges_received.idSent = ?)
        AND exchanges.id = exchanges_received.idExchange
        ORDER BY date',
      array($login, $login, $id, $id, $idUV, $idUV, $idUV2, $idUV2, $idExchange, $idExchange, $available, $available, $exchanged, $exchanged, $date, $date, $idSent, $idSent)
    );

    return $query->fetchAll();
  }

  function getCanceledExchanges($login = NULL, $id = NULL, $idExchange = NULL, $available = 1, $login2 = NULL, $idUV = NULL, $idUV2 = NULL, $date = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT exchanges_canceled.id, idExchange, login, exchanges.idUV, exchanges.idUV2, exchanges_canceled.date, exchanges_canceled.available, exchanges_canceled.login2, exchanges.enabled
        FROM exchanges_canceled, exchanges
        WHERE (? IS NULL OR login = ?) AND (? IS NULL OR exchanges_canceled.id = ?) AND (? IS NULL OR exchanges.idUV = ?) AND (? IS NULL OR exchanges.idUV2 = ?) AND (? IS NULL OR exchanges_canceled.idExchange = ?)
        AND (? IS NULL OR exchanges_canceled.available = ?) AND (? IS NULL OR exchanges_canceled.login2 = ?) AND (? IS NULL OR exchanges_canceled.date = ?)
        AND exchanges.id = exchanges_canceled.idExchange
        ORDER BY date',
      array($login, $login, $id, $id, $idUV, $idUV, $idUV2, $idUV2, $idExchange, $idExchange, $available, $available, $login2, $login2, $date, $date)
    );

    return $query->fetchAll();
  }

  function checkIfUVIsExchangeable($login, $idUV) {
    $query = $GLOBALS['db']->request(
      'SELECT uvs_followed.id
        FROM uvs_followed
        WHERE uvs_followed.login = ? AND uvs_followed.idUV = ? AND uvs_followed.enabled = 1',
      array($login, $idUV)
    );

    if (count($query->fetchAll()) !== 1)
      return 'Problème survenu avec l\'appartenance d\'un créneau';
    if (count(getCanceledExchanges($login, NULL, NULL, 1, NULL, $idUV)) + count(getCanceledExchanges($login, NULL, NULL, 1, NULL, NULL, $idUV)) !== 0)
      return 'Un créneau en cours d\'annulation ne peut être échangé';

    return TRUE;
  }

  function checkIfNoMoreSent($idExchange, $uv, $uv2) {
    // Si on était le dernier à demander, on désactive l'annonce
    if (count(getSentExchanges(NULL, NULL, $idExchange, 1, 0)) == 0) {
      $received = getReceivedExchanges(NULL, NULL, $idExchange, 1, 0);

      foreach ($received as $toEmail) {
        $studentInfos = getStudentInfos($toEmail['login']);

        sendMail(
          $studentInfos['email'],
           uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition reçue indisponible',
          'La dernière personne qui souhaitait échanger avec toi son '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' avec le tien du '.
dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].' n\'est plus disponible à l\'échange.

Tu peux toujours toi-même proposer cet échange en cliquant ici: '.linkToExchange(array(
            'mode_type' => 'received',
            'mode_option' => 'available',
            'id' => 'received-'.$toEmail['login'].'-'.$uv['id']
          ))
        );
      }

      $GLOBALS['db']->request(
        'UPDATE exchanges SET enabled = 0 WHERE id = ?',
        array($idExchange)
      );

      return TRUE;
    }

    return FALSE;
  }

  function checkIfNoMoreReceived($idExchange, $uv, $uv2) {
    // On vérifie si tout le monde a répondu la proposition
    if (count(getReceivedExchanges(NULL, NULL, $idExchange, 1, 0)) == 0) {
      $sentExchanges = getSentExchanges(NULL, NULL, $idExchange, 1, 0);

      foreach ($sentExchanges as $toEmail) {
        $studentInfos = getStudentInfos($toEmail['login']);

        sendMail(
          $studentInfos['email'],
          uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition envoyée refusée',
          'Plus personne ne souhaite échanger ton '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' avec le sien du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].'.

Tu peux toujours proposer d\'échanger d\'autres créneaux de '.uvTypeToText($uv['type']).' de '.$uv['uv'].' en cliquant ici: '.linkToExchange(array(
            'mode_type' => 'uvs_followed',
            'uv' => $uv['uv'],
            'type' => $uv['type']
          ))
        );
      }

      $GLOBALS['db']->request(
        'UPDATE exchanges_sent SET available = 0, date = NOW() WHERE idExchange = ?',
        array($idExchange)
      );

      $GLOBALS['db']->request(
        'UPDATE exchanges SET enabled = 0 WHERE idExchange = ?',
        array($idExchange)
      );

      return TRUE;
    }

    return FALSE;
  }

  function sendMailToWaitings($idExchange, $uv, $uv2) {
    // On envoie un mail à ceux qui attendent s'ils restent encore des gens qui peuvent répondre
    if (count(getReceivedExchanges(NULL, NULL, $idExchange, 1, 0)) != 0 && count(getSentExchanges(NULL, NULL, $idExchange, 1, 0)) != 0) {
      $received = getSentExchanges(NULL, NULL, $idExchange, 1, 0);
      $nbrTotal = count(getSentExchanges(NULL, NULL, $idExchange));
      $nbrExchanged = count(getSentExchanges(NULL, NULL, $idExchange, NULL, 1));
      $nbrReceived = count(getReceivedExchanges(NULL, NULL, $idExchange, 1, 0));

      foreach ($received as $place => $toEmail) {
        $studentInfos = getStudentInfos($toEmail['login']);

        sendMail(
          $studentInfos['email'],
          uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition envoyée en attente',
          'Tu es maintenant la '.($place == 0 ? 'première' : ($place + 1).'ème').' personne à demander l\'échange du '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' avec le tien du '.
dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].'

Cet échange a été demandé par '.$nbrTotal.' personne'.($nbrTotal > 1 ? 's' : '').' et '.$nbrExchanged.' personne'.($nbrExchanged > 1 ? 's' : '').' ont effectué cet échange.
A côté, '.$nbrReceived.' personne'.($nbrReceived > 1 ? 's n\'ont' : ' n\'a').' pas encore répondu à cette proposition d\'échange.

Tu peux toujours annuler cet échange en cliquant ici: '.linkToExchange(array(
            'mode_type' => 'sent',
            'mode_option' => 'available',
            'id' => 'sent-'.$toEmail['login'].'-'.$uv2['id']
          ))
        );
      }

      return TRUE;
    }

    return FALSE;
  }

  function askExchange($login, $idUV, $idUV2, $note) { // Afficher dans le mail combien je dois attendre de validation. Si je suis premier, indiquer que tout le monde a été contacté par mail
    $exchange = getExchanges(NULL, $idUV, $idUV2);
    $data = getUV(NULL, NULL, NULL, $idUV);
    $uv = $data[0];
    $data = getUV(NULL, NULL, NULL, $idUV2);
    $uv2 = $data[0];

    // On vérifie qu'on souhaite échanger la même UV et le même type et qu'on ne souhaite pas échanger le même créneau (ce qui était possible pendant quelques jours de dev xD)
    if ($uv['uv'] != $uv2['uv'])
      return 'Echange entre deux UVs différentes impossible';
    if ($uv['type'] != $uv2['type'])
      return 'Echange entre deux types de créneau différents impossible';
    if ($idUV == $idUV2)
      return 'Impossible d\'échanger le même créneau - petit malin va -';

    // On vérifie qu'on peut échanger notre créneau
    $result = checkIfUVIsExchangeable($login, $idUV);
    if ($result !== TRUE)
      return $result;

    $text = 'Tu viens de proposer ton '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].
    ' en échange avec le celui du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].'.

Tu seras contacté.e dès que quelqu\'un aura accepté la proposition d\'échange.

Si tu regrettes ta proposition d\'échange, tu peux toujours l\'annuler (avant que quelqu\'un n\'accepte ta proposition) en cliquant ici: '.linkToExchange(array(
      'mode_type' => 'sent',
      'mode_option' => 'available',
      'id' => 'sent-'.$login.'-'.$uv2['id']
    ));

    // On regarde si l'annonce n'existe pas
    if (count($exchange) == 0) {
      $GLOBALS['db']->request(
        'INSERT INTO exchanges(idUV, idUV2) VALUES(?, ?)',
        array($idUV, $idUV2)
      );

      $exchange = getExchanges(NULL, $idUV, $idUV2);

      $students = getFollowingStudents($idUV2, 1);
      foreach ($students as $student) { // On envoie une demande à ceux qui possèdent le créneau qui nous intéresse
        $studentInfos = getStudentInfos($student['login']);
        $GLOBALS['db']->request(
          'INSERT INTO exchanges_received(idExchange, login, date) VALUES(?, ?, NOW())',
          array($exchange[0]['id'], $studentInfos['login'])
        );

        sendMail(
          $studentInfos['email'],
          uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition reçue',
          'Tu viens de recevoir une proposition qui est la suivante: obtenir le '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].
          ' en échange avec le tien du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].'

Bien sûr, tu peux attendre pour répondre voire, ne pas répondre du tout à la proposition qui t\'a été faite. Par contre, ça se fait au shotgun, le premier qui accepte est le premier servi.
Il n\'est pas sûr que la proposition ait été faite par plusieurs personnes, il se peut donc, que si la propostion ait été acceptée, elle ne soit plus valide.

Tu peux accepter la proposition d\'échange en cliquant ici: '.linkToExchange(array(
            'mode_type' => 'received',
            'mode_option' => 'available',
            'id' => 'received-'.$student['login'].'-'.$uv['id']
          ))
        );
      }
      $text .= '

Tu es le premier/ère à faire cette demande d\'échange, un mail a été envoyé à toutes les personnes qui pourraient accepter ta proposition.';
    }
    elseif (!$exchange[0]['enabled']) { // On regarde si l'annonce est inactive
      // On vérifie qu'on ait pas déjà demandé
      if (count(getSentExchanges($login, NULL, $exchange[0]['id'])))
        return 'Demande d\'échange déjà réalisée';

      // On regarde si l'annonce a été désactivée parce que plus persone ne veut échanger
      if (count(getReceivedExchanges(NULL, NULL, $exchange[0]['id'], 1, 0)) == 0)
        return 'Cette proposition d\'échange n\'intéresse plus personne';

      $GLOBALS['db']->request(
        'UPDATE exchanges SET enabled = 1 WHERE id = ?',
        array($exchange[0]['id'])
      );

      $students = getReceivedExchanges(NULL, NULL, $exchange[0]['id'], 1, 0);
      foreach ($students as $student) { // On renvoie un mail à ceux qui n'ont pas répondu à la proposition d'échange
        $studentInfos = getStudentInfos($student['login']);
        sendMail(
          $studentInfos['email'],
          uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition reçue de nouveau disponible',
          'La proposition suivante à laquelle tu n\'avais pas répondu vient d\'être de nouveau disponible: obternir le '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].
          ' en échange avec le tien du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].'

Bien sûr, tu peux (encore) attendre pour répondre voire, ne pas répondre du tout à la proposition qui t\'a été faite. Par contre, ça se fait au shotgun comme tu le sais, le premier qui accepte est le premier servi.
Il n\'est pas sûr que la proposition ait été faite par plusieurs personnes, il se peut donc, que si la propostion ait été acceptée, elle ne soit plus valide.

Tu peux accepter la proposition d\'échange en cliquant ici: '.linkToExchange(array(
            'mode_type' => 'received',
            'mode_option' => 'available',
            'id' => 'received-'.$student['login'].'-'.$uv['id']
          ))
        );
      }

      $text .= '

La proposition d\'échange a déjà été réalisée, cependant, elle n\'était plus active. Un mail a donc été envoyé à toutes les personnes qui n\'avaient pas répondu à la proposition.';
    }
    else {
      // On vérifie qu'on ait pas déjà demandé
      if (count(getSentExchanges($login, NULL, $exchange[0]['id'])))
        return 'Demande d\'échange déjà réalisée';

      $nbr = count(getSentExchanges(NULL, NULL, $exchange[0]['id']));
      $text .= '

Tu es la '.($nbr + 1).'ème personne à proposer cette échange. Tu seras tenu.e informé.e lorsqu\'un échange aura été effectué';
    }

    // On ajoute notre demande
    $GLOBALS['db']->request(
      'INSERT INTO exchanges_sent(idExchange, login, note, date) VALUES(?, ?, ?, NOW())',
      array($exchange[0]['id'], $login, $note)
    );

    $studentInfos = getStudentInfos($login);
    sendMail(
      $studentInfos['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition envoyée',
      $text
    );

    return TRUE;
  }

  function acceptReceivedExchange($idExchange) {
    // On vérifie que l'annonce existe/est active
    if (count(getExchanges($idExchange, NULL, NULL, 1)) == 0)
      return 'Aucune proposition existante';

    // On vérifie qu'il y a bien des gens qui propose
    if (count(getSentExchanges(NULL, NULL, $idExchange, 1, 0)) == 0)
      return 'Personne ou plus personne ne propose cet échange';

    // On vérifie qu'on a bien reçu une proposition
    if (count(getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 1, 0)) == 0)
      return 'Impossible d\'accepter une proposition non-reçu';

    // On récupère le premier élu
    $data = getSentExchanges(NULL, NULL, $idExchange, 1, 0);
    $sent = $data[0];
    $data = getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 1, 0);
    $received = $data[0];

    $data = getExchanges($idExchange, NULL, NULL, 1);
    $exchange = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV']);
    $uv = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV2']);
    $uv2 = $data[0];

    // On vérifie que chacun peut échanger son uv (c'est à dire que chacun possède bien son créneau et qu'ils sont pas en train de vouloir la redonner au précédent s'il existe et l'échanger avec le suivant)
    checkIfUVIsExchangeable($sent['login'], $uv['id']);
    checkIfUVIsExchangeable($received['login'], $uv2['id']);

    // On met à jour les échanges
    $GLOBALS['db']->request(
      'UPDATE exchanges_sent SET available = 0, exchanged = 1, date = NOW(), idReceived = ? WHERE id = ?',
      array($received['id'], $sent['id'])
    );
    $GLOBALS['db']->request(
      'UPDATE exchanges_received SET available = 0, exchanged = 1, date = NOW(), idSent = ? WHERE id = ?',
      array($sent['id'], $received['id'])
    );

    // On fait les vérifications concernant la propostion (envoi de mail si nécessaire)
    checkIfNoMoreSent($idExchange, $uv, $uv2);
    checkIfNoMoreReceived($idExchange, $uv, $uv2);
    sendMailToWaitings($idExchange, $uv, $uv2);

    // On récupère les couleurs #Perfection
    $query = $GLOBALS['db']->request(
      'SELECT color FROM uvs_followed WHERE idUV = ? AND login = ? AND enabled = 1',
      array($uv['id'], $sent['login'])
    );
    $data = $query->fetch();
    $colorSent = $data['color'];
    $query = $GLOBALS['db']->request(
      'SELECT color FROM uvs_followed WHERE idUV = ? AND login = ? AND enabled = 1',
      array($uv2['id'], $received['login'])
    );
    $data = $query->fetch();
    $colorReceived = $data['color'];

    // On désactive les créneaux échangés
    $GLOBALS['db']->request(
      'UPDATE uvs_followed SET enabled = 0, exchanged = 1 WHERE idUV = ? AND login = ?',
      array($uv['id'], $sent['login'])
    );
    $GLOBALS['db']->request(
      'UPDATE uvs_followed SET enabled = 0, exchanged = 1 WHERE idUV = ? AND login = ?',
      array($uv2['id'], $received['login'])
    );

    // On ajoute les créneaux échangés
    $GLOBALS['db']->request(
      'INSERT INTO uvs_followed(idUV, login, exchanged, color) VALUES(?, ?, 1, ?)',
      array($uv2['id'], $sent['login'], $colorSent)
    );
    $GLOBALS['db']->request(
      'INSERT INTO uvs_followed(idUV, login, exchanged, color) VALUES(?, ?, 1, ?)',
      array($uv['id'], $received['login'], $colorReceived)
    );

    // On annule toutes les propositions qu'on a faite
    $sentBySender = getSentExchanges($sent['login'], NULL, NULL, 1, 0, $sent['idUV']);
    foreach ($sentBySender as $toCancel)
      cancelAskExchange($toCancel['idExchange'], $sent['login'], 'tu ne possèdes plus ce créneau, étant donné que tu viens de l\'échanger');
    $sentByReceiver = getSentExchanges($received['login'], NULL, NULL, 1, 0, $received['idUV2']);
    foreach ($sentByReceiver as $toCancel)
      cancelAskExchange($toCancel['idExchange'], $received['login'], 'tu ne possèdes plus ce créneau, étant donné que tu viens de l\'échanger');


    // On affilie toutes les propositions qu'on a reçu pour notre créneau à l'autre (si on m'a proposé d'autres échanges pour le même créneau, il faut que celui qui a le nouveau créneau puisse voir les propositions et plus l'autre)
    $receivedBySender = getReceivedExchanges($sent['login'], NULL, NULL, 1, 0, NULL, $sent['idUV']);
    foreach ($receivedBySender as $toChange)
      $GLOBALS['db']->request(
        'UPDATE exchanges_received SET login = ? WHERE id = ?',
        array($received['login'], $toChange['id'])
      );
    $receivedByReceiver = getReceivedExchanges($received['login'], NULL, NULL, 1, 0, NULL, $received['idUV2']);
    foreach ($receivedByReceiver as $toChange)
      $GLOBALS['db']->request(
        'UPDATE exchanges_received SET login = ? WHERE id = ?',
        array($sent['login'], $toChange['id'])
      );

    // Envoie des mails
    $senderInfos = getStudentInfos($sent['login']);
    $receiverInfos = getStudentInfos($received['login']);
    sendMail(
      $senderInfos['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition envoyée acceptée',
      'Ta proposition d\'échanger ton '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].
      ' avec le celui du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].' a été accepté par '.($receiverInfos['surname'] == NULL ? $receiverInfos['login'] : $receiverInfos['firstname'].' '.$receiverInfos['surname']).'.

Il est nécessaire maintenant que tu discutes avec lui/elle pour contacter le/la responsable de l\'UV pour le/la tenir informé.e de cet échange.
Voici son adresse email: '.$receiverInfos['email'].'

Si tu regrettes ton échange, tu peux toujours annuler l\'échange (au plus tôt et en le/la prévenant par mail) en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'sent',
        'mode_option' => 'accepted',
        'id' => 'sent-'.$sent['login'].'-'.$uv2['id']
      )).(count($receivedBySender) == 0 ? '' : '

En échangeant, tu as reçu de nouvelles propositions d\'échange avec ton nouveau créneau récemment échangé. Tu peux regarder toutes les propositions reçues et en attente de réponses en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'received',
        'mode_option' => 'available'
      )))
    );
    sendMail(
      $receiverInfos['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition reçue acceptée',
      'Tu as accepté une proposition d\'échanger qui le '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].
      ' avec le tien du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].' proposé par '.($senderInfos['surname'] == NULL ? $senderInfos['login'] : $senderInfos['firstname'].' '.$senderInfos['surname']).'.

Il est nécessaire maintenant que tu discutes avec lui/elle pour contacter le/la responsable de l\'UV pour le/la tenir informé.e de cet échange.
Voici son adresse email: '.$senderInfos['email'].'

Si tu regrettes ton échange, tu peux toujours annuler l\'échange (au plus tôt et en le/la prévenant par mail) en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'received',
        'mode_option' => 'accepted',
        'id' => 'received-'.$received['login'].'-'.$uv['id']
      )).(count($receivedByReceiver) == 0 ? '' : '

En échangeant, tu as reçu de nouvelles propositions d\'échange avec ton nouveau créneau récemment échangé. Tu peux regarder toutes les propositions reçues et en attente de réponses en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'received',
        'mode_option' => 'available'
      )))
    );

    return TRUE;
  }

  function refuseReceivedExchange($idExchange) {
    // On vérifie que l'annonce existe/est active
    if (count(getExchanges($idExchange, NULL, NULL, 1)) == 0)
      return 'Aucune proposition existante';

    $data = getExchanges($idExchange, NULL, NULL, 1);
    $exchange = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV']);
    $uv = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV2']);
    $uv2 = $data[0];

    // On vérifie qu'il y a bien des gens qui propose
    if (count(getSentExchanges(NULL, NULL, $idExchange, 1, 0)) == 0)
      return 'Personne ou plus personne ne propose cet échange';

    // On vérifie qu'on a bien reçu une proposition
    if (count(getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 1, 0)) == 0)
      return 'Impossible d\'accepter une proposition non-reçu';

    $GLOBALS['db']->request(
      'UPDATE exchanges_received SET available = 0, date = NOW() WHERE idExchange = ? AND login = ?',
      array($idExchange, $_SESSION['login'])
    );

    checkIfNoMoreReceived($idExchange, $uv, $uv2);

    sendMail(
      $_SESSION['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition reçue refusée',
      'Tu as refusé la proposition d\'échange du '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' avec le tien du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].'.

Tu peux toujours proposer d\'échanger d\'autres créneaux de '.uvTypeToText($uv['type']).' de '.$uv['uv'].' en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'uvs_followed',
        'uv' => $uv['uv'],
        'type' => $uv['type']
      ))
    );

    return TRUE;
  }

  function askCancelExchange($idExchange, $note) {
    // On vérifie que l'échange existe bien
    if (count(getExchanges($idExchange)) == 0)
      return 'Impossible d\'annuler un échange non existant';

    // Demande d'annulation déjà effectuée
    if (count(getCanceledExchanges($_SESSION['login'], NULL, $idExchange, 1)) != 0)
      return 'Demande d\'annulation déjà réalisée';
    if (count(getCanceledExchanges($_SESSION['login'], NULL, $idExchange, 0)) != 0)
      return 'Demande d\'annulation déjà réalisée et annulée. Tu ne peux plus faire la demande à présent';

    // Demande d'annulation déjà réalisée par l'autre => on annule définitvement
    if (count(getCanceledExchanges(NULL, NULL, $idExchange, 1, $_SESSION['login'])) != 0)
      return cancelExchange($idExchange);

    $data = getExchanges($idExchange);
    $exchange = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV']);
    $uv = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV2']);
    $uv2 = $data[0];

    // On identiie celui qui envoie et celui recoit et surtout s'il y a bien eu échange
    if (count(getSentExchanges($_SESSION['login'], NULL, $idExchange, 0, 1)) == 1) {
      $data = getSentExchanges($_SESSION['login'], NULL, $idExchange, 0, 1);
      $sent = $data[0];
      if (count(getReceivedExchanges(NULL, $sent['idReceived'], $idExchange, 0, 1)) == 1) {
        $data = getReceivedExchanges(NULL, $sent['idReceived'], $idExchange, 0, 1);
        $received = $data[0];
      }
      else
        return 'Impossible d\'identifier avec qui l\'échange a été effectué';
    }
    elseif (count(getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 0, 1)) == 1) {
      $data = getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 0, 1);
      $received = $data[0];
      if (count(getSentExchanges(NULL, $received['idSent'], $idExchange, 0, 1)) == 1) {
        $data = getSentExchanges(NULL, $received['idSent'], $idExchange, 0, 1);
        $sent = $data[0];
      }
      else
        return 'Impossible d\'identifier avec qui l\'échange a été effectué';
    }
    else
      return 'L\'échange n\'a pas été effectué';

    // On vérifie qu'on a chacun, encore le créneau échangé
    checkIfUVIsExchangeable($sent['login'], $exchange['idUV2']);
    checkIfUVIsExchangeable($received['login'], $exchange['idUV']);

    // Infos de base
    $studentInfos = getStudentInfos($sent['login'] == $_SESSION['login'] ? $received['login'] : $sent['login']);

    // On met notre demande d'échange en état d'annulation
    $GLOBALS['db']->request(
      'UPDATE exchanges_sent SET available = 1, exchanged = 1, date = NOW() WHERE idExchange = ? AND login = ?',
      array($idExchange, $sent['login'])
    );
    $GLOBALS['db']->request(
      'UPDATE exchanges_received SET available = 1, exchanged = 1, date = NOW() WHERE idExchange = ? AND login = ?',
      array($idExchange, $received['login'])
    );

    // On ajoute la demande d'annulation
    $GLOBALS['db']->request(
      'INSERT INTO exchanges_canceled(idExchange, login, login2, date) VALUES(?, ?, ?, NOW())',
      array($idExchange, $_SESSION['login'], $studentInfos['login'])
    );

    sendMail(
      $_SESSION['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Demande d\'annulation d\'échange envoyée',
      'Tu viens de demander l\'annulation de ton échange qui était ton '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' contre celui du '.
dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].' que tu souhaites finalement récupérer.

Si la personne accepte, tu récupèrerais ton '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].'.

Un mail lui a été envoyé avec cette explication:
'.$note.'

Bien sûr, tu peux relancer la personne en lui renvoyant un mail à cette adresse: '.$studentInfos['email'].'

En espérant qu\'une solution puisse être trouvée pour vous deux.

Tu peux toujours annuler ta demande d\'annulation (que tu ne pourras plus redemander) en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'canceled',
        'mode_option' => 'sent',
        'id' => 'canceled-'.$_SESSION['login'].'-'.$uv2['id']
      ))
    );

    sendMail(
      $studentInfos['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Demande d\'annulation d\'échange reçue',
      'Tu viens de recevoir une demande d\'annulation de ton échange de la part de '.$_SESSION['firstname'].' '.$_SESSION['surname'].' qui était le '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' contre le tien du '.
    dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].'.

En acceptant la demande d\'annulation, tu récupèrerais ton '.uvTypeToText($uv2['type']).' de '.$uv2['uv'].' du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].'.

Voici l\'explication de sa demande:
'.$note.'

Bien sûr, tu peux en discuter avec la personne en lui renvoyant un mail à cette adresse: '.$_SESSION['email'].'

En espérant qu\'une solution puisse être trouvée pour vous deux.

Tu peux accepter la demande d\'annulation en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'canceled',
        'mode_option' => 'received',
        'id' => 'canceled-'.$studentInfos['login'].'-'.$uv['id']
      ))
    );

    return TRUE;
  }

  function cancelAskCancelExchange($idExchange) {
    // Demande d'annulation non effectuée
    if (count(getCanceledExchanges($_SESSION['login'], NULL, $idExchange)) == 0)
      return 'Demande d\'annulation non réalisée';

    $data = getExchanges($idExchange);
    $exchange = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV']);
    $uv = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV2']);
    $uv2 = $data[0];

    // On identiie celui qui envoie et celui recoit et surtout s'il y a bien eu échange
    if (count(getSentExchanges($_SESSION['login'], NULL, $idExchange, 1, 1)) == 1) {
      $data = getSentExchanges($_SESSION['login'], NULL, $idExchange, 1, 1);
      $sent = $data[0];
      if (count(getReceivedExchanges(NULL, $sent['idReceived'], $idExchange, 1, 1)) == 1) {
        $data = getReceivedExchanges(NULL, $sent['idReceived'], $idExchange, 1, 1);
        $received = $data[0];
      }
      else
        return 'Impossible d\'identifier avec qui l\'échange a été effectué';
    }
    elseif (count(getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 1, 1)) == 1) {
      $data = getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 1, 1);
      $received = $data[0];
      if (count(getSentExchanges(NULL, $received['idSent'], $idExchange, 1, 1)) == 1) {
        $data = getSentExchanges(NULL, $received['idSent'], $idExchange, 1, 1);
        $sent = $data[0];
      }
      else
        return 'Impossible d\'identifier avec qui l\'échange a été effectué';
    }
    else
      return 'L\'échange n\'a pas été effectué';

    // Infos de base
    $studentInfos = getStudentInfos($sent['login'] == $_SESSION['login'] ? $received['login'] : $sent['login']);

    // On met les demandes d'échanges en état échangé
    $GLOBALS['db']->request(
      'UPDATE exchanges_sent SET available = 0, exchanged = 1, date = NOW() WHERE idExchange = ? AND login = ?',
      array($idExchange, $sent['login'])
    );
    $GLOBALS['db']->request(
      'UPDATE exchanges_received SET available = 0, exchanged = 1, date = NOW() WHERE idExchange = ? AND login = ?',
      array($idExchange, $received['login'])
    );

    // On désactive la demande d'annulation
    $GLOBALS['db']->request(
      'UPDATE exchanges_canceled SET available = 0, date = NOW() WHERE idExchange = ? AND login = ?',
      array($idExchange, $_SESSION['login'])
    );

    sendMail(
      $_SESSION['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Demande d\'annulation d\'échange envoyée annulée',
      'Tu viens d\'annuler ta demande d\'annulation de ton échange qui était ton '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' contre celui du '.
dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].' que tu souhaitais récupérer.

A présent, tu ne peux plus demander d\'annuler l\'échange.'
    );

    sendMail(
      $studentInfos['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Demande d\'annulation d\'échange reçue annulée',
      $_SESSION['firstname'].' '.$_SESSION['surname'].' qui a demandé l\'annulation de ton échange qui était le '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' contre le tien du '.
    dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].' a annulé sa demande.

Tu peux toujours toi-même demander d\'annuler l\'échange si tu le souhaites en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'canceled',
        'mode_option' => 'received',
        'id' => 'canceled-'.$studentInfos['login'].'-'.$uv['id']
      ))
    );

    return TRUE;
  }

  function cancelExchange($idExchange) {
    // On vérifie que l'annonce existe/est active
    if (count(getExchanges($idExchange, NULL, NULL)) == 0)
      return 'Aucune proposition existante';

    $data = getExchanges($idExchange, NULL, NULL);
    $exchange = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV']);
    $uv = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV2']);
    $uv2 = $data[0];

    // On identiie celui qui envoie et celui recoit et surtout s'il y a bien eu échange
    if (count(getSentExchanges($_SESSION['login'], NULL, $idExchange, 1, 1)) == 1) {
      $data = getSentExchanges($_SESSION['login'], NULL, $idExchange, 1, 1);
      $sent = $data[0];
      if (count(getReceivedExchanges(NULL, $sent['idReceived'], $idExchange, 1, 1)) == 1) {
        $data = getReceivedExchanges(NULL, $sent['idReceived'], $idExchange, 1, 1);
        $received = $data[0];
      }
      else
        return 'Impossible d\'identifier avec qui l\'échange a été effectué';
    }
    elseif (count(getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 1, 1)) == 1) {
      $data = getReceivedExchanges($_SESSION['login'], NULL, $idExchange, 1, 1);
      $received = $data[0];
      if (count(getSentExchanges(NULL, $received['idSent'], $idExchange, 1, 1)) == 1) {
        $data = getSentExchanges(NULL, $received['idSent'], $idExchange, 1, 1);
        $sent = $data[0];
      }
      else
        return 'Impossible d\'identifier avec qui l\'échange a été effectué';
    }
    else
      return 'L\'échange n\'a pas été effectué ou aucune demande d\'annulation est en cours';

    if (count(getCanceledExchanges(NULL, NULL, $idExchange, NULL, $_SESSION['login'])) == 0)
      return 'Aucune demande d\'annulation reçue';
    elseif (count(getCanceledExchanges(NULL, NULL, $idExchange, 0, $_SESSION['login'])) == 1)
      return 'La demande d\'annulation reçue n\'est plus valide';

    // On vérifie que chacun peut échanger son uv (c'est à dire que chacun possède bien son créneau et qu'ils sont pas en train de vouloir la redonner au précédent s'il existe et l'échanger avec le suivant)
    checkIfUVIsExchangeable($received['login'], $uv['id']);
    checkIfUVIsExchangeable($sent['login'], $uv2['id']);

    // On annule l'échange
    $GLOBALS['db']->request(
      'DELETE FROM exchanges_sent WHERE id = ?',
      array($sent['id'])
    );
    $GLOBALS['db']->request(
      'UPDATE exchanges_received SET available = 1, exchanged = 0, date = NOW() WHERE id = ?',
      array($received['id'])
    );

    // On supprime les demandes d\'annulation
    $GLOBALS['db']->request(
      'DELETE FROM exchanges_canceled WHERE idExchange = ? AND login = ? AND login2 = ?',
      array($idExchange, $sent['login'], $received['login'])
    );
    $GLOBALS['db']->request(
      'DELETE FROM exchanges_canceled WHERE idExchange = ? AND login = ? AND login2 = ?',
      array($idExchange, $received['login'], $sent['login'])
    );

    // On supprime les créneaux échangés
    $GLOBALS['db']->request(
      'DELETE FROM uvs_followed WHERE idUV = ? AND login = ?',
      array($uv2['id'], $sent['login'])
    );
    $GLOBALS['db']->request(
      'DELETE FROM uvs_followed WHERE idUV = ? AND login = ?',
      array($uv['id'], $received['login'])
    );

    // On réactive les créneaux initiaux
    $GLOBALS['db']->request(
      'UPDATE uvs_followed SET enabled = 1, exchanged = 0 WHERE idUV = ? AND login = ?',
      array($uv['id'], $sent['login'])
    );
    $GLOBALS['db']->request(
      'UPDATE uvs_followed SET enabled = 1, exchanged = 0 WHERE idUV = ? AND login = ?',
      array($uv2['id'], $received['login'])
    );

    // On désactive la demande d'annulation
    $GLOBALS['db']->request(
      'UPDATE exchanges_canceled SET available = 0, date = NOW() WHERE idExchange = ? AND login2 = ?',
      array($idExchange, $_SESSION['login'])
    );

    // On annule toutes les propositions qu'on a faite
    $sentBySender = getSentExchanges($sent['login'], NULL, NULL, 1, 0, $sent['idUV2']);
    foreach ($sentBySender as $toCancel)
      cancelAskExchange($toCancel['idExchange'], $sent['login'], 'tu ne possèdes plus ce créneau, étant donné que tu viens d\'annuler son échange');
    $sentByReceiver = getSentExchanges($received['login'], NULL, NULL, 1, 0, $received['idUV']);
    foreach ($sentByReceiver as $toCancel)
      cancelAskExchange($toCancel['idExchange'], $received['login'], 'tu ne possèdes plus ce créneau, étant donné que tu viens d\'annuler son échange');

    // On affilie toutes les propositions qu'on a reçu pour notre créneau à l'autre (si on m'a proposé d'autres échanges pour le même créneau, il faut que celui qui a le nouveau créneau puisse voir les propositions et plus l'autre)
    $receivedBySender = getReceivedExchanges($sent['login'], NULL, NULL, 1, 0, NULL, $sent['idUV2']);
    foreach ($receivedBySender as $toChange)
      $GLOBALS['db']->request(
        'UPDATE exchanges_received SET login = ? WHERE id = ?',
        array($received['login'], $toChange['id'])
      );
    $receivedByReceiver = getReceivedExchanges($received['login'], NULL, NULL, 1, 0, NULL, $received['idUV']);
    foreach ($receivedByReceiver as $toChange)
      $GLOBALS['db']->request(
        'UPDATE exchanges_received SET login = ? WHERE id = ?',
        array($sent['login'], $toChange['id'])
      );

    // Infos de base
    $studentInfos = getStudentInfos($sent['login'] == $_SESSION['login'] ? $received['login'] : $sent['login']);

    sendMail(
      $_SESSION['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Annulation d\'un échange',
      'Tu as accepté d\'annuler d\'échanger le '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].
      ' avec celui du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].' a été annulé à la demande de '.($studentInfos['surname'] == NULL ? $studentInfos['login'] : $studentInfos['firstname'].' '.$studentInfos['surname']).'.

Les emplois du temps ont été actualisés et chacun a récupéré son créneau de base.'
    );
    sendMail(
      $studentInfos['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Annulation d\'un échange',
      'Ta demande d\'annuler l\'échanger du '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].
      ' avec celui du '.dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].' a été accepté par '.($_SESSION['surname'] == NULL ? $_SESSION['login'] : $_SESSION['firstname'].' '.$_SESSION['surname']).'.

Les emplois du temps ont été actualisés et chacun a récupéré son créneau de base.'
    );

    return TRUE;
  }

  function cancelAskExchange($idExchange, $login = NULL, $reason = NULL) {
    // On vérifie qu'on avait bien une proposition encore disponibles
    if (count(getSentExchanges($login, NULL, $idExchange, 1, 0)) == 0)
      return 'Impossible d\'annuler cet échange';

    $query = $GLOBALS['db']->request(
      'DELETE FROM exchanges_sent WHERE idExchange = ? AND login = ?',
      array($idExchange, $login == NULL ? $_SESSION['login'] : $login)
    );

    $studentInfos = getStudentInfos($login == NULL ? $_SESSION['login'] : $login);
    $data = getExchanges($idExchange);
    $exchange = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV']);
    $uv = $data[0];
    $data = getUV(NULL, NULL, NULL, $exchange['idUV2']);
    $uv2 = $data[0];

    // On fait les vérifications avec envoie de mails si nécessaire
    checkIfNoMoreSent($idExchange, $uv, $uv2);
    sendMailToWaitings($idExchange, $uv, $uv2);

    sendMail(
      $studentInfos['email'],
      uvTypeToText($uv['type']).' de '.$uv['uv']. ' - Proposition envoyée annulée',
      'Tu as annulé ta demande d\'échange qui était ton '.uvTypeToText($uv['type']).' de '.$uv['uv'].' du '.dayToText($uv['day']).' de '.$uv['begin'].' à '.$uv['end'].' contre celui du '.
dayToText($uv2['day']).' de '.$uv2['begin'].' à '.$uv2['end'].($reason == NULL ? '' : ' (pour la raison que '.$reason.')').'.

Tu peux toujours reproposer cet échange en cliquant ici: '.linkToExchange(array(
        'mode_type' => 'uvs_followed',
        'uv' => $uv2['uv'],
        'type' => $uv2['type'],
        'id' => 'uv-'.$uv2['uv'].'-'.$uv2['id']
      ))
    );

    return TRUE;
  }
