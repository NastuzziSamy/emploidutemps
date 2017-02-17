<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  $defaultNote = 'Je souhaiterais échanger mon UV contre la tienne.';

  function printError($error) {
    echo '<div style="background-color: #FF0000" id="popupHead">Erreur: ', $error, '.</div>';
    exit;
  }

  function printSucces($succes) {
    echo '<div style="background-color: #00FF00" id="popupHead">', $succes, '.</div>';
    exit;
  }

  function sendMail($mail, $message) {
    $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM etudiants WHERE mail = ?');
    $GLOBALS['bdd']->execute($query, array($mail));

    if ($query->fetch()['desinscrit'] == '0')
      return mail($mail, $message.PHP_EOL.PHP_EOL.'Pour arrêter de recevoir des mails du service, vous pouvez à tout moment vous désinscrire en cliquant ici: https://agendutc.nastuzzi.fr/desinscription.php', 'FROM: emploidutemps@nastuzzi.fr');

    return FALSE;
  }

  if (isset($_GET['idExchange']) && is_string($_GET['idExchange']) && !empty($_GET['idExchange'])) {
    if (isset($_GET['refuse']) && is_string($_GET['refuse']) && $_GET['refuse'] == '1') { // On annonce que la proposition n'est plus dispo = refusée
      $query = $GLOBALS['bdd']->prepare('UPDATE recues SET disponible = 0, date = NOW() WHERE login = ? AND idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $_GET['idExchange']));

      if (count(getRecuesList(NULL, $_GET['idExchange'], 1)) == 0) { // On regarde s'il reste encore des propositions non répondus
        // On annonce que personne n'a accepté la proposition
        $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));
        // On indique à tous les demandeurs que tout le monde a refusé
        $query = $GLOBALS['bdd']->prepare('UPDATE envoies SET disponible = 0, date = NOW() WHERE idEchange = ? AND disponible = 1');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));

        $envoies = getEnvoiesList(NULL, $_GET['idExchange'], 1);
        foreach ($envoies as $envoie) {
          $infosLogin = getEtu($envoie['login']);
          mail($infosLogin['login'], 'Echange refusé', 'Salut !'.PHP_EOL.'Une demande d\'échange a été refusée par tout le monde.'.PHP_EOL.'Tente ta chance avec une autre proposition!', 'From: agendutc@nastuzzi.fr');
        }
      }

      printSucces('Proposition refusée avec succès');
    }

    elseif (isset($_GET['accept']) && is_string($_GET['accept']) && $_GET['accept'] == '1') {
      if (count(getRecuesList($_SESSION['login'], $_GET['idExchange'], 1)) == 0) // On vérifie que la personne a bien reçu la demande
        printError('La proposition n\'existe pas ou n\'existe plus');

      $envoies = getEnvoiesList(NULL, $_GET['idExchange'], 1);
      if (count($envoies) == 0) // On vérifie qu'on peut échanger avec quelqu'un
        printError('Personne n\'est disponible pour l\'échange');

      // On annonce que la personne a validé la proposition
      $query = $GLOBALS['bdd']->prepare('UPDATE recues SET disponible = 0, echange = 1 WHERE login = ? AND idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $_GET['idExchange']));

      // On annonce au plus tôt demandeur que son échange a été accepté
      $query = $GLOBALS['bdd']->prepare('UPDATE envoies SET disponible = 0, echange = 1, date = NOW() WHERE login = ? AND idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($envoies[0]['login'], $_GET['idExchange']));

      // S'il n'y avait qu'un seul (dernier) demandeur, on désactive l'annonce puisque plus personne d'autre demande l'échange
      if (count($envoies) == 1) {
        $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));
      }

      if (count(getRecuesList(NULL, $_GET['idExchange'], 1)) == 0) { // Si plus personne ne peut répondre
        $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));
        $query = $GLOBALS['bdd']->prepare('UPDATE envoies SET disponible = 0, date = NOW() WHERE idEchange = ? AND disponible = 1');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));
      }

      // On rend les UVs échangées non actuel
      $query = $GLOBALS['bdd']->prepare('UPDATE cours SET actuel = 0, echange = 1 WHERE login = ? AND id = ?');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $envoies[0]['pour']));
      $GLOBALS['bdd']->execute($query, array($envoies[0]['login'], $envoies[0]['idUV']));
      // On ajoute aux edt l'UV récupéré
      $query = $GLOBALS['bdd']->prepare('INSERT INTO cours (login, id, actuel, echange) VALUES (?, ?, 1, 1);');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $envoies[0]['idUV']));
      $GLOBALS['bdd']->execute($query, array($envoies[0]['login'], $envoies[0]['pour']));

      $infosLogin = getEtu($envoies[0]['login']);

      // Envoyer une notif' (à voir)
      mail($_SESSION['mail'], 'Echange effectué', 'Salut !'.PHP_EOL.'Un échange a été effectué avec succès '.$infosLogin['nom'].' '.$infosLogin['prenom'].' (mail: '.$infosLogin['mail'].') !'.PHP_EOL.'Ton emploi du temps a été mis à jour !', 'From: agendutc@nastuzzi.fr');
      mail($infosLogin['login'], 'Echange effectué', 'Salut !'.PHP_EOL.'Un échange a été effectué avec succès '.$SESSION['nom'].' '.$SESSION['prenom'].' (mail: '.$SESSION['mail'].') !'.PHP_EOL.'Ton emploi du temps a été mis à jour !', 'From: agendutc@nastuzzi.fr');
      printSucces('Proposition acceptée avec succès. Les emplois du temps ont été mis à jour');
    }

    elseif (isset($_GET['del']) && is_string($_GET['del']) && $_GET['del'] == '1') {
      $envoie = getEnvoiesList($_SESSION['login'], $_GET['idExchange'], 1);
      // On vérifie bien que notre demande est active
      if (count($envoie) == 0)
        printError('Impossible de retirer la proposition');
      // On la supprime
      $query = $GLOBALS['bdd']->prepare('DELETE FROM envoies WHERE idEchange = ? AND login = ?');
      $GLOBALS['bdd']->execute($query, array($_GET['idExchange'], $_SESSION['login']));

      // Si on était le seul à demander, on désactive l'annonce
      if (count(getEnvoiesList(NULL, $_GET['idExchange'], 1)) == 0) {
        $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));
      }

      printSucces('Proposition supprimée avec succès');
    }

    elseif (isset($_GET['infos']) && is_string($_GET['infos']) && $_GET['infos'] == '1') {
      $envoies = getEnvoiesList(NULL, $_GET['idExchange']);
      // On vérifie bien que la proposition a déjà été demandée
      if (count($envoies) == 0)
        printError('Impossible de trouver une information concernant la proposition');
      // On récup les infos concernant les idUVs
      $idUV = getUVFromIdUV($envoies[0]['idUV']);
      $for = getUVFromIdUV($envoies[0]['pour']);
      // Affichage d'un récap de l'échange
      echo '<div id="popupHead">Proposition d\'échange du ', ($idUV['type'] == 'D' ? $idUV['type'] = 'TD' : ($idUV['type'] == 'C' ? $idUV['type'] = 'cours' : $idUV['type'] = 'TP')), ' de ', $idUV['uv'], '<br />Le ', $jours[$idUV['jour']], ' de ', $idUV['debut'], ' à ', $idUV['fin'], ' en ', $idUV['salle'], (($idUV['semaine'] == '') ? '' : ' chaque semaine '.$idUV['semaine']), ' contre celui du ', $jours[$for['jour']], ' de ', $for['debut'], ' à ', $for['fin'], ' en ', $for['salle'], (($for['semaine'] == '') ? '' : ' chaque semaine '.$for['semaine']).'</div>';
      echo '<div id="searchResult">';

      foreach ($envoies as $envoie) { // Afficher la demande en fonction de son état
        if ($envoie['disponible'] == 1) {
          $bgColor = '#0000FF';
          $note = '';
        }
        elseif ($envoie['disponible'] == 0 && $envoie['echange'] == 1) {
          $bgColor = '#00FF00';
          $note = ' (échangé le '.$envoie['date'].')';
        }
        else {
          $bgColor = '#FF0000';
          $note = ' (refusé)';
        }

        $fgColor = getFgColor($bgColor);

        echo '<div class="searchCard" style="width: 100%; background-color: ', $bgColor, '; color: ', $fgColor, '">',
        '<div class="nameCard">', $envoie['login'], $note, '</div>',
        '<div class="noteCard">', $envoie['note'], '</div></div>';
      }

      echo '</div>';
    }
    else
      printError('Aucune demande réalisée');
  }

  // Si on souhaite ajouter un échange
  elseif (isset($_GET['idUV']) && is_string($_GET['idUV']) && !empty($_GET['idUV']) && isset($_GET['for']) && is_string($_GET['for']) && !empty($_GET['for'])) {
    $sessionInfo = getEtu($_SESSION['login']);
    $idUV = getUVFromIdUV($_GET['idUV']);
    $for = getUVFromIdUV($_GET['for']);
    // Vérifications
    if ($_GET['idUV'] == $_GET['for'])
      printError('Echange impossible avec la même UV');

    if ($idUV['uv'] != $for['uv'])
      printError('Les échanges se font uniquement entre la même UV');

    $edts = getEdtEtu($_SESSION['login']);

    $notIn = TRUE;
    foreach ($edts as $edt) {
      if ($edt['id'] == $_GET['idUV'])
        $notIn = FALSE;
    }

    if ($notIn)
      printError('Les échanges ne peuvent être effectués que si les échangeurs possèdent les UVs concernées');

    // Demander d'ajouter
    if (isset($_GET['ask']) && is_string($_GET['ask']) && !empty($_GET['ask'])) {
      $existedExchange = getEchange($_GET['idUV'], $_GET['for'], 0);
      $etuList = getEtuFromIdUV($_GET['for'], 0);

      if (count($existedExchange) == 0) { // On regarde si la proposition est active ou inexsitante
        $existedExchange = getEchange($_GET['idUV'], $_GET['for']);
        // S'il existe déjà, voir si on a pas déjà fait la demande
        if (count($existedExchange) != 0 && count(getEnvoiesList($_SESSION['login'], $existedExchange[0]['idEchange'])) == 1)
          printError('La proposition a déjà été réalisée');
        // On vérifie que certains sont inscrits
        if (count($etuList) == 0)
          printError('Personne ne souhaite recevoir de proposition d\'échange');
      }
      else { // La proposition est inactive
        // On vérifie si tout le monde a refusé la proposition
        if (count(getRecuesList(NULL, $existedExchange[0]['idEchange'])) == 0)
          printError('Cette proposition a déjà été refusée par tout le monde');
      }

      $note = '<br />Attention, cette proposition a déjà été faite';
      $nbrEnvoi = (count($existedExchange) == 0 ? 0 : count(getEnvoiesList(NULL, $existedExchange[0]['idEchange'])));

      echo '<div id="popupHead">Proposition d\'échange du ', ($idUV['type'] == 'D' ? $idUV['type'] = 'TD' : ($idUV['type'] == 'C' ? $idUV['type'] = 'cours' : $idUV['type'] = 'TP')), ' de ', $idUV['uv'], '<br />Le ', $jours[$idUV['jour']], ' de ', $idUV['debut'], ' à ', $idUV['fin'], ' en ', $idUV['salle'], (($idUV['semaine'] == '') ? '' : ' chaque semaine '.$idUV['semaine']), ' contre celui du ', $jours[$for['jour']], ' de ', $for['debut'], ' à ', $for['fin'], ' en ', $for['salle'], (($for['semaine'] == '') ? '' : ' chaque semaine '.$for['semaine']), '<br />',
      ($nbrEnvoi == 0 ? '' : '<br />Attention, cette demande a déjà été proposée '.$nbrEnvoi.' fois'), '<br />Voulez-vous réellement proposer aux ', count($etuList), ' étudiant', (count($etuList) == 1 ? '' : 's'), ' cette échange ?</div>',
      '<textarea maxlength="500" cols="30" rows="5" id="noteExchange" contenteditable>', $defaultNote, '</textarea><br />
      <input type="button" id="sendExchange" value="Proposer l\'échange" onClick="addExchange(', $_GET['idUV'], ', ', $_GET['for'], ', $(\'#noteExchange\').val())"/></div>';
    }

    elseif (isset($_GET['add']) && is_string($_GET['add']) && !empty($_GET['add']) && isset($_POST['note']) && is_string($_POST['note'])) {
      $note = (empty($_POST['note']) ? $defaultNote : $_POST['note']);
      $existedExchange = getEchange($_GET['idUV'], $_GET['for'], 0);

      if (count($existedExchange) == 0) // On regarde si la proposition est active ou inexistante
        $existedExchange = getEchange($_GET['idUV'], $_GET['for']);
      else { // On vérifie ici si la proposition a déjà été proposée mais est inactive
        // On vérifie si tout le monde a refusé la proposition
        if (count(getRecuesList(NULL, $existedExchange['idEchange'])) == 0)
          printError('Cette proposition a déjà été refusée par tout le monde');
        // On réactive la proposition
        $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET disponible = 1 WHERE idEchange = ?');
        $GLOBALS['bdd']->execute($query, array($existedExchange['idEchange']));
      }

      // On regarde si la proposition n'existe pas
      if (count($existedExchange) == 0) {
        $etuList = getEtuFromIdUV($_GET['for'], 0);

        if (count($etuList) == 0) // On vérifie que certains sont inscrits
          printError('Personne ne souhaite recevoir de proposition d\'échange');

        $query = $GLOBALS['bdd']->prepare('INSERT INTO echanges (idUV, pour) VALUES (?, ?)');
        $GLOBALS['bdd']->execute($query, array($_GET['idUV'], $_GET['for']));

        $idExchange = getEchange($_GET['idUV'], $_GET['for'])[0]['idEchange'];

        // On fait la demande aux personnes inscrites à cet idUV
        $query = $GLOBALS['bdd']->prepare('INSERT INTO recues (login, idEchange) VALUES (?, ?)');

        // On vérifie que chaque étudiant n'est pas désinscrit au service et qu'il suit toujours cet idUV
        foreach ($etuList as $etu) {
          if ($etu['desinscrit'] == 0 && $etu['actuel'] == 1) {
            $GLOBALS['bdd']->execute($query, array($etu['login'], $idExchange));
            // On regarde si l'étudiant s'est déjà connecté
            if ($etu['nouveau'] == 0)
              mail($etu['mail'], 'Nouvelle demande d\'échange', 'Salut !'.PHP_EOL.'Tu as reçu une nouvelle demande d\'échange !'.PHP_EOL.'Fais attention, cela ce joue au shotgun !', 'From: agendutc@nastuzzi.fr');
          }
        }
      }
      else {
        $idExchange = $existedExchange[0]['idEchange'];
        // S'il existe déjà, voir si on a pas déjà fait la demande
        if (count(getEnvoiesList($_SESSION['login'], $idExchange)) == 1)
          printError('La proposition a déjà été réalisée');
      }

      // On insère notre demande
      $query = $GLOBALS['bdd']->prepare('INSERT INTO envoies (login, idEchange, note) VALUES (?, ?, ?)');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $idExchange, $note));

      mail($SESSION['mail'], 'Demande d\'échange', 'Salut !'.PHP_EOL.'Ta demande d\'échange a été envoyée avec succès !'.PHP_EOL.'Tu recevras une notification dès la validation d\'un échange !', 'From: agendutc@nastuzzi.fr');
      printSucces('Votre proposition d\'échange a été ajoutée avec succès');
    }
    else
      printError('Aucune demande d\'échange réalisée');
  }
  else
    printError('Impossible de lancer le protocole d\'échange');
?>
