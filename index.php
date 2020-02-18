<?php include($_SERVER['DOCUMENT_ROOT'] . '/emploidutemps' . '/ressources/php/include.php'); ?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=0.75">
  <link rel="stylesheet" href="ressources/css/style.css" type="text/css">
  <link rel="stylesheet" href="ressources/css/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="ressources/css/jquery-ui.min.css">
  <link rel="stylesheet" href="ressources/css/jquery-ui.structure.min.css">
  <link rel="stylesheet" href="ressources/css/jquery.timepicker.css">
  <link rel="stylesheet" href="ressources/css/horaires-previsionnels.css">
  <title>Emploi d'UTemps</title>
</head>

<body>
  <div id='header'>
    <button id='navButton' onClick='toogleNav()'><i class="fa fa-2x fa-bars" aria-hidden="true"></i></button>
    <a id='name' href='/emploidutemps'>Emploi d'UTemps</a>
    <button id='search' onClick='startSearch()'><i class="fa fa-2x fa-search" aria-hidden="true"></i></button>
    <button id='help' onClick='help()' DISABLED><i class="fa fa-2x fa-question-circle" aria-hidden="true"></i></button>
    <button id='parametersButton' onClick="toogleParam()"><i class="fa fa-2x fa-cog" aria-hidden="true"></i></button>
  </div>

  <div id='zoneFocus' onClick='unFocus()'></div>
  <div id='zoneGrey'></div>
  <div id='zonePopup' onClick='closePopup();'></div>
  <div id='popup'></div>
  <div id='prevoir'>
    <div id="prevoirHead">Découvrez votre futur emploi du temps !</div>
    <div id="future">
      <h2>↯ Copiez vos horaires prévisionnels ci-dessous ↯</h2>
      <p>
        Accessible sur <a href="https://webapplis.utc.fr/smeappli/inscriptions/index.xhtml" target="_blank">cette page</a>, rentrez vos UVs puis cliquez sur le bouton <button id="horaires-p" onclick="this.title = ['Sur l\'autre site', 'Pas ici banane'][+(this.title[0] == 'S')]" title="Pas ici banane">Horaires prévisionnels</button>, sélectionnez tous les horaires du tableau (<kbd id="c-a">Ctrl-A</kbd>, en vrac c'est pas grave) et copiez le ci-dessous.
      </p>

      <p>
        Si vous avez des UVs avec beaucoup de TD et TP, le nombre de combinaisons peut vite exploser ! C'est normal si l'affichage prend un peu de temps. La gestion des cours tous les 15 jours est assez sommaire également, si vous avez 3 créneaux en même temps ça risque de se superposer.
      </p>

      <textarea name="prevoirText" id="prevoirText" rows="5" placeholder="→ Copiez vos horaires ici ←"><?php if (isset($_SESSION['prevoir'])) echo $_SESSION['prevoir']; ?></textarea>
      <p class="center" id="prevoirInfo"></p>
      <p id="prevoirStats"></p>

      <div class="parameters">
        <button id="prevoirButton" onClick="generatePrevoir()" disabled>Générer</button>
      </div>
    </div>
  </div>

  <div id='title'></div>
  <div id='otherDay'><button onClick='setCalendar(focusedDay - 1);'><i class="fa fa-arrow-left" aria-hidden="true"></i></button><button onClick='setCalendar(focusedDay + 1);'><i class="fa fa-arrow-right" aria-hidden="true"></i></button></div>
  <div id="calendar-container"></div>

  <div id="nav">
    <div id='mode' class="menu">
      <div id='modeText'>
        Mode:
      </div>
      <div class='sub-menu'>
        <div>
          Semaine type:
        </div>
        <span <?php if ($_SESSION['extern']) {
                echo 'style="display:none"';
              } ?>> 
          <input name='mode' id='mode_classique' type='radio' onClick='changeMode("classique");'><label for='mode_classique'> Classique</label><br />
          <input name='mode' id='mode_comparer' type='radio' onClick='changeMode("comparer");'><label for='mode_comparer'> Comparer</label><br />
          <input name='mode' id='mode_modifier' type='radio' onClick='changeMode("modifier");'><label for='mode_modifier'> Modifier</label><br />
        </span>
        <input name='mode' id='mode_prevoir' type='radio' onClick='promptPrevoir();'><label for='mode_prevoir'> Prévoir</label><br />
      </div>
      <div class='sub-menu'>
        <div id="mode_week">
          Semaine du <?php $data = explode('-', $_SESSION['week']);
                      echo $data[2], '/', $data[1]; ?>:
        </div>
        <input name='mode' id='mode_semaine' type='radio' onClick='changeMode("semaine");'><label for='mode_semaine'> Classique</label><br />
        <input name='mode' id='mode_organiser' type='radio' onClick='changeMode("organiser");'><label for='mode_organiser'> Superposer</label><br />
        <div id='week'>
          <button id='before'><i class="fa fa-arrow-left" aria-hidden="true"></i></button>
          <button id='actual'>Cette semaine</button>
          <button id='after'><i class="fa fa-arrow-right" aria-hidden="true"></i></button>
        </div>
      </div>
    </div>
    <div class="menu">
      <div id='affichageText'>
        Affichage:
      </div>
      <div id='affichage_tabs' class="sub-menu">
        Par emploi du temps:
        <div id='tabs'></div>
      </div>
      <div id='affichage_groups' class="sub-menu">
        Par groupe:
        <div id='groups'></div>
      </div>
    </div>
  </div>

  <div id="parameters" class="menu sub-menu">
    <div id='affichage_printed' style='display: none;' class='menu sub-menu'>
      <div id='printedText'></div>
      <div id='printed'></div>
      <div>
        <button id='eventTool' onClick="delActive(Object.keys(window.active))"><i class="fa fa-calendar-minus-o" aria-hidden="true"></i> Désafficher tout le monde</button>
      </div>
    </div>
    <div id='affichage_prevoir' style='display: none;' class='menu sub-menu'>
      <div id='prevoirStatText'></div>
      <div>
        <button onClick="promptPrevoir()"><i class="fa fa-calendar-o" aria-hidden="true"></i> Prévoir un autre calendrier</button>
      </div>
      <div id='prevoirPossibilities'></div>
    </div>
    <div id='affichage_tools' class="menu sub-menu">
      Outils:
      <div style='display: none' id='modifyTools'>
        <!-- Bug chelou, je dois mettre un timeout d'un minimum de 1ms pour que l'acutalisation fonctionne correctementt... Peutt-etre dû à mon serveur -->
        <button id='eventTool' onClick="" DISABLED><i class="fa fa-calendar-times-o" aria-hidden="true"></i> Refuser toutes les propositions reçues avec des créneaux incompatibles</button>
        <button id='eventTool' onClick="getRequest('exchanges.php', { 'mode': 'refuseAll' }, setTimeout(function () { generate(true) }, 10))"><i class="fa fa-calendar-times-o" aria-hidden="true"></i> Refuser toutes les propositions reçues</button>
        <button id='eventTool' onClick="getRequest('exchanges.php', { 'mode': 'cancelSentAll' }, setTimeout(function () { generate(true) }, 10))"><i class="fa fa-calendar-times-o" aria-hidden="true"></i> Annuler toutes les propositions envoyées</button>
        <button id='eventTool' onClick="" DISABLED><i class="fa fa-calendar-minus-o" aria-hidden="true"></i> Demander une annulation de tous mes échanges</button>
        <button onClick="" DISABLED><i class="fa fa-handshake-o" aria-hidden="true"></i> Indiquer un échange déjà effectué</button>
        <button onClick="" DISABLED><i class="fa fa-remove" aria-hidden="true"></i> Supprimer des créneaux de cours</button>
      </div>
      <div style='display: none' id='weekTools'>
        <div>
          Calendrier:
          <input type='radio' id='withWeekTool' name='weekTool' onClick="generate()" CHECKED><label for='withWeekTool'>Coloré</label>
          <input type='radio' id='withoutWeekTool' name='weekTool' onClick="generate()"><label for='withoutWeekTool'>Normal</label>
        </div>
        <div>
          Infos:
          <input type='radio' id='withAlternanceTool' name='alternanceTool' onClick="generate()" CHECKED><label for='withAlternanceTool'>Alternance</label>
          <input type='radio' id='withoutAlternanceTool' name='alternanceTool' onClick="generate()"><label for='withoutAlternanceTool'>Normal</label>
        </div>
        <button id='eventTool' onClick="createEvenement()"><i class="fa fa-calendar-o" aria-hidden="true"></i> Créer un évènement</button>
      </div>
      <div style='display: none' id='organizeTools'>
        <button id='eventTool' onClick="generateFreeTimes()"><i class="fa fa-calendar-o" aria-hidden="true"></i> Trouver le meilleur créneau</button>
      </div>
      <div id='printedTools'></div>
      <div id='tools'>
        <button id='export' onClick="exportDownload()"><i class="fa fa-download" aria-hidden="true"></i> Exporter/Télécharger</button>
      </div>
    </div>
    <div id='affichage_useful' class="menu sub-menu">
      Liens utiles:
      <div>
        <button onClick="window.open('https://assos.utc.fr');"><i class="fa fa-external-link" aria-hidden="true"></i> Portail des assos</button>
        <button onClick="window.open('https://moodle.utc.fr/login/index.php?authCAS=CAS');"><i class="fa fa-external-link" aria-hidden="true"></i> Moodle</button>
        <button onClick="window.open('https://assos.utc.fr/uvweb/');"><i class="fa fa-external-link" aria-hidden="true"></i> UVWeb</button>
        <button onClick="window.open('https://github.com/simde-utc/emploidutemps');"><i class="fa fa-external-link" aria-hidden="true"></i> Github (code source)</button>
        <button onClick="window.open('mailto:simde@assos.utc.fr?subject=[EmploidUTemps] ');"><i class="fa fa-envelope-o" aria-hidden="true"></i> Nous contacter</button>
      </div>
    </div>
    <div id='affichage_options' class="menu sub-menu">
      Options:
      <div>
        Suggestions:
        <input type='radio' id='withSuggestions' name='suggestionsTool' onClick="pasEncoreRomanet()"><label for='withSuggestions'>Avec</label>
        <input type='radio' id='withoutSuggestions' name='suggestionsTool' onClick="generate()" CHECKED><label for='withoutSuggestions'>Sans</label>
      </div>
      <div>
        <button onClick="getRequest('parameters.php', { 'defaultMode': get.mode })"><i class="fa fa-cog" aria-hidden="true"></i> Affecter ce mode par défaut</button>
        <button <?php if ($_SESSION['status'] != -1) {
                  echo 'style="display:none;"';
                } ?> onClick="changeStatus(1)"><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> Se réabonner</button>
        <button <?php if ($_SESSION['status'] == -1) {
                  echo 'style="display:none;"';
                } ?> onClick="changeStatus(-1);"><i class="fa fa-thumbs-o-down" aria-hidden="true"></i> Se désabonner</button>
        <button <?php if ($_SESSION['status'] != -1) {
                  echo 'style="display:none;"';
                } ?> onClick="window.open('https://assos.utc.fr/');" DISABLED><i class="fa fa-remove" aria-hidden="true"></i> Se désinscrire définitivement</button>
      </div>
    </div>
    <div id='affichage_disconnect' class="menu sub-menu">
      <div>
        <button onClick="disconnect();"><i class="fa fa-sign-out" aria-hidden="true"></i> Se déconnecter</button>
      </div>
    </div>
    <div id='affichage_credit' class="menu sub-menu">
      <h5 style='text-align: center;'>
        <i class="fa fa-code" aria-hidden="true"></i> avec <i class="fa fa-heart" aria-hidden="true"></i> par Samy NASTUZZI
      </h5>
    </div>
  </div>

  <script type="text/javascript" src="ressources/js/jquery-3.1.1.min.js"></script>
  <script type="text/javascript" src="ressources/js/generation.js"></script>
  <script type="text/javascript" src="ressources/js/jquery.touchSwipe.min.js"></script>
  <script type="text/javascript" src="ressources/js/html2canvas.min.js"></script>
  <script type="text/javascript" src="ressources/js/jspdf.min.js"></script>
  <script type="text/javascript" src="ressources/js/clipboard.min.js"></script>
  <script type="text/javascript" src="ressources/js/jquery.mininoty.min.js"></script>
  <script type="text/javascript" src="ressources/js/jquery-ui.min.js"></script>
  <script type="text/javascript" src="ressources/js/datapicker-fr.js"></script>
  <script type="text/javascript" src="ressources/js/jquery.timepicker.min.js"></script>
  <script src="ressources/js/horaires-previsionnels.js"></script>
  <script type="text/javascript">
    generateCalendar([], 1, {});
    setCalendar();

    window.get = <?php echo json_encode(array('mode' => $_SESSION['mode'])); ?>;

    window.get = <?php
                  $query = $GLOBALS['db']->request(
                    'SELECT login FROM students WHERE login = ?',
                    array($_SESSION['login'])
                  );

                  if ($query->rowCount() == 0)
                    echo json_encode(array('mode' => 'semaine'));
                  else
                    echo json_encode($_GET);

                  echo ';
      ';
                  /*
      // Lance le paramètre demandé (désinscription par exemple)
      if (isset($_GET['param']) && is_string($_GET['param']) && !empty($_GET['param']))
        echo 'setTimeout(function () { parameters("', $_GET['param'], '"); }, 1000);';
*/
                  if ($_SESSION['status'] == 0 && !$_SESSION['extern'])
                    echo "var isNew = true;";
                  else
                    echo "var isNew = false;";

                  if ($_SESSION['extern'])
                    echo "var isExtern = true;";
                  else
                    echo "var isExtern = false;";

                  ?>

    if (isNew) {
      setTimeout(function() {
        popup('Bienvenue sur le service Emploid\'UTemps', $('<div></div>').addClass('centerCard')
          .append($('<div></div>').text('Salut ! Bienvenue sur un service proposé par le BDE/SiMDE qui te permettra de réaliser tout un tas de choses avec ton emploi du temps étudiant.'))
          .append($('<br /><br />'))
          .append($('<div></div>').text('Emploid\'UTemps te permet de faire plusieurs choses:'))
          .append($('<div></div>').text('- Afficher un emploi du temps (comme le tien, celui d\'un.e de tes potes ou d\'une UV) sur une semaine type'))
          .append($('<div></div>').text('- Comparer ton emploi du temps avec un autre (comme celui d\'un.e de tes potes ou d\'une UV) sur une semaine type'))
          .append($('<div></div>').text('- Modifier ton emploi du temps en échangeant tes créneaux avec d\'autres valides et disponibles (de façon très simple)'))
          .append($('<div></div>').text('- Afficher ton emploi du temps d\'une semaine réelle (comme afficher ton emploi du temps sur la semaine du 05/03)'))
          .append($('<div></div>').text('- Afficher simultanément plusieurs emplois du temps pour organiser facilement des réunions ou des évènements'))
          .append($('<br /><br />'))
          .append($('<div></div>').text('Mais c\'est aussi encore plus:'))
          .append($('<div></div>').text('- Rechercher via un Trombi un.e étudiant.e'))
          .append($('<div></div>').text('- Affichage automatique de tes associations avec l\'affichage des membres'))
          .append($('<div></div>').text('- Une gestion totale d\'un système de groupe que tu peux toi-même créer'))
          .append($('<div></div>').text('- Des possibilités infinies d\'export: en pdf, en image, en ics pour le mettre dans ton agenda informatique...'))
          .append($('<div></div>').text('- Un système facile et intelligent d\'échange de créneaux'))
          .append($('<div></div>').text('- Une paramétration et des outils'))
          .append($('<div></div>').text('- Un affichage des salles de cours et de TDs libres'))
          .append($('<div></div>').text('- Un site adapté aux mobiles'))
          .append($('<br /><br />'))
          .append($('<div></div>').text('En cliquant sur le bouton Accepter, j\'accepte d\'utiliser le service de la meilleure des manières et d\'être responsable des mes choix (lors de mes échanges):'))
          .append($('<button></button>').text('Accepter').on('click', function() {
            changeStatus(1);
          }))
        );
      }, 1000);
    }

    $("body").keyup(function(event) {
      if (event.keyCode == 27) {
        closePopup();
        window.search = '';
      }
    });


    generate();

    var toogleNav = function() {
      $('#nav').toggleClass('see');
      $('#parameters').removeClass('see');
    }

    var toogleParam = function() {
      $('#parameters').toggleClass('see');
      $('#nav').removeClass('see');
    }

    $(window).resize(function() {
      setCalendar();
    });

    $(function() {
      $('#calendar-container').swipe({
        swipeLeft: function() {
          setCalendar(focusedDay + 1);
        },
        swipeRight: function() {
          setCalendar(focusedDay - 1);
        }
      });
    });

    $.miniNoty = function(message, type) {
      var timeToHide = 10000;
      var timeAnimEnd = 250;
      var padding = 10;

      var cls = 'miniNoty miniNoty-' + (type ? type : 'success');
      var node = $('<div/>', {
        'class': cls,
        html: message
      });

      if ($('.miniNoty').length) {
        var elLast = $('.miniNoty:last-child');
        var elLastBottom = parseInt(elLast.css('bottom'));
        var elLastHeight = elLast.outerHeight();

        node.css('bottom', elLastBottom + elLastHeight + padding + 'px');
      }

      $('body').append(node);

      // delete on click
      node.click(function() {
        node.removeClass('miniNoty-show');
        setTimeout(function() {
          node.remove();
        }, timeAnimEnd);
      })

      // push stack
      setTimeout(function() {
        node.addClass('miniNoty-show');
      }, 10)

      // timeout to hide
      setTimeout(function() {
        node.removeClass('miniNoty-show');
        setTimeout(function() {
          node.remove();
        }, timeAnimEnd)
      }, timeToHide)
    }

    // En cas d'erreur, recharger la page
    $(document).ajaxError(function(err, jqxhr, settings, thrownError) {
      console.log(jqxhr.status);
      if (jqxhr.status == '503')
        $.miniNoty('<i class="fa fa-exclamation-circle" aria-hidden="true"></i> Il faut que tu te reconnectes au CAS', 'normal');
      else
        $.miniNoty('<i class="fa fa-exclamation-circle" aria-hidden="true"></i> Une erreur a été détectée. Si le problème persiste, <a href=/disconnect.php>déconnecte-toi</a> ou signale-le nous. Merci !', 'error');

      setTimeout(function() {
        location.reload();
      }, 5000);
    });
  </script>
</body>

</html>

<!--

        <button style="display: inline; margin-left: 0;" onClick="window.open(\'/emploidutemps\' + \'/logs/changelog.txt\');"><i class="fa fa-file-text-o" aria-hidden="true"></i> Changelog</button>
        <button style="display: inline; margin-left: 5px;" onClick="parameters(\'checkUpdate\');"><i class="fa fa-refresh" aria-hidden="true"></i> Chercher une màj (indisponible pour le moment)</button>
      </div>
      <button onClick="parameters(\'contacter\');"><i class="fa fa-envelope-o" aria-hidden="true"></i> Nous contacter</button>';
//      <button onClick="window.open(\'/emploidutemps\' + \'/maj.php\');">Rechercher des mises à jour</button>
        echo '<button onClick="parameters(\'sedesinscrire\');""><i class="fa fa-times" aria-hidden="true"></i> Se désinscrire du service</button>';
      else
        echo '<button onClick="parameters(\'reinscription\');""><i class="fa fa-check" aria-hidden="true"></i> Se réinscrire au service</button>';
    }

    </div>';
-->