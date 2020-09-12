var defaultSettings = {
    headers: [],
    tasks: [],

    //cardTemplate: '<div class="time">${horaire}</div><div>${uv}</div><div>(${type} ${group})</div><div>${salle}</div><div>${note}</div><div class="interraction" style="opacity: 0; visibility: hidden;">${interraction}</div>',
    cardTemplate: '<div class="time">${horaire}</div><div class="uvType"><span>${uv}</span><h5>${type} ${group}</h5></div><div class="uvSalle">${salle}</div><div class="uvNote">${note}</div><div class="interraction" style="opacity: 0; visibility: hidden;">${interraction}</div>',
    // OnClick event handler
    onClick: function (card) {
      if (window.click) {
        window.click = false;
        return;
      }

      if (card.columnPerDay == 1 && window.compare === 0) {
        var type = '';
        if (card.type == 'TD')
          type = 'D';
        else if (card.type == 'TP')
          type = 'T';
        else
          type = 'C';

        $('#zoneGrey').removeClass('focused');
        $('#zoneFocus').removeClass('focused');
        window.card = '';

        seeOthers(card.uv, type, card.idUV);
      }
      else {
        var end = (window.mode == 'planifier' ? 629 : 588);

        if (window.card === '') {
          if (card.top + 150 > end) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
            $('#' + card.id).css('top', end - 150);
          if (card.height < 150)
            $('#' + card.id).css('height', 150);
          $('#' + card.id).css('left', 0).css('width', 132).addClass('focus');
          $('#' + card.id + ' .interraction').css('opacity', '1').css('visibility', 'visible');

          $('#zoneGrey').addClass('focused');
          $('#zoneFocus').addClass('focused');

          window.card = card;
        }
        else if (window.card.id == card.id) {
          $('#' + card.id).css('top', card.top).css('left', card.left).css('height', card.height).css('width', card.width).removeClass('focus');
          $('#' + card.id + ' .interraction').css('opacity', '0').css('visibility', 'hidden');

          $('#zoneGrey').removeClass('focused');
          $('#zoneFocus').removeClass('focused');

          window.card = '';
        }
        else {
          $('#' + window.card.id).css('top', window.card.top).css('left', window.card.left).css('height', window.card.height).css('width', window.card.width).removeClass('focus');
          $('#' + window.card.id + ' .interraction').css('opacity', '0').css('visibility', 'hidden');

          if (card.top + 150 > end) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
            $('#' + card.id).css('top', end - 150);
          if (card.height < 150)
            $('#' + card.id).css('height', 150);
          $('#' + card.id).css('left', 0).css('width', 132).addClass('focus');
          $('#' + card.id + ' .interraction').css('opacity', '1').css('visibility', 'visible');

          $('#zoneGrey').addClass('focused');
          $('#zoneFocus').addClass('focused');

          window.card = card;
        }
      }
    },
    // Css classes
    containerCssClass: 'skeduler-container',
    headerContainerCssClass: 'skeduler-headers',
    schedulerContainerCssClass: 'skeduler-main',
    taskPlaceholderCssClass: 'skeduler-task-placeholder',
    cellCssClass: 'skeduler-cell',

    lineHeight: 20,      // height of one half-hour line in grid
    borderWidth: 1,      // width of board of grid cell

    debug: false
};
var settings = {};

function schedule(tasks) {
  $("#skeduler-container").skeduler({
    headers: window.headers,
    tasks: tasks,
  });

  if (window.mode === 'planifier' || window.mode == 'organiser') {
    var cells = $('.futureDay .skeduler-cell10');
    var length = cells.length;
    var perDay = length / 7;

    cells.each(function(index) {
      var day = Math.floor(index / perDay);
      var begin = window.HOUR_MIN + ((index % perDay) / 2);
      if (begin >= window.HOUR_MAX)
        begin = -1;

      $(cells[index]).on('click', function() {
        //createTask(day, begin, 1, 'Créer un nouvel évènement');
        console.log('Bientot');
      });
    });
  }
}
/**
 * Convert double value of hours to zero-preposited string with 30 or 00 value of minutes
 */
function toTimeString(value) {
  return (value < 10 ? '0' : '') + Math.floor(value) + (Math.ceil(value) > Math.floor(value) ? ':30' : ':00');
}

/**
 * Return height of task card based on duration of the task
 * duration - in hours
 */
function getCardHeight(duration) {
  return (settings.lineHeight + settings.borderWidth) * (duration * 2) - 1;
}

/**
 * Return top offset of task card based on start time of the task
 * startTime - in hours
 */
function getCardTopPosition(startTime) {
  return (settings.lineHeight + settings.borderWidth) * (startTime * 2);
}

/**
* Render card template
*/
function renderInnerCardContent(task) {
  var result = settings.cardTemplate;
  for (var key in task) {
    if (task.hasOwnProperty(key))
      result = result.replace('${' + key + '}', task[key]);
  }

  return $(result);
}

/**
 * Generate task cards
 */
function appendTasks(placeholder, tasks) {
  var passed = [];
  var colors = ['#7DC779', '#82A1CA', '#F2D41F', '#457293', '#AB7AC6', '#DF6F53', '#B0CEE9', '#576D7C', '#1C704E'];
  var nbrPassed = 0;

  tasks.forEach(function(task) {
    var classCard = 'card';

    if (task.duration >= (window.HOUR_MAX - window.HOUR_MIN)) {
      task.startTime = (window.HOUR_MAX - window.HOUR_MIN);
      task.duration = 1;
      task.horaire = 'Journée';
    }

    task.top = Math.ceil(getCardTopPosition(task.startTime));
    task.height = Math.ceil(getCardHeight(task.duration));
    var style = 'top: ' + task.top + 'px; height: ' + task.height + 'px';
    var nbrSameTime = 0;

    tasks.forEach(function(toCompare) {
      if (toCompare.duration < (window.HOUR_MAX - window.HOUR_MIN) && (task.columnPerDay === toCompare.columnPerDay && task.column === toCompare.column) && ((task.startTime >= toCompare.startTime && task.startTime < toCompare.startTime + toCompare.duration) || (toCompare.startTime >= task.startTime && toCompare.startTime < task.startTime + task.duration)))
        nbrSameTime++;

      if (window.mode != 'organiser' && window.mode != 'planifier' && task.id != toCompare.id && task.idUV == toCompare.idUV)
      classCard += ' sameCard';
    });

    if (typeof task.interraction == 'undefined') {
      if (typeof task.login == 'undefined' && (window.compare == 1 || window.columnPerDay == 1))
        task.interraction = '';
      else
        task.interraction = "<button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + ";' onClick='edtUV(\"" + task.uv + "\");'><i class='fa fa-calendar-o' aria-hidden='true'></i> Voir l'edt de l'UV</button>";

      task.interraction += "<button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + "; width: 59px' onClick='uvMoodle(\"" + task.uv + "\");'><i class='fa fa-external-link' aria-hidden='true'></i> Moodle</button><button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + "; width: 59px' onClick='uvWeb(\"" + task.uv + "\");' ><i class='fa fa-external-link' aria-hidden='true'></i> UVweb</button><button class='option' style=\"color:" + task.fgColor + "; background-color:" + task.bgColor + ";\" onClick=\"seeEtu(" + task.idUV + ", '" + task.bgColor + "', '" + task.fgColor + "');\"><i class='fa fa-user-o' aria-hidden='true'></i> Voir les étudiants</button>";

      if (window.compare === 0 && task.columnPerDay == 2)
        task.interraction += "<button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + "' onClick='askForExchange(" + window.idUV + ", " + task.idUV + ");'><i class='fa fa-handshake-o' aria-hidden='true'></i> Proposer un échange</button>";
    }

    if (task.session) {
      var type = '';
      if (task.type == 'TD')
        type = 'D';
      else if (task.type == 'TP')
        type = 'T';
      else
        type = 'C';

      for (var key in colors) {
        if (colors[key] == task.bgColor)
          task.interraction += "<button class='colorButton' style='background-color:" + colors[key] + "; color: " + task.fgColor + "' onClick='changeColor(" + task.idUV + ", \"#NULL\");' ><i class=\"fa fa-times\" aria-hidden=\"true\"></i></button>";
        else
          task.interraction += "<button class='colorButton' style='background-color:" + colors[key] + "; color: " + colors[key] + "' onClick='changeColor(" + task.idUV + ", \"" + colors[key] + "\");'>0</button>";
      }

      task.interraction += "<i onClick='$(this).next().click();' class='colorButton fa fa-pencil-square-o' style='height: 10px; width: 10px; color:" + task.fgColor + "' aria-hidden='true'></i><input class='colorButton' style='display: none;' onChange='changeColor(" + task.idUV + ", this.value);' type='color'/>";
    }

    if (window.idUV == task.idUV) {
      if (task.columnPerDay == 1)
        style += '; box-shadow: 0em 1em 1em rgba(0,0,0,.5)';
      else {
        style += '; opacity: 0.5';
        task.fgColor = '#000000';
        task.bgColor = '#CCCCCC';
        task.interraction = '';
      }
    }

    style += '; color: ' + task.fgColor + '; background-color: ' + task.bgColor;

    if (window.mode == 'organiser') {
      style += '; opacity: 0.5; box-shadow: none; cursor: default';

      card = $('<div></div>')
        .attr({
          style: style,
          id: task.id,
          class: classCard
        });
      card.append($('<div>' + task.idUV + '<h6 style="display: inline; padding-left: 2px;">' + task.salle + '</h6></div>')).appendTo(placeholder);
      return;
    }

    if (nbrSameTime == 1 || (nbrSameTime == 2 && task.frequence == 2)) {
      task.width = (132 / (window.columnPerDay * task.frequence)) - (window.columnPerDay - 1);
      task.left = (task.frequence == 2 && task.semaine == '' ? (window.columnPerDay == 1 ? 32 : 16) : 0) + (task.semaine === 'B' ? 66 / window.columnPerDay : 0) + (task.columnPerDay == 2 ? 69 : 0) + (window.columnPerDay == 2 ? -1 : 0);
    }
    else {
      var toPass = [task.columnPerDay, task.column, task.startTime, task.duration];
      if ((passed[0] === toPass[0] && passed[1] === toPass[1]) && ((passed[2] >= toPass[2] && passed[2] < toPass[2] + toPass[3]) || (toPass[2] >= passed[2] && toPass[2] < passed[2] + passed[3])))
        nbrPassed += 1;
      else {
        passed = toPass;
        nbrPassed = 0;
      }

      nbr = (nbrSameTime > 4 ? ((nbrSameTime + 4) % 5) : nbrSameTime);
      if (nbr == 0) { nbr = 2; }
      space = ((nbrSameTime % 2 == 1 ? (nbr - (nbrPassed % nbr)) : nbrPassed) % nbr);

      task.width = 132 / nbr;
      task.left = 132 * space / nbr;
    }

    style += '; width: ' + task.width + 'px; left: ' + task.left + 'px';

    card = $('<div></div>')
      .attr({
        style: style,
        id: task.id,
        class: classCard
      });

    if (window.mode == 'planifier') {
      if (window.get.indexOf('salle=') > -1 && task.horaire != "Journée") {
        card.on('click', function () {
          nbr = task.uv.split(' ')[0];
          html = '<div id="popupHead">' + nbr + ' salle' + (nbr == 1 ? '' : 's') + ' disponible' + (nbr == 1 ? '' : 's') + (task.horaire == 'Journée' ? ' toute la journée': (' de ' + task.horaire.replace('-', ' à ').replace(':', 'h').replace(':', 'h'))) + '</div><table style="padding: 1%; width: 100%">'

          if (task.note['C'].length > 0)
            html += '<tr><td style="width: 15%">Salle' + (task.note['C'].length == 1 ? '' : 's') + ' de cours</td><td style="width: 85%"> ' + task.note['C'].join(', ') + '</td></tr>';
          if (task.note['D'].length > 0)
            html += '<tr><td style="width: 15%">Salle' + (task.note['D'].length == 1 ? '' : 's') + ' de TD</td><td style="width: 85%"> ' + task.note['D'].join(', ') + '</td></tr>';

          popup(html + '</table>', task.bgColor, task.fgColor);
        });
        card.append($('<div class="time">' + (task.startTime < (window.HOUR_MAX - window.HOUR_MIN) ? (task.duration - Math.floor(task.duration) > 0.25 ? Math.ceil(task.duration) : Math.floor(task.duration)) + 'h' : task.horaire) + '</div><div class="uvType"><span>' + task.uv + '</span></div>')).appendTo(placeholder);
        return;
      }

      task.interraction = "<button disabled class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + ";' onClick='modify(\"" + task.idUV + "\");'><i class='fa fa-pencil-square-o' aria-hidden='true'></i> Modifier</button>\
      <button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + ";' onClick='seeOthers(\"" + task.uv + "\", \"" + type + "\", " + task.idUV + ");'><i class='fa fa-info' aria-hidden='true'></i> Echanger son " + (task.type == 'Cours' ? task.type.toLowerCase() : task.type) + "</button>\
      <button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + ";' onClick='afficher(\"" + task.idUV + "\");'><i class='fa fa-calendar-o' aria-hidden='true'></i> Afficher d'autres options</button>";
    }

    if (task.idUV == null)
      task.interraction = '';

    if (window.idUV != task.idUV)
      card.on('click', function () {
        settings.onClick(task);
      });

    card.append(renderInnerCardContent(task)).appendTo(placeholder);
  }, this);
}

/**
* Generate scheduler grid with task cards
* options:
* - headers: string[] - array of headers
* - tasks: Task[] - array of tasks
* - containerCssClass: string - css class of main container
* - headerContainerCssClass: string - css class of header container
* - schedulerContainerCssClass: string - css class of scheduler
* - lineHeight - height of one half-hour cell in grid
* - borderWidth - width of border of cell in grid
*/
$.fn.skeduler = function( options ) {
  settings = $.extend(defaultSettings, options);
  var currentDay = (date.getDay() + 6) % 7;

  if (Date.parse(window.week) + (1000*60*60*24*(currentDay + 2)) < Date.now())
    currentDay = 7;
  else if (Date.parse(window.week) > Date.now())
    currentDay = -1;

  if (settings.debug) {
    console.time('skeduler');
  }

  var skedulerEl = $(this);

  skedulerEl.empty();
  skedulerEl.addClass(settings.containerCssClass);

  var div = $('<div></div>');

  // Add headers
  var headerContainer = div.clone().addClass(settings.headerContainerCssClass);
  var d = 0;
  var classDay = '';

  settings.headers.forEach(function(element) {
    if (window.mode === 'planifier' || window.mode === 'organiser') {
      if (d < currentDay)
        classDay = 'passedDay';
      else if (d == currentDay)
        classDay = 'currentDay';
      else
        classDay = 'futureDay';
    }

    div.clone().addClass(classDay).text(element).appendTo(headerContainer);
    d++;
  }, this);

  // Pour améliorer la propreté du tableau
  div.clone().css('flex', '0').appendTo(headerContainer);

  //div.clone().addClass('currentDay').text(settings.headers[currentDay]).appendTo(headerContainer);
  skedulerEl.append(headerContainer);

  // Add schedule
  var scheduleEl = div.clone().addClass(settings.schedulerContainerCssClass);
  var scheduleTimelineEl = div.clone().addClass(settings.schedulerContainerCssClass + '-timeline');
  var scheduleBodyEl = div.clone().addClass(settings.schedulerContainerCssClass + '-body');

  var gridColumnElement = [];
  for (var right = 0; right < window.columnPerDay; right++)
    gridColumnElement[right] = div.clone();

  var hour = date.getHours();
  var classHour = '';

  // Populate timeline
  for (var i = window.HOUR_MIN; i < window.HOUR_MAX; i++) {
    if (window.mode === 'planifier' || window.mode === 'organiser') {
      if (currentDay == -1)
        classHour = 'futureHour';
      else if (currentDay == 7)
        classHour = 'passedHour';
      else if (i < hour)
        classHour = 'passedHour';
      else if (i === hour)
        classHour = 'currentHour';
      else
        classHour = 'futureHour';
    }
    else
      classHour = '';

    div.clone().addClass(classHour).text(toTimeString(i)).appendTo(scheduleTimelineEl);
    div.clone().addClass(classHour).appendTo(scheduleTimelineEl);

    for (right = 0; right < window.columnPerDay; right++) {
      gridColumnElement[right].append(div.clone().addClass(settings.cellCssClass + window.columnPerDay + right));
      gridColumnElement[right].append(div.clone().addClass(settings.cellCssClass + window.columnPerDay + right));
    }
  }

  if (window.mode === 'planifier' || window.mode === 'organiser') {
    if (classHour == 'currentHour')
      classHour = 'futureHour';
    div.clone().text('').addClass(classHour).addClass('allDay').appendTo(scheduleTimelineEl);
    gridColumnElement[0].append(div.clone().addClass('allDay').addClass(settings.cellCssClass + '10'));
  }

  // Populate grid
  for (var j = 0; j < settings.headers.length * window.columnPerDay; j++) {
    var placeholder = div.clone().addClass(settings.taskPlaceholderCssClass);
    appendTasks(placeholder, settings.tasks.filter(function (t) { return t.column == (j / window.columnPerDay); }));

    var el = gridColumnElement[j % window.columnPerDay].clone();

    if (window.mode === 'planifier' || window.mode === 'organiser') {
      if (j == currentDay) {
        el = div.clone();

        for (var i = window.HOUR_MIN; i < window.HOUR_MAX; i++) {
          if (i < hour)
            classHour = 'passedHour';
          else if (i === hour)
            classHour = 'currentHour';
          else
            classHour = 'futureHour';

          el.append(div.clone().addClass(classHour).addClass(settings.cellCssClass + '10'));
          el.append(div.clone().addClass(classHour).addClass(settings.cellCssClass + '10'));
        }

        el.append(div.clone().addClass('allDay').addClass(classHour).addClass(settings.cellCssClass + '10'));
      }
      else if (j > currentDay)
        el.addClass('futureDay');
      else
        el.addClass('passedDay');
    }

    el.addClass('days');
    el.prepend(placeholder);
    el.appendTo(scheduleBodyEl);
    /*
    var el = gridColumnElement.clone();
    el.appendTo(scheduleBodyEl);*/
  }

  scheduleEl.append(scheduleTimelineEl);
  scheduleEl.append(scheduleBodyEl);

  skedulerEl.append(scheduleEl);

  if (settings.debug) {
    console.timeEnd('skeduler');
  }

  return skedulerEl;
};
