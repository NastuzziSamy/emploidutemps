var HOUR_MIN = 7;
var HOUR_MAX = 21;
var RELOAD_SEC = 120;
var headers = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"];
var date = new Date();
var sessionLogin = '';
var get = {};
var colors = [];
var task = null;
var focusedDay = (date.getDay() + 6) % 7;

var pasEncoreRomanet = function() {
    $.miniNoty('Pas encore Romanet !', 'warning');
}

var getRequest = function (url, get, callback, silentMode) {
  var request = '';

  if (!silentMode) {
    loading();
  }

  for (var key in get) {
    if (typeof get[key] == 'object') {
      for (var key2 in get[key])
        request += '&' + encodeURIComponent(key) + '[]=' + encodeURIComponent(get[key][key2]);
    }
    else
      request += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(get[key]);
  }

  console.log('/emploidutemps/ressources/php/' + url + ($.isEmptyObject(get) ? '' : '?') + request.substr(1));
  $.getJSON('/emploidutemps/ressources/php/' + url + ($.isEmptyObject(get) ? '' : '?') + request.substr(1), function (data) {
    if (data.error)
      $.miniNoty('<i class="fa fa-exclamation-circle" aria-hidden="true"></i> ' + data.error, 'error');
    else if (data.fatal)
      $.miniNoty('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + data.fatal, 'error');
    else if (data.success) {
      $.miniNoty('<i class="fa fa-check-circle" aria-hidden="true"></i> ' + data.success, 'success');

      if (callback)
        callback();
    }
    else if (data.info) {
      $.miniNoty('<i class="fa fa-info-circle" aria-hidden="true"></i> ' + data.info, 'normal');

      if (callback)
        callback();
    }
    else if (callback)
      callback(data);

    endLoading();
  });
};

var addGet = function (get) {
  for (var key in get) {
    window.get[key] = get[key];
  }
};

var changeMode = function (mode) {
  window.get.mode = mode;
  generate();
};

var promptPrevoir = function () {
  $('#prevoir').css('visibility', 'visible');
  $('#prevoir').css('opacity', '1');
  $('#zonePopup').addClass('focused');

  focus();
};

var generatePrevoir = function() {
  loading();
  window.get.mode = 'prevoir';

  $.ajax({
    url: '/emploidutemps/ressources/php/prevoir.php',
    type: 'POST',
    data: {
      'text': $('#prevoirText').val(),
    },
    complete: function () {
      endLoading();
      closePopup();

      generatePrevoirPossibility();
    }
  });

  if (text.value.length > 0) {
    text.oninput();
  }
};

var generateSubMenuPrevoir = function () {
  $('#prevoirStatText').html(prevoirValidPossibilities.length + ' prédiction' + (prevoirValidPossibilities.length ? 's:' : ':'));
  $('#affichage_prevoir').css('display', 'block');
  $('#prevoirPossibilities').empty();

  $.each(prevoirValidPossibilities.slice(0, 50), function (index, possibility) {
    $('<div></div>').attr('id', 'printed-' + index).append($('<button></button>').html('<span>Cal n°' + possibility.num + ' - ' + possibility.free_days + ' jours libres</span>').css('color', prevoirIndex === index ? '#228822' : '').on('click', function () {
      prevoirIndex = index;

      generatePrevoirPossibility();
    })).appendTo($('#prevoirPossibilities'));
  });
};

var generatePrevoirPossibility = function () {
  generateTitle("Possibilité n°" + prevoirValidPossibilities[prevoirIndex].num + " - " + prevoirValidPossibilities[prevoirIndex].free_days + " jours libres");
  
  generateSubMenuPrevoir();

  generateCalendar([{
    data: prevoirValidPossibilities[prevoirIndex].map(parsePrevoirPossibility),
    type: 'prevoir',
  }], 1);
};

var parsePrevoirPossibility = function (course) {
  return {
    day: course.day,
    duration: course.he - course.hs,
    frequency: 1,
    groupe: '',
    startTime: course.hs,
    subject: course.uv,
    timeText: course.h_start + "-" + course.h_end,
    location: course.uv,
    type: course.type === 'Cours' ? 'C' : (course.type === 'TD' ? 'D' : 'T'),
  };
};

var generate = function (silentMode, callback) {
  if (window.get.mode === 'prevoir') {
    return;
  }
  
  $('#affichage_prevoir').css('display', 'none');

  console.time('generate');

  getRequest('calendar.php', window.get, function (data) {
    console.timeEnd('generate');
    console.log(data);

    if (silentMode) {
      if (window.task) {
        var tempId = window.task.id;
        window.task = null;
      }
    }
    else {
      closePopup();
      unFocus();
    }

    window.sessionLogin = data.infos.login;
    window.active = data.infos.active;
    window.colors = data.infos.colors;
    window.get = data.infos.get;
    window.sides = data.infos.sides;
    delete window.get.addActive;
    delete window.get.setActiveTabs;
    delete window.get.delActive;

    if (tempId)
      window.get.id = tempId;

    getRequest('groups.php', {
      'mode': 'get',
    }, function (groups) {
      generatePrinted(groups.groups);
    }, silentMode);
    generateTitle(data.title);
    generateWeeks(data.infos.week, data.infos.get.week);
    generateSubMenu(data.tabs, 'tab');
    generateSubMenu(data.groups, 'group');
    generateCalendar(data.tasks, data.infos.sides, data.infos.uvs, data.infos.daysInfo);
    generateMode();
    setCalendar();

    if (callback)
      callback();

    // Recharge l'affichage chaque minute pour les mises à jour
    window.clearInterval(window.reload);
    window.reload = window.setInterval(function () {
      generate(true);
    }, window.RELOAD_SEC * 1000);
  }, silentMode);
};

var loading = function () {
  $('<img>').attr('id', 'loading').attr('src', '/emploidutemps/ressources/img/loading.gif').appendTo($('#calendar-container'));
};

var endLoading = function () {
  $('#loading').remove();
};

var submited = function () {
  setTimeout(function () {
    if ($('.focusedInput').length != 0)
      $('.focusedInput').first().focus();

    if ($(".submitedInput").length != 0 && $(".submitedButton").length != 0)
      $(".submitedInput").keyup(function (event) {
        var code = event.keyCode || event.which;

        console.log($(".submitedInput:focus").parents().find('.submitedButton'))

        if (code == 13)
          $(".submitedInput:focus").parents().find('.submitedButton').first().click();
      });
  }, 100);
};

var setPopupButtons = function (enable) {
  $('#popup').find('button').each(function () {
    $(this).prop('disabled', !enable);
  });
}

var popup = function (popupHead, content, bgColor, fgColor) {
  window.click = true;
  $('#zonePopup').addClass('focused');
  $('#nav').removeClass('see');
  $('#parameters').removeClass('see');

  bgColor = bgColor || '#BBBBBB';
  fgColor = fgColor || '#000000';
  $('#popup').css('border', '5px SOLID' + bgColor);

  var div = $('<div></div>').attr('id', 'popupHead').css('border', '5px SOLID' + bgColor).css('background-color', bgColor).css('color', fgColor).append($('<b></b>').html(popupHead));
  $('#popup').empty().append(div).append(content.attr('id', 'popupContent'));
  $('#popup').css('visibility', 'visible');
  $('#popup').css('opacity', '1');

  submited();
};

var closePopup = function () {
  $('#popup').css('visibility', 'hidden');
  $('#popup').css('opacity', '0');

  if ($('#prevoir').css('visibility') === 'visible') {
    $('#mode_' + window.get.mode).click();
  }

  $('#prevoir').css('visibility', 'hidden');
  $('#prevoir').css('opacity', '0');

  $('#zonePopup').removeClass('focused');
  $('#zonePopup').removeClass('focused');
};

var unFocus = function () {
  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');

  $('#nav').removeClass('see');
  $('#parameters').removeClass('see');

  if (window.task != null)
    $('#' + window.task.id).click();
};

var disconnect = function () {
  popup('Déconnexion', $('<div></div>')
    .append($('<iframe></iframe>', {
      'frameborder': 0,
      'scrolling': 'no',
      'height': '100%',
      'width': '100%',
      'src': '/emploidutemps/disconnect.php'
    }).css('position', 'absolute'))
    .append($('<div></div>', {
      'border': '100%',
      'width': '100%',
      'class': 'optionCards'
    }).css('position', 'absolute').css('bottom', 0)
      .append($('<button></button>').html('<i class="fa fa-external-link" aria-hidden="true"></i> Portail des assos').on('click', function () { window.location = 'https://assos.utc.fr/'; }))
      .append($('<button></button>').html('<i class="fa fa-undo" aria-hidden="true"></i> Se reconnecter').on('click', function () { window.location.reload(); }))
      .append($('<button></button>').html('<i class="fa fa-external-link" aria-hidden="true"></i> ENT').on('click', function () { window.location = 'https://ent.utc.fr/'; }))
    )
  );

  $('#zoneFocus').css('visibility', 'hidden');
  $('#popup').css('height', '100%').css('overflow', 'hidden');
};


var help = function () {
  popup('Aide', $('<div></div>').addClass('centerCard').text('Rien de fait pour l\'instant'));
};


/* Groupes */

var addGroup = function () {
  var corps = $('<div></div>').addClass('centerCard');

  if ($('#group').length > 0) {
    var name = $('#group').val();

    getRequest('groups.php', {
      'mode': 'add',
      'group': name
    }, function () {
      generate();
    });
  }

  popup('Création d\'un nouveau groupe', corps.append(
    $('<div></div>').addClass('optionCards')
      .append($('<input /><br />').attr('id', 'group').addClass('focusedInput').addClass('submitedInput').css('flex', '1'))
      .append($('<button></button>').text('Créer').attr('onClick', 'addGroup()').addClass('submitedButton'))));
};

var delGroup = function (idGroup) {
  getRequest('groups.php', {
    'mode': 'del',
    'group': idGroup,
  }, function () {
    $('#group-' + idGroup).remove();
    closePopup();
  });
};

var seeGroup = function (group, edit) {
  getRequest('groups.php', {
    'mode': 'get',
    'group': group
  }, function (data) {
    console.log(data);
    var corps = $('<div></div>');
    var groupElements = {};

    if (data[group].type == 'asso') {
      var optionCards = $('<div></div>').addClass('optionCards');
      $('<button></button>').html('<i class="fa fa-external-link" aria-hidden="true"></i> Voir sur le portail').on('click', function () {
        window.document.location = 'https://assos.utc.fr/asso/' + group;
      }).appendTo(optionCards);
      $('<button></button>').html('<i class="fa fa-send" aria-hidden="true"></i> Envoyer un email à l\'asso').on('click', function () {
        window.document.location = 'mailto:' + group + '@assos.utc.fr';
      }).appendTo(optionCards);
      optionCards.appendTo(corps);
    }

    var div;
    $.each(data[group].subgroups, function (name, sub_group) {
      var subGroupElements = {};
      var actives = [];

      div = $('<div></div>').addClass('studentCards');

      if (sub_group.elements.length == 0) {
        $('<div></div>').addClass('subCard').attr('id', 'sub-' + name).css('color', (edit && sub_group.type == 'asso' ? '#FF0000' : '#000000'))
          .append($('<b></b>').text(sub_group.name))
          .append($('<button></button>').html('<i class="fa fa-' + (edit ? 'edit' : 'eye') + '"></i>').prop('disabled', (!edit || (edit && sub_group.type != 'custom'))).on('click', edit ? function () {
            setSubGroup(group, name, 'sub-' + name);
          } : function () { })).appendTo(corps);

        if (edit)
          return div.addClass('optionCards').append($('<button></button>').html('<i class="fa fa-remove" aria-hidden="true"></i> Supprimer ce sous-groupe vide').prop('disabled', sub_group.type != 'custom').attr('onClick', 'delSubGroup("' + group + '", "' + name + '")')).appendTo(corps);
        else
          return div.append(div.addClass('voidCard').css('margin-top', 0).text(name == 'resps' ? 'Aucun responsable' : (name == 'members' ? 'Aucun membre' : (name == 'admins' ? 'Aucun bureau (il faudrait que quelqu\'un fasse la demande de passation)' : 'Sous-groupe vide')))).appendTo(corps);
      }

      $.each(sub_group.elements, function (login, infos) {
        if (!edit) {
          groupElements[login] = infos.active;
          subGroupElements[login] = infos.active;
        }

        if (infos.uv == undefined) {
          infos.login = login;
          if (edit)
            generateStudentCard(infos, undefined, group, name, sub_group.type).appendTo(div);
          else
            generateStudentCard(infos, undefined, window.get.mode == 'organiser' ? group : undefined).appendTo(div);
        }
        else {
          if (edit)
            generateUVCard(infos, undefined, undefined, group, name, sub_group.type).appendTo(div);
          else
            generateUVCard(infos, undefined, undefined, window.get.mode == 'organiser' ? group : undefined).appendTo(div);
        }
      });

      $.each(subGroupElements, function (login, active) {
        if (active == sub_group.active)
          actives.push(login);
      });

      var active = sub_group.active && window.get.mode == 'organiser' ? 'delActive' : 'addActive';
      console.log(actives)
      $('<div></div>').addClass('subCard').attr('id', 'sub-' + name).css('color', (edit && sub_group.type == 'asso' ? '#FF0000' : '#000000'))
        .append($('<b></b>').text(sub_group.name))
        .append($('<button></button>').html('<i class="fa fa-' + (edit ? 'edit' : (sub_group.active && window.get.mode == 'organiser' ? 'eye-slash' : 'eye')) + '"></i>').prop('disabled', (!edit && (Object.keys(actives).length == 0 || (Object.keys(actives).length == 1 && actives[0] == window.sessionLogin))) || (edit && sub_group.type != 'custom')).on('click', edit ? function () {
          setSubGroup(group, name, 'sub-' + name);
        } : function () {
          window.get = {
            'mode': 'organiser'
          };
          window.get[active] = actives;

          loading();
          generate(true, function () {
            seeGroup(group);
          });
        })).appendTo(corps);
      div.appendTo(corps);
    });

    if (data[group].subgroups.length == 0) {
      if (edit)
        corps = $('<div></div>').append($('<div></div>').addClass('optionCards').append($('<button></button>').html('<i class="fa fa-remove" aria-hidden="true"></i> Supprimer ce groupe vide').prop('disabled', data[group].type != 'custom').attr('onClick', 'delGroup("' + group + '")')));
      else
        corps = $('<div></div>').append($('<div></div>').addClass('voidCard').text('Vide'));
    }

    var optionCards = $('<div></div>').addClass('optionCards');
    if (data[group].type != 'others')
      $('<button></button>').html('<i class="fa fa-plus" aria-hidden="true"></i> Créer un sous-groupe').attr('id', 'create-subgroup').attr('onClick', 'addSubGroup("' + group + '", "' + data[group].name + '", ' + edit + ')').appendTo(optionCards);

    if (edit)
      $('<button></button>').html('<i class="fa fa-eye" aria-hidden="true"></i> Afficher le groupe').attr('onClick', 'seeGroup("' + group + '")').appendTo(optionCards);
    else
      $('<button></button>').html('<i class="fa fa-gear" aria-hidden="true"></i> Paramétrer le groupe').attr('onClick', 'seeGroup("' + group + '", true)').appendTo(optionCards);
    optionCards.appendTo(corps);

    var actives = [];
    $.each(groupElements, function (login, active) {
      if (active == data[group].active)
        actives.push(login);
    });

    var active = data[group].active && window.get.mode == 'organiser' ? 'delActive' : 'addActive';
    popup(data[group].name, corps);
    $('#popupHead').append($('<button></button>').html('<i class="fa fa-' + (edit ? 'edit' : (data[group].active && window.get.mode == 'organiser' ? 'eye-slash' : 'eye')) + '"></i>').prop('disabled', (!edit && Object.keys(actives).length == 0) || (edit && data[group].type != 'custom')).on('click', edit ? function () {
      setGroup(group, 'popupHead');
    } : function () {
      window.get = {
        'mode': 'organiser'
      };
      window.get[active] = actives;

      loading();
      generate(true, function () {
        seeGroup(group);
      });
    }));
  });
};

var setGroup = function (idGroup, id) {
  setPopupButtons(false);

  $('#' + id + ' b').replaceWith($('<input />').addClass('focusedInput').addClass('submitedInput').val($('#' + id + ' b').text()).attr('placeholder', $('#' + id + ' b').text()));
  $('#' + id + ' button').first().replaceWith($('<button></button>').html('<i class="fa fa-send"></i>').addClass('submitedButton').on('click', function () {
    setPopupButtons(true);
    getRequest('groups.php', {
      'mode': 'set',
      'group': idGroup,
      'info': $('#' + id + ' input').val()
    }, function () {
      generate(true, function () {
        $('#' + id + ' input').replaceWith($('<b></b>').text($('#' + id + ' input').val()));
        $('#' + id + ' button').first().replaceWith($('<button></button>').html('<i class="fa fa-edit"></i>').on('click', function () {
          setGroup(idGroup, id);
        }));
      });
    });
  }));

  submited();
};

var addSubGroup = function (idGroup, group, edit) {
  setPopupButtons(false);

  $('#create-subgroup').replaceWith($('<button></button>').html('<i class="fa fa-undo"></i> Annuler la création').on('click', function () {
    seeGroup(idGroup, edit);
  }));

  $('<div></div>').addClass('subCard').attr('id', 'sub-create')
    .append($('<input />').addClass('focusedInput').addClass('submitedInput'))
    .append($('<button></button>').addClass('submitedButton').html('<i class="fa fa-send"></i>').on('click', function () {
      getRequest('groups.php', {
        'mode': 'add',
        'group': idGroup,
        'sub_group': $('#sub-create input').last().val()
      }, function () {
        generate(true, function () {
          seeGroup(idGroup, edit);
        });
      });
    })).insertBefore($('.optionCards'));

  submited();
};

var delSubGroup = function (idGroup, idSubGroup) {
  getRequest('groups.php', {
    'mode': 'del',
    'group': idGroup,
    'sub_group': idSubGroup,
  }, function () {
    generate(true, function () {
      seeGroup(idGroup, true);
    });
  });
};

var setSubGroup = function (idGroup, idSubGroup, id) {
  setPopupButtons(false);

  $('#' + id + ' b').replaceWith($('<input />').addClass('focusedInput').addClass('submitedInput').val($('#' + id + ' b').text()).attr('placeholder', $('#' + id + ' b').text()));
  $('#' + id + ' button').first().replaceWith($('<button></button>').html('<i class="fa fa-send"></i>').addClass('submitedButton').on('click', function () {
    getRequest('groups.php', {
      'mode': 'set',
      'group': idGroup,
      'sub_group': idSubGroup,
      'info': $('#' + id + ' input').val()
    }, function () {
      $('#' + id + ' input').replaceWith($('<b></b>').text($('#' + id + ' input').val()));
      $('#' + id + ' button').first().replaceWith($('<button></button>').html('<i class="fa fa-edit"></i>').on('click', function () {
        generate(true, function () {
          setSubGroup(idGroup, idSubGroup, id);
        });
      }));

      setPopupButtons(true);
    });
  }));

  submited();
};

var addToGroup = function (element, text) {
  getRequest('groups.php', {
    'mode': 'get',
  }, function (data) {
    console.log(Object.keys(data.groups).length)
    if (Object.keys(data.groups).length == 1) {
      addGroup();
      $.miniNoty('<i class="fa fa-info-circle" aria-hidden="true"></i> Il faut créer un groupe avant de pouvoir ajouter ' + text, 'normal');

      return;
    }

    console.log(data);
    var corps = $('<div></div>').addClass('centerCard').text('Groupe: ');

    var groups = $('<select></select>').attr('id', 'selectedGroup').on('change', function () {
      $($(this).data('selected')).css('display', 'none');
      $('#sub_' + this.value).css('display', 'inline');
      $(this).data('selected', '#sub_' + this.value);

      if ($('#sub_' + this.value).prop('disabled')) {
        $('#sub0').attr('disabled', true);
        $('#sub1').click();

        if ($('#sub_' + this.value + ' option').length > 1)
          $.miniNoty('<i class="fa fa-info-circle" aria-hidden="true"></i> Il n\'est pas possible d\'ajouter ' + text + ' dans un sous-groupe généré automatiquement pour la gestion des groupes associations', 'normal');
      }
      else
        $('#sub0').attr('disabled', false).click();
    });
    var subgroups = $('<div></div>');
    $.each(data.groups, function (name, group) {
      if (name == 'others')
        return;

      $('<option></option>').attr('value', name).text(group.name).appendTo(groups);

      var hidden = $('<select></select>').css('width', '25%').css('min-width', '350px').css('display', 'none').prop('disabled', true).attr('id', 'sub_' + name);
      var option;
      $.each(group.subgroups, function (subname, subgroup) {
        option = $('<option></option>').attr('value', subname).text(subgroup.name);

        if (subgroup.type == 'asso')
          option.prop('disabled', true);
        else
          hidden.prop('disabled', false);

        option.appendTo(hidden);
      });

      if (hidden.prop('disabled'))
        $('<option></option>').text('Aucun sous-groupe disponible').prependTo(hidden);

      hidden.appendTo(subgroups);
    });

    $(groups).data('selected', '#' + $(subgroups).children().first().css('display', 'inline').attr('id'));

    groups.appendTo(corps);
    $('<span></span><br />').text('Choisir un sous-groupe existant: ').prependTo(subgroups);
    $('<input />').attr('type', 'radio').attr('id', 'sub0').attr('name', 'sub').prop("checked", true).val(0).prependTo(subgroups);
    $('<br />').prependTo(subgroups);
    subgroups.attr('onClick', '$("#sub0").prop("checked", !$("#sub0").prop("disabled"))').appendTo(corps);
    $('<input></input>').attr('type', 'radio').attr('id', 'sub1').attr('name', 'sub').val(1).appendTo(corps);
    $('<span></span><br />').text('Créer un sous-groupe: ').appendTo(corps);
    $('<input></input><br /><br />').css('width', '25%').css('min-width', '350px').attr('id', 'newSubGroup').attr('onClick', '$("#sub1").prop("checked", true);').attr('placeholder', 'Nouveau sous-groupe').appendTo(corps);
    $('<span></span><br />').text('Description: ').appendTo(corps);
    $('<input></input><br /><br />').css('width', '25%').css('min-width', '350px').attr('id', 'info').attr('placeholder', 'Description').appendTo(corps);
    $('<button></button>').html('<i class="fa fa-plus" aria-hidden="true"></i> Ajouter').on('click', function () {
      var group = $('#selectedGroup').val();
      var subGroup = $('#sub_' + group).val();
      var createSubGroup = $('input[name=sub]:checked').val();
      var info = $('#info').val();

      if (createSubGroup == 1)
        subGroup = $('#newSubGroup').val();

      getRequest('groups.php', {
        'mode': 'add',
        'group': group,
        'sub_group': subGroup,
        'createSubGroup': createSubGroup,
        'element': element,
        'info': info
      }, function () {
        closePopup();
      });
    }).appendTo(corps);
    popup('Ajouter ' + text, corps);

    if (Object.keys(data.groups)[0]) {
      if ($('#sub_' + Object.keys(data.groups)[0]).prop('disabled')) {
        $('#sub0').prop('disabled', true);
        $('#sub1').click();
        $
      }
    }
  });
};

var delFromGroup = function (idGroup, idSubGroup, element) {
  getRequest('groups.php', {
    'mode': 'del',
    'group': idGroup,
    'sub_group': idSubGroup,
    'element': element,
  }, function () {
    seeGroup(idGroup, true);
  });
};

var setToGroup = function (idGroup, idSubGroup, element, text, id, semester, noInfo) {
  setPopupButtons(false);

  var text = $('#' + id + ' .infosCard span').text();
  $('#' + id + ' .infosCard span').replaceWith($('<input />').addClass('focusedInput').addClass('submitedInput').val(!noInfo ? text : '').attr('placeholder', !noInfo ? text : 'Description'));
  $('#' + id + ' .optionsCard button').first().replaceWith($('<button></button>').html('<i class="fa fa-send"></i>').addClass('submitedButton').on('click', function () {
    getRequest('groups.php', {
      'mode': 'set',
      'group': idGroup,
      'sub_group': idSubGroup,
      'element': element,
      'info': $('#' + id + ' .infosCard input').val()
    }, function () {
      setPopupButtons(false);

      var text = $('#' + id + ' .infosCard input').val();
      $('#group-' + idGroup + ' button').val($('#group-' + idGroup + ' button').val().replace(element, text));
      $('#' + id + ' .infosCard input').replaceWith($('<span></span>').text(text && text != '' ? text : (semester ? semester + ' - ' + element : '')));
      $('#' + id + ' .optionsCard button').first().replaceWith($('<button></button>').html('<i class="fa fa-edit"></i>').on('click', function () {
        setToGroup(idGroup, idSubGroup, element, text, id, semester, !(text && text != ''));
      }));

      setPopupButtons(true);
    });
  }));

  submited();
};

var addActive = function (list, callback) {
  if (typeof list === 'string')
    list = [list];

  window.get.addActive = list;
  delete window.get.delActive;

  loading();
  generate(true, callback);
};

var delActive = function (list, callback) {
  if (typeof list === 'string')
    list = [list];

  window.get.delActive = list;
  delete window.get.addActive;

  loading();
  generate(true, callback);
};

var seeStudent = function (login, info) {
  if (window.get.mode == 'comparer' || window.get.mode == 'modifier')
    window.get = { 'mode': 'comparer' };
  else
    window.get = { 'mode': 'classique' };

  window.get.login = login;


  if (info != undefined)
    window.get.info = info;

  generate();
};

var seeUV = function (uv) {
  if (window.get.mode == 'comparer')
    window.get = { 'mode': 'comparer' };
  else if (window.get.mode == 'modifier')
    window.get = { 'mode': 'modifier' };
  else if (window.get.mode == 'semaine')
    window.get = { 'mode': 'semaine' };
  else
    window.get = { 'mode': 'classique' };

  window.get.uv = uv;

  generate();
};

var seeUVInformations = function (task) {
  getRequest('lists.php', {
    'idUV': task.idUV
  }, function (data) {
    var popupHead = 'Liste des ' + task.nbrEtu + ' étudiants en ' + (task.type == 'D' ? 'TD' : (task.type == 'T' ? 'TP' : 'cours')) + ' de ' + task.subject + ' de ' + task.timeText.replace('-', ' à ');
    var corps = $('<div></div>');
    var optionCards = $('<div></div>').addClass('optionCards');
    $('<button></button>').html('<i class="fa fa-external-link" aria-hidden="true"></i> Moodle').attr('onClick', 'uvMoodle("' + task.subject + '")').appendTo(optionCards);
    $('<button></button>').html('<i class="fa fa-external-link" aria-hidden="true"></i> UVWeb').attr('onClick', 'uvWeb("' + task.subject + '")').appendTo(optionCards);
    optionCards.appendTo(corps);
    var div = $('<div></div>').addClass('studentCards');
    data.students.forEach(function (student) {
      generateStudentCard(student).appendTo(div);
    });
    div.appendTo(corps);
    popup(popupHead, corps, task.bgColor, task.fgColor);
  });
};

var seeOthers = function (uv, type, idUV, id) {
  window.get = {
    'mode': 'modifier',
    'uv': uv,
    'type': type,
    'idUV': idUV,
    'id': id
  };

  generate();
};

var uvWeb = function (uv) {
  window.open('https://assos.utc.fr/uvweb/uv/' + uv);
};

var uvMoodle = function (uv) {
  window.open('http://moodle.utc.fr/course/search.php?search=' + uv);
};

var changeColor = function (id, color, name) {
  var get = {
    'color': color.substr(1)
  };
  get[name ? name : 'idUV'] = id;
  getRequest('parameters.php', get, function () {
    generate();
  });
};

var changeStatus = function (status) {
  getRequest('parameters.php', {
    'status': status
  }, function () {
    setTimeout(function () {
      window.location.reload();
    }, 1000);
  });
};


/* Trombi */

var startSearch = function () {
  popup(
    $('<div></div>')
      .append($('<div></div>').text('Chercher un étudiant ou une UV pour l\'ajouter'))
      .append($('<input />').attr('id', 'addTabText').attr('onInput', 'checkSearch(this.value)'))
      .append($('<button></button>').attr('id', 'searchButton').attr('onClick', 'printSearch()').text('Chercher')).html(),
    $('<div></div>')
      .append($('<div></div>').addClass('studentCardsText'))
      .append($('<div></div>').addClass('studentCards'))
      .append($('<div></div>').addClass('uvCardsText'))
      .append($('<div></div>').addClass('uvCards'))
      .append($('<div></div>').addClass('cardButtons')));

  $("#addTabText").keyup(function (event) {
    if (event.keyCode == 13) {
      if (!$('#searchButton').prop('disabled'))
        printSearch();
    }
  });

  $('#searchButton').prop('disabled', true);

  getRequest('suggestions.php', {}, function(data) {
    $('.studentCards').empty();
    $('.studentCardsText').text(data.length + ' étudiant' + (data.length > 1 ? 's' : '') + ' suggéré' + (data.length > 1 ? 's' : ''));
    data.forEach(function (student) {
      $('.studentCards').append(generateStudentCard(student, 'Ajouté.e depuis le trombi'));
    });

    $('#popup').scrollTop(0);
  });

  setTimeout(function () { // La fonction ne marche pas sans Timeout..
    $("#addTabText").focus();
  }, 100);
};

var checkSearch = function (input) {
  var text = input.replace(/\s+/g, ' ').replace(/^\s+/g, '').replace(/(\s.+)\s$/g, '$1');
  $('#addTabText').val(text);

  $('#searchButton').prop('disabled', text.length == 0);
};

var printSearch = function (begin) {
  if (begin == undefined || begin == '')
    begin = 0;
  $('#searchButton').prop('disabled', true);
  getRequest('lists.php', {
    'search': $('#addTabText').val().replace(/^\s+|\s+$/g, '').replace(/_/g, '').replace(/\s/, '%\\_%\\'),
    'begin': begin,
    'nbr': 50
  }, function (data) {
    $('.studentCards').empty();
    $('.studentCardsText').text(data.students.length + ' étudiant' + (data.students.length > 1 ? 's' : '') + ' affiché' + (data.students.length > 1 ? 's' : ''));
    data.students.forEach(function (student) {
      $('.studentCards').append(generateStudentCard(student, 'Ajouté.e depuis le trombi'));
    });

    $('.uvCards').empty();
    $('.uvCardsText').text(data.uvs.length + ' UV' + (data.uvs.length > 1 ? 's' : '') + ' affichée' + (data.uvs.length > 1 ? 's' : ''));
    data.uvs.forEach(function (uv) {
      $('.uvCards').append(generateUVCard(uv, data.infos.uvs));
    });

    $('.cardButtons').empty();
    if (data.infos.begin != 0 || data.infos.more)
      $('.cardButtons')
        .append($('<button></button>').text('Précédent').attr('onClick', 'printSearch(' + (data.infos.begin - 50) + ')').prop('disabled', data.infos.begin == 0))
        .append($('<button></button>').text('Suivant').attr('onClick', 'printSearch(' + (data.infos.begin + 50) + ')').prop('disabled', !data.infos.more));

    $('#popup').scrollTop(0);
  });
};


/* Outils */

var exportDownload = function (type) {
  if (type) {
    if (type == 'ical') {
      popup('Obtenir sous format iCal', $('<div></div>').addClass('centerCard')
        .append($('<input></input>').attr('type', 'radio').attr('name', 'alarm').attr('id', 'withAlarm').prop('checked', true).on('click', function () { $('#alarm').prop('disabled', false); }))
        .append($('<label></label><br />').attr('for', 'withAlarm').text('Activer un rappel ').append($('<input />').attr('type', 'number').attr('min', 0).attr('max', 1440).attr('step', 1).attr('placeholder', 0).attr('id', 'alarm').addClass('focusedInput').addClass('submitedInput').on('click', function () { $('#alarm').prop('disabled', false); })).append(' min avant l\'évnènement'))
        .append($('<input></input>').attr('type', 'radio').attr('name', 'alarm').attr('id', 'withoutAlarm').on('click', function () { $('#alarm').val(0).prop('disabled', true); }))
        .append($('<label></label><br /><br />').attr('for', 'withoutAlarm').text('N\'activer aucun rappel'))
        .append('Du ')
        .append($('<input />').attr('type', 'date').val(new Date().toJSON().slice(0, 10)).attr('id', 'begin'))
        .append(' Au ')
        .append($('<input /><br /><br />').attr('type', 'date').attr('placeholder', 'dernier évènement').attr('id', 'end'))
        .append($('<button></button>').text('Générer et télécharger mon emploi du temps').on('click', function () {
          var begin = new Date($('#begin').val());
          var end = new Date($('#end').val());

          if (begin.getTime() > end.getTime())
            $.miniNoty('<i class="fa fa-exclamation-circle" aria-hidden="true"></i> Impossible de créer un fichier d\'export avec une date de début finissant après la date de fin', 'error');
          else
            window.location.href = '/emploidutemps/ressources/php/exports.php?mode=all&begin=' + $('#begin').val() + ($('#end').val() == '' ? '' : '&end=' + $('#end').val()) + ($('#alarm').val() == '' ? '' : '&alarm=' + $('#alarm').val());
        }))
      );
    }
    else if (type == 'pdf') {
      popup('Obtenir sous format PDF', $('<div></div>').addClass('centerCard')
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck0').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck0').text('Afficher le lundi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck1').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck1').text('Afficher le mardi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck2').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck2').text('Afficher le mercredi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck3').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck3').text('Afficher le jeudi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck4').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck4').text('Afficher le vendredi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck5').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck5').text('Afficher le samedi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck6').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck6').text('Afficher le dimanche'))
        .append($('<br />'))
        .append($('<input></input>').addClass('focusedInput').addClass('submitedInput').attr('id', 'pdfTitle').val($('#title').text()))
        .append($('<br />'))
        .append($('<input></input>').addClass('submitedInput').attr('id', 'pdfName').val('edt_' + window.get.mode + '_' + (window.get.login ? window.get.login : window.sessionLogin)))
        .append($('<br /><br />'))
        .append($('<button></button>').addClass('submitedButton').text('Générer et télécharger mon emploi du temps').attr('onClick', 'getPDF()'))
      );
    }
    else if (type == 'img') {
      popup('Obtenir sous format image', $('<div></div>').addClass('centerCard')
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck0').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck0').text('Afficher le lundi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck1').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck1').text('Afficher le mardi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck2').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck2').text('Afficher le mercredi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck3').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck3').text('Afficher le jeudi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck4').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck4').text('Afficher le vendredi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck5').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck5').text('Afficher le samedi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck6').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck6').text('Afficher le dimanche'))
        .append($('<select></select>').attr('id', 'imgType')
          .append($('<option></option>').val('png').text('PNG'))
          .append($('<option></option>').val('jpeg').text('JPEG')))
        .append($('<div></div>').attr('id', 'generatedImg'))
        .append($('<button></button>').addClass('submitedButton').text('Générer mon emploi du temps').attr('onClick', 'getImg()'))
      );
    }
  }
  else
    popup('Exporter/Télécharger', $('<div></div>').addClass('parameters')
      .append($('<button></button>').on('click', function () { exportDownload('ical'); }).text('Obtenir son calendrier pour agenda informatique sous format iCal (.ics)'))
      .append($('<button></button>').on('click', function () { exportDownload('pdf'); }).text('Obtenir son calendrier sous format PDF (.pdf)'))
      .append($('<button></button>').on('click', function () { exportDownload('img'); }).text('Obtenir son calendrier sous format image (.png/.jpg)'))
      .append($('<button></button>').on('click', function () { window.location.href = 'http://wwwetu.utc.fr/sme/EDT/' + window.sessionLogin + '.edt'; }).text('Obtenir son calendrier sous format SME (mail reçu)'))
      .append($('<button></button>').on('click', function () { window.location.href = '/emploidutemps/ressources/pdf/alternances.pdf' }).text('Télécharger le calendrier des alternances'))
      .append($('<button></button>').on('click', function () { window.location.href = '/emploidutemps/ressources/pdf/infosRentree.pdf' }).text('Télécharger l\'info rentrée'))
    );

  /*  <div onClick="parameters()" style="cursor: pointer" id="popupHead">Exporter/Télécharger</div>
      <div class="parameters" style="text-align: center;">
        <button onClick="parameters(\'ical\');">Obtenir son calendrier sous format iCal (.ics)</button>
        <button onClick="parameters(\'pdf\');">Obtenir son calendrier sous format PDF (.pdf)</button>
        <button onClick="parameters(\'img\');">Obtenir son calendrier sous format image (.png/jpg)</button>
        <button onClick="window.location.href = \'http://wwwetu.utc.fr/sme/EDT/', $_SESSION['login'], '.edt\';"></button>
        <button onClick="window.location.href = \'/emploidutemps/ressources/pdf/alternances.pdf\';"></button>
        <button onClick="window.location.href = \'/emploidutemps/ressources/pdf/infosRentree.pdf\';"></button>
      </div>*/
};

var getImg = function () {
  var headers = $('.calendar-headers div');
  var days = $('.calendar-main-body .days');
  var length = window.headers.length;
  var displays = [];
  var hidden = 0;
  var type = $('#imgType').val();
  doc = new jsPDF('l', 'mm', [297, 210]);

  for (var i = 0; i < length; i++) {
    displays[i] = $(headers[i]).css('display');

    if ($('#imgCheck' + i).prop('checked')) {
      $(headers[i]).css('display', 'block');
      $(days[i * window.sides]).css('display', 'block');

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', 'block');
    }
    else {
      $(headers[i]).css('display', 'none');
      $(days[i * window.sides]).css('display', 'none');

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', 'none');

      hidden += 1;
    }
  }

  var calendar = $('#calendar-container');
  calendar.css('width', (1028 - (hidden * 138)) + 'px').addClass('calendar-exported');

  html2canvas(calendar[0], {
    onrendered: function (canvas) {
      if (type == 'jpeg') {
        var ctx = canvas.getContext('2d')
        var imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var data = imgData.data;

        for (var i = 0; i < data.length; i += 4) {
          if (data[i + 3] < 255) {
            data[i] = 255;
            data[i + 1] = 255;
            data[i + 2] = 255;
            data[i + 3] = 255;
          }
        }

        ctx.putImageData(imgData, 0, 0);
      }

      for (var i = 0; i < length; i++) {
        $(headers[i]).css('display', displays[i]);
        $(days[i * window.sides]).css('display', displays[i]);

        if (window.sides == 2)
          $(days[(i * window.sides) + 1]).css('display', displays[i]);
      }

      calendar.removeClass('calendar-exported');
      $('#generatedImg').html('<img src="' + canvas.toDataURL('image/' + type || 'png', 1.0) + '">');
      setCalendar(window.focusedDay);
    }
  });
};

var getPDF = function () {
  var headers = $('.calendar-headers div');
  var days = $('.calendar-main-body .days');
  var length = window.headers.length;
  var displays = [];
  var hidden = 0;
  doc = new jsPDF('l', 'mm', [297, 210]);

  doc.text($('#pdfTitle').val(), 148, 14, null, null, 'center');
  for (var i = 0; i < length; i++) {
    displays[i] = $(headers[i]).css('display');

    if ($('#pdfCheck' + i).prop('checked')) {
      $(headers[i]).css('display', 'block');
      $(days[i * window.sides]).css('display', 'block');

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', 'block');
    }
    else {
      $(headers[i]).css('display', 'none');
      $(days[i * window.sides]).css('display', 'none');

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', 'none');

      hidden += 1;
    }
  }

  var calendar = $('#calendar-container');
  calendar.css('width', 1032 + 'px').addClass('calendar-exported');

  html2canvas(calendar[0], {
    onrendered: function (canvas) {
      doc.addImage(canvas.toDataURL('image/png', 1.0), 'PNG', 12 + (hidden * 18), 26);

      for (var i = 0; i < length; i++) {
        $(headers[i]).css('display', displays[i]);
        $(days[i * window.sides]).css('display', displays[i]);

        if (window.sides == 2)
          $(days[(i * window.sides) + 1]).css('display', displays[i]);
      }

      calendar.removeClass('calendar-exported');
      setCalendar(window.focusedDay);
      doc.save($('#pdfName').val() + '.pdf');
    }
  });
};



/* Evenements */

var createEvenement = function (day, begin, end, subject, description, location, type, idEvent) {
  var date = new Date(window.get.week);
  var now = new Date();

  if (day === undefined) // day peut valoir 0
    day = (now.getDay() + 6) % 7;

  date.setDate(date.getDate() + day);

  if (!begin)
    begin = (now.getHours() + 1) + ':00';
  else if (!subject)
    begin = Math.floor(begin) + (begin - Math.floor(begin) == 0.5 ? ':30' : ':00');

  if (!end)
    end = (now.getHours(begin) + 2) + ':00';
  else if (!subject)
    end = Math.floor(end) + (end - Math.floor(end) == 0.5 ? ':30' : ':00');

  popup((subject ? 'Modification' : 'Création') + ' d\'un évènement', $('<div></div>').addClass('centerCard').text('Date: ')
    .append($('<input></input>', {
      'id': 'date',
      'type': 'date',
      'val': date.toLocaleString().split(' ')[0]
    }))
    .append('<br />Début: ')
    .append($('<input></input>', {
      'id': 'begin',
      'type': 'time',
      'data-time-format': 'H:i',
      'val': begin
    }))
    .append('<br />Fin: ')
    .append($('<input></input>', {
      'id': 'end',
      'type': 'time',
      'data-time-format': 'H:i',
      'val': end
    }))
    .append('<br /><br />Sujet: ')
    .append($('<input></input>', {
      'id': 'subject',
      'placeholder': 'Nom de l\'évènement',
      'val': subject
    }))
    .append('<br />Description: ')
    .append($('<textarea></textarea>', {
      'id': 'description',
      'placeholder': 'Description de l\'évènement',
      'val': description
    }))
    .append('<br />Lieu: ')
    .append($('<input></input>', {
      'id': 'location',
      'placeholder': 'Lieu de l\'évènement',
      'val': location
    }))
    .append('<br /><br />Type d\'évènement: ')
    .append($('<select></select>', {
      'id': 'type',
    })
      .append($('<option></option>', {
        'val': 'event',
        'text': 'Evènement personnel'
      }))
      .append($('<option></option>', {
        'val': 'meeting',
        'text': 'Réunion personnelle'
      }))
      .append($('<option></option>', {
        'val': 'event_group',
        'text': 'Evènement groupé',
        'disabled': true
      }))
      .append($('<option></option>', {
        'val': 'meeting_group',
        'text': 'Réunion groupée',
        'disabled': true
      }))
      .append($('<option></option>', {
        'val': 'event_asso',
        'text': 'Evènement associatif',
        'disabled': true
      }))
      .append($('<option></option>', {
        'val': 'meeting_asso',
        'text': 'Réunion associative',
        'disabled': true
      }))
    )
    .append($('<br /><br />'))
    .append($('<input></input>', {
      'id': 'sendMail',
      'type': 'checkbox',
      'val': 'Créer l\'évènement',
      'checked': true
    }))
    .append($('<label></label>', {
      'for': 'sendMail',
      'text': 'Envoyer un mail'
    }))
    .append($('<br />'))
    .append($('<button></button>', {
      'id': 'sendButton',
      'class': 'submitedButton',
      'html': (subject ? '<i class="fa fa-edit" aria-hidden="true"></i> Modifier' : '<i class="fa fa-plus" aria-hidden="true"></i> Créer') + ' l\'évènement'
    }).on('click', function () {
      var type = $('#type').val();
      var creator_asso = null;
      var invited = null;

      if (type == 'groupedEvent' || type == 'groupedMeeting')
        invited = window.active;
      else if (type == 'assoEvent' || type == 'assoMeeting') {
        creator_asso == $('#asso').val();
      }

      getRequest('events.php', {
        'mode': (subject ? 'edit' : 'add'),
        'idEvent': idEvent,
        'date': $('#date').datepicker('getDate').toISOString().slice(0, 10),
        'begin': $('#begin').val(),
        'end': $('#end').val(),
        'sendMail': $('#sendMail').prop('checked'),
        'subject': $('#subject').val(),
        'description': $('#description').val(),
        'location': $('#location').val(),
        'type': type,
        'creator_asso': creator_asso,
        'invited': invited,
      }, function () {
        generate();
      })
    }))
  );

  if (type) {
    $('#type').val(type).prop('disabled', true);
    $('#sendMail').prop('disabled', true);

    $('<button></button>', {
      'class': 'submitedButton',
      'html': '<i class="fa fa-times" aria-hidden="true"></i> Supprimer l\'évènement'
    }).on('click', function () {
      deleteEvenement(idEvent);
    }).insertBefore($('#sendButton'));
  }

  $("#date").datepicker($.datepicker.regional["fr"]);
  $('#begin').timepicker();
  $('#end').timepicker();
};

var deleteEvenement = function (idEvent) {
  getRequest('events.php', {
    'mode': 'del',
    'idEvent': idEvent
  }, function () {
    generate();
  });
};

var seeEventInformations = function (task) {
  var type;

  if (task.type == 'event')
    type = 'Evènement';
  else if (task.type == 'meeting')
    type = 'Réunion';

  popup(type + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.timeText.replace('-', ' à '), $('<div></div>').addClass('centerCard')
    .append($('<div></div>').text('Sujet:').css('text-decoration', 'underline'))
    .append($('<div></div>').text(task.subject))
    .append($('<br />'))
    .append($('<div></div>').text('Description:').css('text-decoration', 'underline'))
    .append($('<div></div>').text(task.description || 'Aucune description'))
    .append($('<br />'))
    .append($('<div></div>').text('Lieu:').css('text-decoration', 'underline'))
    .append($('<div></div>').text(task.location || 'Aucun lieu défini'))
    .append($('<br />'))
    .append($('<div></div>').text(task.note ? (task.creator_asso ? 'Evènement créé pour ' : '') + task.note : 'Evènement créé par moi-même'))
    .append($('<br />'))
    .append($('<button></button>').html('<i class="fa fa-download" aria-hidden="true"></i> Télécharger sous format .ics').on('click', function () {
      window.document.location = '/emploidutemps/ressources/php/exports.php?idEvent=' + task.idEvent;
    }))
    .append($('<br />'))
    .append($('<button></button>').html('<i class="fa fa-edit" aria-hidden="true"></i> Modifier').prop('disabled', task.creator != window.sessionLogin).on('click', function () {
      createEvenement(task.day, task.timeText.split('-')[0], task.timeText.split('-')[1], task.subject, task.description, task.location, task.type, task.idEvent);
    }))
  );
}

/* Echanges */

var askExchange = function (idUV, idUV2) {
  getRequest('exchanges.php', {
    'mode': 'get',
    'idUV': idUV,
    'idUV2': idUV2
  }, function (data) {
    var task = data.uv;
    var toExchange = data.uv2;
    var corps = $('<div></div>').addClass('centerCard');

    if (data.sent.length == 0)
      corps.append($('<div></div>').append('Tu es le/la premier/ère à faire cette demande. Un mail sera envoyé à ceux qui possèdent le créneau pour leur signaler que tu viens de réaliser cette proposition.<br />'));
    else
      corps.append($('<div></div>').append(data.sent.length + (data.sent.length == 1 ? ' personne a déjà fait ' : ' personnes ont déjà fait ') + ' cette proposition. Tu devras attendre ton tour.<br />'));

    corps
      .append($('<div></div>').html('Après avoir effectué cette proposition, tu peux bien sûr l\'annuler avant celle-ci soit acceptée. Tu peux toujours aussi proposer d\'autres créneaux bien sûr.<br /><br />Tu dois maintenant écrire rapidement pourquoi tu souhaites effectuer cet échange:'))
      .append($('<textarea /><br /><br />').attr('id', 'note').addClass('focusedInput').addClass('submitedInput').css('width', '50%').attr('maxlength', 180).attr('placeholder', 'Je souhaite échanger mon créneau contre le tien').val('Je souhaite échanger mon créneau contre le tien'))
      .append($('<button></button>').addClass('submitedButton').text('Proposer cet échange').on('click', function () {
        var note = $('#note').val();

        if (note == '')
          note = $('#note').attr('placeholder');

        getRequest('exchanges.php', {
          'mode': 'ask',
          'idUV': idUV,
          'idUV2': idUV2,
          'note': note
        }, function () {
          window.get = {
            'mode': 'modifier',
            'mode_type': 'sent',
            'mode_option': 'available'
          };

          generate();
        });
      }));

    popup('Echanger ton ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre celui du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end, corps);
  });
};

var cancelAskExchange = function (idExchange, idUV, idUV2) {
  getRequest('exchanges.php', {
    'mode': 'get',
    'idUV': idUV,
    'idUV2': idUV2
  }, function (data) {
    var task = data.uv;
    var toExchange = data.uv2;
    var corps = $('<div></div>').addClass('centerCard');

    if (data.sent.length == 1)
      corps.append($('<div></div>').append('Tu étais le/la seul.e à avoir fait cette proposition. Un mail sera envoyé à ceux qui possèdent le créneau pour leur signaler que la proposition n\'est plus disponible.<br />'));
    else
      corps.append($('<div></div>').append(data.sent.length - 1 + (data.sent.length == 2 ? ' autre personne souhaite effectuer cet échange. Il ne sera plus que le/la seul.e.' : ' autres personnes ont aussi fait cette proposition.<br />')));

    corps
      .append($('<div></div>').html('Après avoir annulé cette proposition, tu pourras de nouveau refaire cette demande. Si entre-temps, d\'autres personnes ont fait la demande, tu devras attendre ton tour. Tu peux toujours aussi proposer d\'autres créneaux bien sûr.<br /><br />'))
      .append($('<button></button>').addClass('submitedButton').text('Annuler cette proposition').on('click', function () {
        getRequest('exchanges.php', {
          'mode': 'cancelAsk',
          'idExchange': idExchange,
        }, function () {
          window.get = {
            'mode': 'modifier',
            'mode_type': 'uv_followed',
            'uv': task.uv,
            'type': task.type
          };

          generate();
        });
      }));

    popup('Annuler la proposition d\'échange de ton ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre celui du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end, corps);
  });
};

var refuseExchange = function (idExchange, idUV, idUV2) {
  getRequest('exchanges.php', {
    'mode': 'get',
    'idUV': idUV,
    'idUV2': idUV2
  }, function (data) {
    var task = data.uv;
    var toExchange = data.uv2;
    var corps = $('<div></div>').addClass('centerCard');

    corps
      .append($('<div></div>').html('Après avoir refusé cette proposition, tu ne pourras plus accepter cet échange, même si quelqu\'un d\'autre se propose. Tu peux toujours aussi proposer d\'autres créneaux ou toi-même demander cet échange si tu souhaites enfin de compte échanger ton créneau.<br /><br />'))
      .append($('<button></button>').addClass('submitedButton').text('Refuser cette proposition').on('click', function () {
        getRequest('exchanges.php', {
          'mode': 'refuse',
          'idExchange': idExchange,
        }, function () {
          window.get = {
            'mode': 'modifier',
            'mode_type': 'received',
            'mode_option': 'refused'
          };

          generate();
        });
      }));

    popup('Refuser la proposition d\'échange de ton ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre celui du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end, corps);
  });
};

var acceptExchange = function (idExchange, idUV, idUV2) {
  getRequest('exchanges.php', {
    'mode': 'get',
    'idUV': idUV,
    'idUV2': idUV2
  }, function (data) {
    var task = data.uv;
    var toExchange = data.uv2;
    var corps = $('<div></div>').addClass('centerCard');

    corps
      .append($('<div></div>').html('Après avoir accepté cette proposition, les emplois du temps seront mis à jour et l\'échange aura été effectué. Si malheuresement, tu souhaites récupérer ton créneau, tu peux toujours réaliser une demande d\'annulation de l\'échange. Tu pourras ensuite échanger ton nouveau créneau avec un autre si tu le souhaites.<br /><br />'))
      .append($('<button></button>').addClass('submitedButton').text('Accepter cette proposition').on('click', function () {
        getRequest('exchanges.php', {
          'mode': 'accept',
          'idExchange': idExchange,
        }, function () {
          window.get = {
            'mode': 'modifier',
            'mode_type': 'received',
            'mode_option': 'accepted'
          };

          generate();
        });
      }));

    popup('Accepter la proposition d\'échange de ton ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre celui du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end, corps);
  });
};

var askCancelExchange = function (idExchange, idUV, idUV2) {
  getRequest('exchanges.php', {
    'mode': 'get',
    'idUV': idUV,
    'idUV2': idUV2
  }, function (data) {
    var task = data.uv;
    var toExchange = data.uv2;
    var corps = $('<div></div>').addClass('centerCard');

    corps
      .append($('<div></div>').html('Après avoir demandé l\'annulation de l\'échange, tu ne pourras plus proposer d\'échanger ton créneau avec celui-là. Tu peux toujours relancer la personne en espérant qu\'elle accepte l\'annulation de l\'échange.<br />Tu peux bien sûr annuler ta demande d\'annulation si l\'échange te convient en fin de compte.<br />Tu seras tenu.e informé.e si ta demande a été acceptée.<br /><br />Tu peux expliquer pourquoi tu souhaites annuler l\'échange:'))
      .append($('<textarea /><br /><br />').attr('id', 'note').addClass('focusedInput').addClass('submitedInput').css('width', '50%').attr('maxlength', 180).attr('placeholder', 'Je souhaite annuler l\'échange que l\'on a effectué ensemble qui est celui de mon ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre le tien du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end).val('Je souhaite annuler l\'échange que l\'on a effectué ensemble qui est celui de mon ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre le tien du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end))
      .append($('<button></button>').addClass('submitedButton').text('Demander d\'annuler cet échange').on('click', function () {
        var note = $('#note').val();

        if (note == '')
          note = $('#note').attr('placeholder');

        getRequest('exchanges.php', {
          'mode': 'askCancel',
          'idExchange': idExchange,
          'note': note
        }, function () {
          window.get = {
            'mode': 'modifier',
            'mode_type': 'canceled',
            'mode_option': 'sent'
          };

          generate();
        });
      }));

    popup('Demander d\'annuler l\'échange de ton ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre celui du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end, corps);
  });
};

var cancelAskCancelExchange = function (idExchange, idUV, idUV2) {
  getRequest('exchanges.php', {
    'mode': 'get',
    'idUV': idUV,
    'idUV2': idUV2
  }, function (data) {
    var task = data.uv;
    var toExchange = data.uv2;
    var corps = $('<div></div>').addClass('centerCard');

    corps
      .append($('<div></div>').html('Après avoir annulé ta demande d\'annulation de l\'échange, tu ne pourras plus demander l\'annulation de cet échange. Bien sûr, si la personne avec qui tu souhaites échanger souhaite annuler l\'échange, tu peux accepter cette annulation.<br /><br />'))
      .append($('<button></button>').addClass('submitedButton').text('Annuler ma demander d\'annulation').on('click', function () {
        getRequest('exchanges.php', {
          'mode': 'cancelAskCancel',
          'idExchange': idExchange,
        }, function () {
          generate();
        });
      }));

    popup('Annuler la demande d\'annuler l\'échange de ton ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre celui du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end, corps);
  });
};

var acceptCancelExchange = function (idExchange, idUV, idUV2) {
  getRequest('exchanges.php', {
    'mode': 'get',
    'idUV': idUV,
    'idUV2': idUV2
  }, function (data) {
    var task = data.uv;
    var toExchange = data.uv2;
    var corps = $('<div></div>').addClass('centerCard');

    corps
      .append($('<div></div>').html('Après avoir annulé l\'échange, les emplois du temps seront restaurés comme si l\'échange n\'a jamais été effectué. Bien sûr, tu pourras rééchanger ton créneau avec d\'autres.<br /><br />'))
      .append($('<button></button>').addClass('submitedButton').text('Annuler l\'échange').on('click', function () {
        getRequest('exchanges.php', {
          'mode': 'cancel',
          'idExchange': idExchange,
        }, function () {
          generate();
        });
      }));

    popup('Annuler l\'échange de ton ' + (task.type == 'T' ? 'TP' : task.type == 'D' ? 'TD' : 'cours') + ' de ' + task.uv + ' du ' + window.headers[task.day].toLowerCase() + ' de ' + task.begin + ' à ' + task.end + ' contre celui du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + toExchange.begin + ' à ' + toExchange.end, corps);
  });
};


/* Couleurs */


var getFgColor = function (bgColor) {
  if (bgColor == null)
    return null;

  if ((((parseInt(bgColor.substr(1, 2), 16) * 299) + (parseInt(bgColor.substr(3, 2), 16) * 587) + (parseInt(bgColor.substr(5, 2), 16) * 114))) > 127000)
    return '#000000';
  else
    return '#FFFFFF';
};


/* Génération */

var getFreeTimes = function (timeNeeded, nbrNotAvailable, dayToSee) {
  var freeTimes = {};
  var freeTimesDiv = $('<div></div>');
  var nbrAvailables = {};
  var max = window.HOUR_MAX;

  if (!timeNeeded)
    timeNeeded = 0.5;

  if (max + +timeNeeded > 24)
    max = 24 - +timeNeeded + 0.5;

  getRequest('calendar.php', window.get, function (data) {
    var days = {};
    for (var i = 0; i < window.headers.length; i++) {
      if ($('#ftCheck' + i))
        days[i] = $('#ftCheck' + i).prop('checked');
      else
        days[i] = true;
    }

    $.each(days, function (i, bool) {
      if (!bool)
        return;

      freeTimes[i] = {};

      for (var j = window.HOUR_MIN; j < max; j += 0.5) { // On fait par tranche de demi-heure
        freeTimes[i][j] = [];

        $.each(data.infos.active, function (element, color) {
          freeTimes[i][j].push(element);
        });
      }
    });

    $.each(data.tasks, function (id, infos) {
      $.each(infos.data, function (idTasks, task) {
        $.each(freeTimes[task.day], function (freeTime, list) {
          if ((+freeTime < +task.startTime && +freeTime + +timeNeeded > +task.startTime) || (+freeTime < +task.startTime + +task.duration && +freeTime + +timeNeeded > +task.startTime)) {
            var index = list.indexOf(infos.info);

            if (index != -1)
              freeTimes[task.day][freeTime].splice(index, 1);
          }
        });
      });
    });

    for (var i = 0; i < window.headers.length; i++) {
      $.each(freeTimes[i], function (freeTime, availableList) {
        if (!nbrAvailables[availableList.length])
          nbrAvailables[availableList.length] = {};

        if (!nbrAvailables[availableList.length][i])
          nbrAvailables[availableList.length][i] = [];

        nbrAvailables[availableList.length][i].push(freeTime);
      });
    }

    var most = 0;
    var select = $('<select></select>');
    for (var i = Object.keys(nbrAvailables).length - 1; i > 0; i--) {
      var diff = Object.keys(window.active).length - Object.keys(nbrAvailables)[i];

      select.append($('<option></option>', {
        'text': diff,
        'val': diff,
      }).on('click', function () {
        getFreeTimes(+timeNeeded, diff, dayToSee);
      }))

      if (Object.keys(nbrAvailables)[i] > most)
        most = Object.keys(nbrAvailables)[i];
    }
    select.val(nbrNotAvailable || Object.keys(window.active).length - most);

    $('<div></div>')
      .append(select)
      .append(' indisponible(s) aux créneaux suivants:')
      .appendTo(freeTimesDiv);

    if (nbrNotAvailable)
      most = Object.keys(window.active).length - nbrNotAvailable;

    if (!dayToSee)
      dayToSee = Object.keys(nbrAvailables[most])[0];

    if (nbrAvailables[most]) {
      var days = $('<div></div>');
      $.each(nbrAvailables[most], function (day, begins) {
        days.append($('<div></div>', {
          'text': window.headers[day] + ': ' + Object.keys(begins).length + ' créneau' + (Object.keys(begins).length > 1 ? 'x' : '') + ' disponible' + (Object.keys(begins).length > 1 ? 's' : '')
        })
          .append($('<button></button>').html('<i class="fa fa-eye"></i>').prop('disabled', dayToSee == day).on('click', function () {
            getFreeTimes(+timeNeeded, Object.keys(window.active).length - most, day);
          })));
      });

      days.appendTo(freeTimesDiv)
    }
    else {
      $('#freeTimes').append($('<div></div>', {
        'text': 'Aucun créneau disponible cette semaine pour la durée demandée (personne n\'est jamais disponible en quelque sorte haha)'
      }));
    }

    getRequest('groups.php', {
      'mode': 'get',
      'group': 'others'
    }, function (data) {
      var begins = $('<div></div>');
      var elements = {};

      $.each(window.active, function (element, color) {
        elements[element] = element;
      });

      elements[window.sessionLogin] = 'Moi';
      $.each(data.others.subgroups.students.elements, function (login, infos) {
        $.each(window.active, function (element, color) {
          if (element == login)
            elements[login] = infos.firstname + ' ' + infos.surname;
        });
      });

      $.each(nbrAvailables[most][dayToSee], function (id, begin) {
        var notAvailable = $.extend({}, elements);

        var slot = $('<div></div>', {
          'text': 'Créneau de ' + begin + ' à ' + (+begin + +timeNeeded) + ': '
        }).append($('<div></div>', {
          'text': 'Disponible' + (freeTimes[dayToSee][begin].length > 1 ? 's: ' : ': ')
        }));

        for (var i = 0; i < freeTimes[dayToSee][begin].length - 1; i++) {
          var element = freeTimes[dayToSee][begin][i];
          delete notAvailable[element];
          slot.append(elements[element] + ', ');
        }

        var element = freeTimes[dayToSee][begin][freeTimes[dayToSee][begin].length - 1];
        delete notAvailable[element];
        slot.append(elements[element]).append($('<div></div>', {
          'text': 'Indisponible' + (Object.keys(notAvailable).length > 1 ? 's: ' : ': ')
        }));

        var i = 0;
        $.each(notAvailable, function (element, text) {
          slot.append(text + (i++ == Object.keys(notAvailable).length - 1 ? '' : ', '));
        });

        slot.appendTo(begins);
      });

      begins.appendTo(freeTimesDiv);
      $('#freeTimes').html(freeTimesDiv);
    });
  });
};

var generateFreeTimes = function () {
  popup('Trouver le meilleur créneau', $('<div></div>').addClass('centerCard').text('Chercher un créneau pour:')
    .append($('<input></input>').attr('type', 'checkbox').attr('id', 'ftCheck0').prop('checked', true)).append($('<label></label><br />').attr('for', 'ftCheck0').text('Lundi'))
    .append($('<input></input>').attr('type', 'checkbox').attr('id', 'ftCheck1').prop('checked', true)).append($('<label></label><br />').attr('for', 'ftCheck1').text('Mardi'))
    .append($('<input></input>').attr('type', 'checkbox').attr('id', 'ftCheck2').prop('checked', true)).append($('<label></label><br />').attr('for', 'ftCheck2').text('Mercredi'))
    .append($('<input></input>').attr('type', 'checkbox').attr('id', 'ftCheck3').prop('checked', true)).append($('<label></label><br />').attr('for', 'ftCheck3').text('Jeudi'))
    .append($('<input></input>').attr('type', 'checkbox').attr('id', 'ftCheck4').prop('checked', true)).append($('<label></label><br />').attr('for', 'ftCheck4').text('Vendredi'))
    .append($('<input></input>').attr('type', 'checkbox').attr('id', 'ftCheck5').prop('checked', true)).append($('<label></label><br />').attr('for', 'ftCheck5').text('Samedi'))
    .append($('<input></input>').attr('type', 'checkbox').attr('id', 'ftCheck6').prop('checked', true)).append($('<label></label><br />').attr('for', 'ftCheck6').text('Dimanche'))
    .append('Durée du créneau (en heure): ')
    .append($('<input />', {
      'type': 'number',
      'min': '0.5',
      'max': window.HOUR_MAX - window.HOUR_MIN,
      'step': '0.5',
      'value': '2',
      'id': 'duration',
      'class': 'focusedInput submitedInput',
    }))
    .append($('<button></button>', {
      'class': 'submitedButton',
      'text': 'Trouver les meilleurs créneaux'
    }).on('click', function () {
      getFreeTimes($('#duration').val());
    })
    )
    .append($('<div></div>', {
      'id': 'freeTimes',
    }))
  );
};

var generateMode = function () {
  if (window.get.mode == 'modifier')
    $('#modifyTools').css('display', 'block');
  else
    $('#modifyTools').css('display', 'none');

  if (window.get.mode == 'organiser')
    $('#organizeTools').css('display', 'block');
  else
    $('#organizeTools').css('display', 'none');

  if (window.get.mode == 'semaine' || window.get.mode == 'organiser')
    $('#weekTools').css('display', 'block');
  else
    $('#weekTools').css('display', 'none');
};

var generateStudentCard = function (infos, info, idGroup, idSubGroup, type) {
  var text = (infos.login == 'cerichar' ? 'César RICHARD - Licorne d\'amour <3' : (infos.firstname == null ? 'Aucune information sur la personne' : infos.firstname + ' ' + infos.surname));
  var id = 'card-' + infos.login + (idSubGroup ? '-' + idSubGroup : '');
  var option = $('<div></div>').addClass('optionsCard');

  if (idGroup && idSubGroup && type) {
    option
      .append($('<button></button>').html('<i class="fa fa-edit"></i>').attr('disabled', type == 'asso').on('click', function () {
        setToGroup(idGroup, idSubGroup, infos.login, text, id, infos.semester, (infos.info === undefined || infos.info == null));
      }))
      .append($('<button></button>').html('<i class="fa fa-remove"></i>').attr('disabled', type == 'asso').attr('onClick', 'delFromGroup("' + idGroup + '", "' + idSubGroup + '", "' + infos.login + '", "' + infos.info + '")'));
  }
  else {
    if (window.get.mode == 'organiser')
      option.append($('<button></button>').html('<i class="fa fa-' + (window.active[infos.login] ? 'eye-slash' : 'eye') + '"></i>').attr('disabled', infos.login == window.sessionLogin || infos.extern).attr('onClick', (window.active[infos.login] ? 'delActive' : 'addActive') + '("' + infos.login + '", function () {' + (idGroup ? 'seeGroup("' + idGroup + '")' : 'printSearch();') + '});'))
    else
      option.append($('<button></button>').html('<i class="fa fa-eye"></i>').attr('disabled', infos.login == window.sessionLogin || infos.extern).attr('onClick', 'seeStudent("' + infos.login + (info == undefined ? '' : '", "' + info) + '")'))

    option.append($('<button></button>').html('<i class="fa fa-plus"></i>').attr('disabled', infos.login == window.sessionLogin || infos.extern).on('click', function () {
      addToGroup(infos.login, text);
    }));
  }

  var card = $('<div></div>').addClass('studentCard').attr('id', id)
    .append($('<div></div>').addClass('imgCard')
      .append($('<i class="fa fa-4x fa-user-o"></i>').css('padding-top', '3px').css('padding-left', '1px'))
      .append($('<img />').attr('src', 'https://demeter.utc.fr/pls/portal30/portal30.get_photo_utilisateur?username=' + infos.login)))
    .append($('<div></div>').addClass('infosCard')
      .append($('<b></b>').text(text)).append($('<br />'))
      .append($('<span></span>').text(infos.info === undefined || infos.info == null ? infos.semester + ' - ' + infos.login : infos.info)).append($('<br />'))
      .append($('<a></a>').attr('href', 'mailto:' + infos.email).text(infos.email)))
    .append(option);

  if (window.active[infos.login])
    card.css('background-color', window.active[infos.login] + 'CC').css('color', getFgColor(window.active[infos.login]));

  return card;
};

var generateUVCard = function (infos, uvs, info, idGroup, idSubGroup, type) {
  var id = 'card-' + infos.uv + (idSubGroup ? '-' + idSubGroup : '');
  var option;

  if (idGroup && idSubGroup && type) {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-edit"></i>').attr('disabled', type == 'asso').on('click', function () {
        setToGroup(idGroup, idSubGroup, infos.uv, infos.uv, id);
      }))
      .append($('<button></button>').html('<i class="fa fa-remove"></i>').attr('disabled', type == 'asso').attr('onClick', 'delFromGroup("' + idGroup + '", "' + idSubGroup + '", "' + infos.uv + '")'));
  }
  else if (window.get.mode == 'organiser' && infos.info) {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-' + (infos.active ? 'eye-slash' : 'eye') + '"></i>').attr('onClick', (infos.active ? 'delActive' : 'addActive') + '("' + infos.uv + '"); seeGroup("' + idGroup + '")'))
      .append($('<button></button>').html('<i class="fa fa-plus"></i>').attr('onClick', 'addToGroup("' + infos.uv + '", "' + infos.uv + '")'));
  }
  else {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-eye"></i>').attr('onClick', 'seeUV("' + infos.uv + '")'))
      .append($('<button></button>').html('<i class="fa fa-plus"></i>').attr('onClick', 'addToGroup("' + infos.uv + '", "' + infos.uv + '")'));
  }

  var card = $('<div></div>').addClass('uvCard').attr('id', id)
    .append($('<div></div>').addClass('imgCard')
      .append($('<i class="fa fa-4x fa-book"></i>').css('padding-top', '3px').css('padding-left', '1px')))
    .append($('<div></div>').addClass('infosCard').css('padding-left', '5px')
      .append($('<b></b>').text(infos.uv))
      .append($('<br />'))
      .append($('<span></span>').text(uvs == undefined ? infos.info : (uvs.search(infos.uv) != -1 ? 'UV suivie' : ''))))
    .append(option);

  if (infos.active && window.active[infos.uv])
    card.css('background-color', window.active[infos.uv] + 'CC').css('color', getFgColor(window.active[infos.uv]));

  return card;
};

var generateTitle = function (title) {
  $('#title').text(title);
};

var generateWeeks = function (weeks, week) {
  console.log(weeks)
  if (window.get.mode == 'semaine') {
    $('#mode_semaine').prop('checked', true);
    $('#mode_week').text(' Semaine du ' + week.split('-')[2] + '/' + week.split('-')[1] + ':');
  }
  else if (window.get.mode == 'comparer')
    $('#mode_comparer').prop('checked', true);
  else if (window.get.mode == 'modifier')
    $('#mode_modifier').prop('checked', true);
  else if (window.get.mode == 'organiser') {
    $('#mode_organiser').prop('checked', true);
    $('#mode_week').text(' Semaine du ' + week.split('-')[2] + '/' + week.split('-')[1] + ':');
  }
  else
    $('#mode_classique').prop('checked', true);

  if (!weeks.before)
    $('#before').prop('disabled', true);
  else
    $('#before').prop('disabled', false).attr('onClick', 'window.get.week="' + weeks.before + '"; generate()');

  if (!weeks.actual)
    $('#actual').prop('disabled', true);
  else
    $('#actual').prop('disabled', false).attr('onClick', 'window.get.week="' + weeks.actual + '"; generate()');

  if (!weeks.after)
    $('#after').prop('disabled', true);
  else
    $('#after').prop('disabled', false).attr('onClick', 'window.get.week="' + weeks.after + '"; generate()');
};

var generateSubMenu = function (tabs, id) {
  var submenu = $('#' + id + 's');
  submenu.empty();

  if ($.isEmptyObject(tabs))
    $('#affichage_' + id + 's').prop('checked', false).css('display', 'none');
  else
    $('#affichage_' + id + 's').prop('checked', true).css('display', 'block');

  var div, menu, text;
  $.each(tabs, function (key, tab) {
    div = $('<div></div>').attr('id', id + '-' + key);
    menu = $('<button></button>');

    if (tab.name == undefined)
      text = tab.text;
    else
      text = tab.name;

    if (tab.type == 'asso')
      text = '<i class="fa fa-address-book" aria-hidden="true"></i> ' + text;
    else if (tab.type == 'custom')
      text = '<i class="fa fa-user-circle" aria-hidden="true"></i> ' + text;
    else if (tab.type == 'others')
      text = '<i class="fa fa-eye" aria-hidden="true"></i> ' + text;
    else if (tab.type == 'new')
      text = '<i class="fa fa-plus" aria-hidden="true"></i> ' + text;

    if (key == 'me')
      menu.attr('id', 'myTab');

    if (tab.active)
      menu.addClass('active');
    else if (tab.partialyActive)
      menu.addClass('partialyActive');

    if (tab.disabled)
      menu.prop('disabled', true);

    if (tab.options == undefined) {
      if (tab.action == undefined) {
        menu.on('click', function () {
          addGet(tab.get);

          generate();
        });
      }
      else {
        if (tab.action == 'paramGroup')
          menu.on('click', function () {
            paramGroup(tab);
          });
        else
          menu.attr('onClick', tab.action);
      }

      menu.html(text).appendTo(div);
    }
    else {
      menu.html(text + '<i class="fa fa-caret-up" aria-hidden="true"></i>').on('click', function () {
        if ($(this).children().last().hasClass('fa-caret-up')) {
          $(this).children().last().removeClass('fa-caret-up').addClass('fa-caret-down');
          $(this).next().css('height', 'auto');
        }
        else {
          $(this).children().last().removeClass('fa-caret-down').addClass('fa-caret-up');
          $(this).next().css('height', 0);
        }
      });

      var container = $('<div></div>').addClass('options');

      $.each(tab.options, function (value, option) {
        var button = $('<button></button>').html(option.text).addClass('option');

        if (option.action == undefined) {
          button.on('click', function () {
            if (tab.get != undefined)
              addGet(tab.get);
            if (option.get != undefined)
              addGet(option.get);

            generate();
          });
        }
        else {
          if (button.action == 'paramGroup')
            button.on('click', function () {
              paramGroup(tab);
            });
          else
            button.attr('onClick', option.action);
        }

        if (option.active || (tab.type != 'select' && tab.active && value != 'more'))
          button.addClass('active');
        else if (option.partialyActive)
          button.addClass('partialyActive');

        if (option.disabled)
          button.prop('disabled', true);

        button.appendTo(container);
      });

      menu.appendTo(div);
      div.append(container);
    }

    div.appendTo(submenu);

    if (tab.separate)
      $('<div></div>').addClass('separatorTab').appendTo(submenu);
  });
}

var generatePrinted = function (groups) {
  $('#printed').empty();
  $('#printedTools').empty();

  if (window.get.mode == 'organiser') {
    $('#affichage_printed').css('display', 'block');

    if (window.active && Object.keys(window.active).length > 1) {
      $('#printedText').text(Object.keys(window.active).length + ' affichés:');
      $('#eventTool').prop('disabled', false);
    }
    else {
      $('#printedText').text('1 seul affiché:');
      $('#eventTool').prop('disabled', true);
    }

    $('<div></div>').attr('id', 'printed-' + window.sessionLogin).append($('<button></button>').html('<span>Moi</span>').css('background-color', window.active[window.sessionLogin] || '#222222').css('color', getFgColor(window.active[window.sessionLogin] || '#222222'))).appendTo($('#printed'));

    $.each(window.active, function (element, color) {
      if (element != window.sessionLogin)
        $('<div></div>').attr('id', 'printed-' + element).append($('<button></button>').html('<span>' + element + '</span><i class="fa fa-times" aria-hidden="true"></i>').css('background-color', window.active[element] || '#222222').css('color', getFgColor(window.active[element] || '#222222')).on('click', function () {
          window.get.delActive = [element];

          generate();
        })).appendTo($('#printed'));
    });

    var elem;
    var emails = {};
    $.each(groups, function (name, group) {
      if (!group.partialyActive)
        return;
      $.each(group.subgroups, function (sub_name, sub_group) {
        $.each(sub_group.elements, function (element, infos) {
          if (!infos.active && element != window.sessionLogin)
            return;

          if (infos.email)
            emails[element] = infos.email;

          if (infos.firstname && infos.surname)
            $('#printed-' + element + ' span').text(infos.firstname + ' ' + infos.surname);
          else
            $('#printed-' + element + ' span').text(infos.uv + ' (uv)');

          if (name != 'others')
            $('<div></div>').html('<i class="fa fa-angle-right" aria-hidden="true"></i> ' + group.name + ' - ' + sub_group.name + (infos.info != null && infos.info != '' ? ' (' + infos.info + ')' : '')).appendTo($('#printed-' + element).children());
        });
      });
    });

    $('<button></button>').on('click', function () {
      var mailto = '';

      $.each(emails, function (element, email) {
        mailto += ',' + email;
      });

      window.open('mailto:' + mailto.substr(1));
    }).html('<i class="fa fa-send" aria-hidden="true"></i> Envoyer un mail aux étudiant.e.s affiché.e.s ').prop('disabled', Object.keys(emails).length == 0).appendTo($('#printedTools'));
  }
  else {
    $('#affichage_printed').css('display', 'none');

    if (window.get.mode != 'modifier' && window.get.mode != 'semaine') {
      var text = (window.get.login && groups.others.subgroups.students.elements[window.get.login].firstname != null ? groups.others.subgroups.students.elements[window.get.login].firstname + ' ' + groups.others.subgroups.students.elements[window.get.login].surname : (window.get.uv ? window.get.uv : window.get.login));

      if (window.get.login || window.get.uv)
        $('<button></button>').html('<i class="fa fa-plus" aria-hidden="true"></i> Ajouter ' + text).on('click', function () {
          addToGroup(window.get.login || window.get.uv, text);
        }).appendTo($('#printedTools'));

      if (window.get.login) {
        $('<button></button>').attr('id', 'clipboard').attr('data-clipboard-text', window.get.login).html('<i class="fa fa-clipboard" aria-hidden="true"></i> Copier son login: ' + window.get.login).appendTo($('#printedTools'));
        $('<button></button>').on('click', function () {
          window.open('mailto:' + groups.others.subgroups.students.elements[window.get.login].email);
        }).html('<i class="fa fa-send" aria-hidden="true"></i> Lui envoyer un email').appendTo($('#printedTools'));
        new Clipboard('#clipboard');
      }
    }
  }
}

var cardClick = function (task) {
  var end = (window.get.mode === 'semaine' || window.get.mode === 'organiser' ? 629 : 588);

  $('#nav').removeClass('see');
  $('#parameters').removeClass('see');

  if (window.task == null) {
    if (task.top + 150 > end) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
      $('#' + task.id).css('top', end - 150);
    $('#' + task.id).css('left', -2).css('height', 150).css('width', 135).addClass('focus');
    $('#' + task.id + ' .interraction').css('display', 'block');

    $('#zoneGrey').addClass('focused');
    $('#zoneFocus').addClass('focused');

    window.task = task;
  }
  else if (window.task.id == task.id) {
    $('#' + task.id).css('top', task.top).css('left', task.left).css('height', task.height).css('width', task.width).removeClass('focus');
    $('#' + task.id + ' .interraction').css('display', 'none');

    $('#zoneGrey').removeClass('focused');
    $('#zoneFocus').removeClass('focused');

    window.task = null;
  }
  else {
    if (window.task != null) {
      $('#' + window.task.id).css('top', window.task.top).css('left', window.task.left).css('height', window.task.height).css('width', window.task.width).removeClass('focus');
      $('#' + window.task.id + ' .interraction').css('display', 'none');
    }

    if (task.top + 150 > end) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
      $('#' + task.id).css('top', end - 150);
    $('#' + task.id).css('left', -2).css('height', 150).css('width', 135).addClass('focus');
    $('#' + task.id + ' .interraction').css('display', 'block');

    $('#zoneGrey').addClass('focused');
    $('#zoneFocus').addClass('focused');

    window.task = task;
  }
}

var cardRoomClick = function (task) {
  var toAdd = (task.subject > 1 ? 's' : '');
  var popupHead = task.subject + ' salle' + toAdd + ' disponible' + toAdd + (task.timeText == 'Journée' ? ' toute la journée' : (' vers ' + Math.ceil(task.startTime) + 'h pour ' + task.timeText));
  var table = $('<table></table').css('padding', '1%').css('width', '100%');

  var rooms, tr;
  for (key in task.description) {
    rooms = task.description[key]
    tr = $('<tr></tr>');
    $('<td></td>').css('width', '15%').text('Salle' + (rooms.length == 1 ? '' : 's') + ' de ' + (key == 'D' ? 'TD' : (key == 'C' ? 'cours' : 'TP'))).appendTo(tr);
    $('<td></td>').css('width', '85%').text(rooms.join(', ')).appendTo(tr);
    tr.appendTo(table);
  }

  popup($('<div></div>').text(popupHead), $('<div></div>').append(table), task.bgColor, task.fgColor);
}

var generateCards = function (schedulerTasks, tasks, day, sides, uvs) {
  var passed = [];
  var toPass = [];
  var nbrPassed = 0;
  var nbrSameTime = 1;
  var toExchange;
  var div = $('<div></div>');
  var button = $('<button/>');

  var card, style;
  //tasks.forEach(function(group) {
  $.each(tasks, function (index, group) {
    //    group.data.forEach(function(task) {
    $.each(group.data, function (index2, task) {
      if (window.get.mode == 'modifier' && window.get.mode_type == 'uvs_followed') {
        $.each(tasks[0].data, function (index0, task) {
          if (task.subject == window.get.uv && task.type == window.get.type)
            toExchange = task;
        });
      }



      cardClass = 'card';
      if (task.day != day)
        return;

      if (task.duration - task.startTime == 24) {
        task.top = Math.ceil(21 * ((window.HOUR_MAX - window.HOUR_MIN) * 2));
        task.height = Math.ceil(21 * (1 * 2) - 1);
      }
      else {
        task.top = Math.ceil(21 * ((task.startTime - window.HOUR_MIN) * 2));

        if (task.startTime < window.HOUR_MIN) {
          task.startTime = window.HOUR_MIN;
          cardClass += ' before';
        }

        if (task.startTime + task.duration > window.HOUR_MAX) {
          task.duration = window.HOUR_MAX - task.startTime;
          cardClass += ' after';
        }

        task.height = Math.ceil(21 * (task.duration * 2) - 1);
      }

      card = div.clone().attr({
        'id': task.id,
        'class': cardClass,
      });

      style = {
        'top': task.top,
        'height': task.height,
        'background-color': task.bgColor,
        'color': getFgColor(task.bgColor)
      };

      // Il faut vérifier si des cards coincident
      nbrSameTime = 1;
      $.each(tasks, function (indexComp, groupToCompare) {
        $.each(groupToCompare.data, function (indexComp2, toCompare) {
          if (toCompare.day != day || task.id == toCompare.id)
            return;

          if ((group.side === groupToCompare.side && ((task.startTime >= toCompare.startTime && task.startTime < toCompare.startTime + toCompare.duration) || (toCompare.startTime >= task.startTime && toCompare.startTime < task.startTime + task.duration))) && ((task.duration < (window.HOUR_MAX - window.HOUR_MIN) && toCompare.duration < (window.HOUR_MAX - window.HOUR_MIN)) || task.duration == toCompare.duration))
            nbrSameTime++;

          if ((window.get.mode == 'comparer' || window.get.mode == 'modifier') && task.idUV == toCompare.idUV) {
            card.addClass('sameCard');
          }
        });
      });

      if (group.type == 'organize') {
        $('<div></div>').append($('<span></span>').text(task.subject)).append($('<h6></h6>').css('display', 'inline').css('padding-left', '2px').text(task.location)).appendTo(card);
        style.opacity = 0.5;
        style['box-shadow'] = 'none';
      }
      else {
        toPass = [group.side, task.day, task.startTime, task.duration];
        nbrPassed = 0;
        passed.forEach(function (toCompare) {
          if (((toCompare[0] === toPass[0] && toCompare[1] === toPass[1]) && ((toCompare[2] >= toPass[2] && toCompare[2] < toPass[2] + toPass[3]) || (toPass[2] >= toCompare[2] && toPass[2] < toCompare[2] + toCompare[3]))) && ((toPass[3] < (window.HOUR_MAX - window.HOUR_MIN) && toCompare[3] < (window.HOUR_MAX - window.HOUR_MIN)) || toPass[3] == toCompare[3]))
            nbrPassed += 1;
        });
        passed.push(toPass);

        task.width = 133 / ((sides * nbrSameTime) + (nbrSameTime == 1 && task.week != undefined) + (sides == 2 && nbrSameTime == 1 && task.week != undefined)) - ((sides * nbrSameTime == 2) || (nbrSameTime == 1 && task.week != undefined));
        task.left = (group.side == undefined ? 0 : ((group.side - 1) * 69)) - sides + (nbrPassed * task.width) + ((nbrSameTime == 1 && task.week == 'B') * 66 / sides);
        /*
                if (card.hasClass('sameCard')) {
                  if (window.get.mode == 'comparer') {
                    if (group.side == 1) {
                      if (task.week == undefined) {
                        task.width = 133;
                        task.left = -1;
                      }
                      else {
                        task.width = 66;
                        task.left = 33;
                      }
                    }
                    else
                    return;
                  }
                  else {
                    card.addClass('sameCard' + group.side);
                    task.width += 2;
        
                    if (group.side == 2)
                      task.left -= 2;
                  }
                }
        */
        style.width = task.width;
        style.left = task.left;

        isUV = group.type == 'uv_followed' || group.type == 'uv' || group.type == 'received' || group.type == 'sent' || group.type == 'canceled' || group.type === 'prevoir';
        isEvent = group.type == 'event' || group.type == 'meeting';
        subject = div.clone().addClass('subject');

        $('<span></span>').text(task.subject + (group.type == 'room' ? ' dispo' + (task.subject > 1 ? 's' : '') : '')).appendTo(subject);

        if (isUV) {
          type = (task.type == 'D' ? 'TD' : (task.type == 'T' ? 'TP' : 'Cours'));
          $('<h5></h5>').text(type + ' ' + task.groupe).appendTo(subject);
        }

        div.clone().addClass('time').text(task.timeText).appendTo(card);
        subject.appendTo(card);
        div.clone().addClass('location').text(task.location).appendTo(card);

        if (task.note != null)
          div.clone().addClass('note').text(task.note).appendTo(card);

        if (window.get.mode == 'modifier' && sides == 2 && (window.get.mode_type != 'changement' || group.side == 1)) {
          if (card.hasClass('sameCard') && window.get.mode_type == 'uvs_followed') {
            style.opacity = 0.5;
            style['box-shadow'] = 'none';
          }
          else {
            if (group.side == 1) {
              card.on('click', function () {
                seeOthers(task.subject, task.type, task.idUV, 'uv-' + task.subject + '-' + task.idUV);
              });
            }
            else {
              interraction = div.clone().addClass('interraction');
              option = button.clone().addClass('option').css('background-color', task.bgColor).css('color', getFgColor(task.bgColor));

              if (window.get.mode_type == 'uvs_followed') {
                option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Informations").on('click', function () { seeUVInformations(task); }).appendTo(interraction);
                option.clone().html('<i class="fa fa-handshake-o" aria-hidden="true"></i> Proposer en échange').on('click', function () {
                  askExchange(toExchange.idUV, task.idUV);
                }).appendTo(interraction);

                if (!task.description)
                  task.description = 'En ' + (toExchange.available == '1' && toExchange.exchanged == '1' ? 'récupération' : 'échange') + ' du mien du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + (toExchange.timeText ? toExchange.timeText.replace('-', ' à ') : toExchange.begin + ' à ' + toExchange.end);

              }
              else {
                var toExchange = task.exchange;
                var type = '';
                var bgColor;

                if (toExchange.available == '1') {
                  if (toExchange.exchanged == '1') {
                    type = ' - Annulée';
                    bgColor = '#FFFF00';

                    if (toExchange.login2 && toExchange.login2 == window.sessionLogin)
                      button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-check" aria-hidden="true"></i> Accepter l\'annulation').on('click', function () {
                        acceptCancelExchange(toExchange.idExchange, (toExchange.idUV == task.idUV ? toExchange.idUV2 : toExchange.idUV), task.idUV);
                      }).appendTo(interraction);
                    else
                      button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-times" aria-hidden="true"></i> Annuler ma demande').on('click', function () {
                        cancelAskCancelExchange(toExchange.idExchange, (toExchange.idUV == task.idUV ? toExchange.idUV2 : toExchange.idUV), task.idUV);
                      }).appendTo(interraction);
                  }
                  else if (toExchange.enabled == '1') {
                    bgColor = '#0000FF';

                    if (window.get.mode_type == 'sent')
                      button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-undo" aria-hidden="true"></i> Annuler ma proposition').on('click', function () {
                        cancelAskExchange(toExchange.idExchange, toExchange.idUV, toExchange.idUV2);
                      }).appendTo(interraction);
                    else {
                      button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-check" aria-hidden="true"></i> Accepter l\'échange').on('click', function () {
                        acceptExchange(toExchange.idExchange, toExchange.idUV2, toExchange.idUV);
                      }).appendTo(interraction);
                      button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-times" aria-hidden="true"></i> Refuser l\'échange').on('click', function () {
                        refuseExchange(toExchange.idExchange, toExchange.idUV2, toExchange.idUV);
                      }).appendTo(interraction);
                    }
                  }
                  else {
                    type = ' - Indisponible';
                    bgColor = '#333333';

                    if (window.get.mode_type == 'sent') {
                      type = '(erreur: contacte le SIMDE stp)';
                      button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-exclamation-circle" aria-hidden="true"></i> Erreur d\'état de l\'échange').prop('disabled', true).appendTo(interraction);
                    }
                    else
                      button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-handshake-o" aria-hidden="true"></i> Proposer l\'échange').on('click', function () {
                        askExchange(toExchange.idUV2, toExchange.idUV);
                      }).appendTo(interraction);
                  }
                }
                else {
                  if (toExchange.exchanged == '1') {
                    type = ' - Acceptée';
                    bgColor = '#00FF00';

                    button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-chevron-left" aria-hidden="true"></i> Annuler l\'échange').on('click', function () {
                      askCancelExchange(toExchange.idExchange, (toExchange.idUV == task.idUV ? toExchange.idUV2 : toExchange.idUV), task.idUV);
                    }).appendTo(interraction);
                  }
                  else {
                    type = ' - Refusée';
                    bgColor = '#FF0000';

                    button.clone().addClass('option').css('background-color', bgColor).css('color', getFgColor(bgColor)).html('<i class="fa fa-handshake-o" aria-hidden="true"></i> Proposer un autre créneau').on('click', function () {
                      window.get = {
                        'mode': 'modifier',
                        'mode_type': 'uvs_followed',
                        'uv': toExchange.uv,
                        'type': toExchange.type
                      };

                      generate();
                    }).appendTo(interraction);
                  }
                }
                style['background-color'] = bgColor;
                style.color = getFgColor(bgColor);
                card.children('.note').first().append(type);
                button.clone().addClass('option').css('background-color', bgColor).css('color', style.color).html("<i class='fa fa-info' aria-hidden='true'></i> Informations").on('click', function () { seeUVInformations(task); }).prependTo(interraction);

                if (!task.description)
                  task.description = 'En ' + (toExchange.available == '1' && toExchange.exchanged == '1' ? 'récupération' : 'échange') + ' du mien du ' + window.headers[toExchange.day].toLowerCase() + ' de ' + (toExchange.timeText ? toExchange.timeText.replace('-', ' à ') : toExchange.begin + ' à ' + toExchange.end);
              }

              interraction.prepend($('<div></div>').addClass('description').html(task.description));
              interraction.appendTo(card);
              card.on('click', function () {
                cardClick(task);
              });
            }
          }
        }
        else if (group.type == 'room') {
          card.on('click', function () {
            cardRoomClick(task);
          });
        }
        else if (isUV || group.type == 'calendar') {
          interraction = div.clone().addClass('interraction');
          option = button.clone().addClass('option').css('background-color', task.bgColor).css('color', getFgColor(task.bgColor));

          if (task.description)
            interraction.prepend($('<div></div>').addClass('description').html(task.description));

          if (group.type == 'uv_followed') {
            option.clone().html("<i class='fa fa-calendar-o' aria-hidden='true'></i> Voir l'edt de l'UV").on('click', function () { seeUV(task.subject); }).appendTo(interraction);
            option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Informations").on('click', function () { seeUVInformations(task); }).appendTo(interraction);

            if (uvs && uvs.search(task.subject) != -1)
              option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Echanger cet UV").on('click', function () { seeOthers(task.subject, task.type, task.idUV, 'uv-' + task.subject + '-' + task.idUV); }).appendTo(interraction);


            if (window.sessionLogin == group.info) {
              colorButton = button.clone().addClass('colorButton');
              window.colors.forEach(function (color) {
                if (color == task.bgColor)
                  colorButton.clone().html('<i class="fa fa-times" aria-hidden="true"></i>').on('click', function () { changeColor(task.idUV, '#NULL'); }).css('background-color', color).css('color', getFgColor(color)).appendTo(interraction);
                else
                  colorButton.clone().text('0').on('click', function () { changeColor(task.idUV, color); }).css('background-color', color).css('color', color).appendTo(interraction);
              });

              $('<i></i>').addClass('colorButton fa fa-pencil-square-o').on('click', function () { $(this).next().click(); }).css('color', '#000000').appendTo(interraction);
              $('<input>').addClass('colorButton').on('change', function () { changeColor(task.idUV, this.value); }).attr('type', 'color').css('display', 'none').appendTo(interraction);
            }
          }
          else if (group.type == 'uv') {
            if (uvs.search(task.subject) != -1)
              option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Echanger cet UV").on('click', function () { seeOthers(task.subject, task.type, task.idUV, task.id); }).appendTo(interraction);

            option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Informations").on('click', function () { seeUVInformations(task); }).appendTo(interraction);
          }

          interraction.appendTo(card);
          card.on('click', function () {
            cardClick(task);
          });
        }
        else if (isEvent) {
          interraction = div.clone().addClass('interraction');
          option = button.clone().addClass('option').css('background-color', task.bgColor).css('color', getFgColor(task.bgColor));

          option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Informations").on('click', function () { seeEventInformations(task); }).appendTo(interraction);
          if (task.creator == window.sessionLogin)
            option.clone().html("<i class='fa fa-edit' aria-hidden='true'></i> Modifier").on('click', function () { createEvenement(task.day, task.timeText.split('-')[0], task.timeText.split('-')[1], task.subject, task.description, task.location, task.type, task.idEvent); }).appendTo(interraction);
          else
            option.clone().html("<i class='fa fa-times' aria-hidden='true'></i> Ne plus suivre").on('click', function () { deleteEvenement(task.idEvent); }).appendTo(interraction);

          colorButton = button.clone().addClass('colorButton');
          window.colors.forEach(function (color) {
            if (color == task.bgColor)
              colorButton.clone().html('<i class="fa fa-times" aria-hidden="true"></i>').on('click', function () { changeColor(task.idEvent, '#NULL', 'idEvent'); }).css('background-color', color).css('color', getFgColor(color)).appendTo(interraction);
            else
              colorButton.clone().text('0').on('click', function () { changeColor(task.idEvent, color, 'idEvent'); }).css('background-color', color).css('color', color).appendTo(interraction);
          });

          $('<i></i>').addClass('colorButton fa fa-pencil-square-o').on('click', function () { $(this).next().click(); }).css('color', '#000000').appendTo(interraction);
          $('<input>').addClass('colorButton').on('change', function () { changeColor(task.idEvent, this.value, 'idEvent'); }).attr('type', 'color').css('display', 'none').appendTo(interraction);

          interraction.appendTo(card);
          card.on('click', function () {
            cardClick(task);
          });
        }
      }

      for (var key in style) {
        card.css(key, style[key]);
      }

      card.appendTo(schedulerTasks);
    });
  });
}

var generateCalendar = function (tasks, sides, uvs, daysInfo) {
  console.log(tasks, sides, uvs, daysInfo);
  console.time('calendar');
  var div = $('<div></div>');
  var schedule = div.clone().addClass('calendar-container');
  var scheduleHeader = div.clone().addClass('calendar-headers');
  var scheduleMain = div.clone().addClass('calendar-main');
  var scheduleTimeline = div.clone().addClass('calendar-main-timeline');
  var scheduleBody = div.clone().addClass('calendar-main-body');

  var currentDay = (date.getDay() + 6) % 7;
  var hour = date.getHours();

  // On check si aujourd'hui fais parti de la semaine choisie
  if (Date.parse(window.get.week) + (1000 * 60 * 60 * 24 * (currentDay + 2)) < Date.now())
    currentDay = 7;
  else if (Date.parse(window.get.week) > Date.now())
    currentDay = -1;

  // Préparation du header
  var classDay = '';
  window.headers.forEach(function (element, day) {
    if ((window.get.mode === 'semaine' || window.get.mode === 'organiser') && $('#withWeekTool').prop('checked')) {
      if (day < currentDay)
        classDay = 'passedDay';
      else if (day == currentDay)
        classDay = 'currentDay';
      else
        classDay = 'futureDay';
    }

    div.clone().addClass(classDay).text(((window.get.mode === 'semaine' || window.get.mode === 'organiser') && daysInfo[day] && $('#withAlternanceTool').prop('checked')) ? daysInfo[day] : element).appendTo(scheduleHeader);
  }, this);
  // Pour améliorer la propreté du tableau
  div.clone().css('flex', '0').appendTo(scheduleHeader);

  // Ajout du header
  schedule.append(scheduleHeader);

  // Préparation des colonnes de chaque jour
  var gridColumnElement = [];
  for (var side = 0; side < sides; side++)
    gridColumnElement[side] = div.clone();

  // Création des demi-heures
  var classHour = '';
  for (let i = 0; i < (window.HOUR_MAX - window.HOUR_MIN) * 2; i++) {
    var time = (window.HOUR_MIN + i / 2);

    if ((window.get.mode === 'semaine' || window.get.mode === 'organiser') && $('#withWeekTool').prop('checked')) {
      if (currentDay == -1)
        classHour = 'futureHour';
      else if (currentDay == 7)
        classHour = 'passedHour';
      else if (time > hour)
        classHour = 'futureHour';
      else if (time < hour)
        classHour = 'passedHour';
      else
        classHour = 'currentHour';
    }
    else
      classHour = '';

    // Création des demi-horaires
    div.clone().addClass(classHour).text(((time == parseInt(time, 10)) ? (time < 10 ? '0' : '') + Math.floor(time) + (Math.ceil(time) > Math.floor(time) ? ':30' : ':00') : '')).appendTo(scheduleTimeline)

    // Création des demi-cases (* ${side} pour chaque heure)
    for (side = 0; side < sides; side++)
      gridColumnElement[side].append(div.clone().addClass('calendar-cell' + sides + side));
  }

  // Ajout de la case toute la journée
  if (window.get.mode === 'semaine' || window.get.mode === 'organiser' || (window.get.mode === 'classique' && window.get.mode_type === 'rooms')) {
    div.clone().addClass('allDay').addClass((window.HOUR_MAX > hour && $('#withWeekTool').prop('checked') && currentDay != 7) ? 'futureHour' : ($('#withWeekTool').prop('checked') && (window.get.mode === 'semaine' || window.get.mode === 'organiser') ? (currentDay != -1 && window.HOUR_MAX > hour ? 'passedHour' : 'currentHour') : '')).appendTo(scheduleTimeline);
    gridColumnElement[0].append(div.clone().addClass('allDay').addClass('calendar-cell' + '10'));
  }

  // On peuple l'affichage
  for (var j = 0; j < window.headers.length * sides; j++) {
    (function (j) {
      var schedulerTasks = div.clone().addClass('calendar-tasks');
      generateCards(schedulerTasks, tasks, j / sides, sides, uvs);

      var grid = gridColumnElement[j % sides].clone();

      if ((window.get.mode === 'semaine' || window.get.mode === 'organiser') && $('#withWeekTool').prop('checked')) {
        if (j == currentDay) {
          grid = div.clone().addClass('currentDay');

          for (var i = 0; i < (window.HOUR_MAX - window.HOUR_MIN) * 2; i++) {
            (function (i) {
              cell = div.clone().addClass('calendar-cell10');
              if ((window.HOUR_MIN + i / 2) < hour)
                cell.addClass('passedHour');
              else if ((window.HOUR_MIN + i / 2) === hour)
                cell.addClass('currentHour');
              else
                cell.addClass('futureHour').html('<i class="fa fa-plus" aria-hidden="true"></i>').on('click', function () {
                  console.log(j)
                  console.log(i)
                  createEvenement(j, (window.HOUR_MIN + i / 2), (window.HOUR_MIN + i / 2) + 1);
                });

              grid.append(cell);
            })(i);
          }

          grid.append(div.clone().addClass('allDay').addClass(window.HOUR_MAX > hour ? 'futureHour' : 'currentHour').addClass('calendar-cell' + '10').html('<i class="fa fa-plus" aria-hidden="true"></i>').on('click', function () {
            createEvenement(j, 0, 24);
          }));
        }
        else if (j > currentDay) {
          grid.addClass('futureDay');

          for (var i = 0; i < (window.HOUR_MAX - window.HOUR_MIN) * 2; i++) {
            (function (i) {
              $(grid.children()[i]).addClass('futureHour').html('<i class="fa fa-plus" aria-hidden="true"></i>').on('click', function () {
                createEvenement(j, (window.HOUR_MIN + i / 2), (window.HOUR_MIN + i / 2) + 1);
              });
            })(i);
          }

          $(grid.children()[(window.HOUR_MAX - window.HOUR_MIN) * 2]).addClass('futureHour').html('<i class="fa fa-plus" aria-hidden="true"></i>').on('click', function () {
            createEvenement(j, 0, 24);
          });
        }
        else {
          grid.addClass('passedDay');

          for (var i = 0; i < (window.HOUR_MAX - window.HOUR_MIN) * 2 + 1; i++)
            $(grid.children()[i]).addClass('passedHour');
        }
      }

      grid.addClass('days');
      grid.prepend(schedulerTasks);
      grid.appendTo(scheduleBody);
    })(j);
  }

  scheduleMain.append(scheduleTimeline);
  scheduleMain.append(scheduleBody);

  schedule.append(scheduleMain);

  $('#calendar-container').html(schedule);

  //  Animer l'affichage d'une UV à échanger lors de la réception d'un mail ou d'un reload
  if (window.get.id) {
    setTimeout(function () {
      $('#' + window.get.id).click();
      delete window.get.id;
    }, 100);
  }
  console.timeEnd('calendar');
};

// Il faut modifier l'affichage du calendrier en fonction de la taille de l'écran
var setCalendar = function (day) {
  var headers = $('.calendar-headers div');
  var days = $('.calendar-main-body .days');
  var sides = days.length / (headers.length - 1);
  var width = $(window).width();

  var numbers = [200.4, 339.2, 478, 616.8, 755.6, 894.4, 1033.2];
  var length = window.headers.length;
  var number = length;

  var focusedDay = day;

  if (focusedDay === undefined || focusedDay < 0 || focusedDay >= length) // undefined nécessaire car 0 est une bonne valeur ^^'
    focusedDay = window.focusedDay;

  var indexs = [focusedDay];

  for (var i = 0; i < length; i++) {
    if (numbers[i] > width) {
      number = i;
      break;
    }
  }

  $('#calendar-container').width(numbers[number - 1] || 0);
  $('#otherDay').css('display', 'block').css('padding-right', numbers[number - 1] - 60);

  if (number >= length) {
    $('#otherDay').css('display', 'none');

    for (var i = 0; i < length; i++)
      indexs.push(i);
  }
  else if (number === 2) {
    if (focusedDay + 1 === length)
      indexs.push(focusedDay - 1);
    else
      indexs.push(focusedDay + 1);
  }
  else if (number === 3) {
    if (focusedDay + 1 === length) {
      indexs.push(focusedDay - 1);
      indexs.push(focusedDay - 2);
    }
    else {
      indexs.push(focusedDay + 1);
      if (focusedDay === 0)
        indexs.push(focusedDay + 2);
      else
        indexs.push(focusedDay - 1);
    }
  }
  else if (number === 4) {
    if (focusedDay + 1 === length) {
      indexs.push(focusedDay - 1);
      indexs.push(focusedDay - 2);
      indexs.push(focusedDay - 3);
    }
    else {
      indexs.push(focusedDay + 1);
      if (focusedDay === 0) {
        indexs.push(focusedDay + 2);
        indexs.push(focusedDay + 3);
      }
      else {
        indexs.push(focusedDay - 1);
        if (focusedDay + 2 === length)
          indexs.push(focusedDay - 2);
        else
          indexs.push(focusedDay + 2);
      }
    }
  }
  else if (number === 5) {
    if (focusedDay + 1 === length) {
      indexs.push(focusedDay - 1);
      indexs.push(focusedDay - 2);
      indexs.push(focusedDay - 3);
      indexs.push(focusedDay - 4);
    }
    else {
      indexs.push(focusedDay + 1);
      if (focusedDay === 0) {
        indexs.push(focusedDay + 2);
        indexs.push(focusedDay + 3);
        indexs.push(focusedDay + 4);
      }
      else {
        indexs.push(focusedDay - 1);
        if (focusedDay + 2 === length) {
          indexs.push(focusedDay - 2);
          indexs.push(focusedDay - 3);
        }
        else {
          indexs.push(focusedDay + 2);
          if (focusedDay - 1 === 0)
            indexs.push(focusedDay + 3);
          else
            indexs.push(focusedDay - 2);
        }
      }
    }
  }
  else if (number === 6) {
    if (focusedDay + 1 === length) {
      indexs.push(focusedDay - 1);
      indexs.push(focusedDay - 2);
      indexs.push(focusedDay - 3);
      indexs.push(focusedDay - 4);
      indexs.push(focusedDay - 5);
    }
    else {
      indexs.push(focusedDay + 1);
      if (focusedDay === 0) {
        indexs.push(focusedDay + 2);
        indexs.push(focusedDay + 3);
        indexs.push(focusedDay + 4);
        indexs.push(focusedDay + 5);
      }
      else {
        indexs.push(focusedDay - 1);
        if (focusedDay + 2 === length) {
          indexs.push(focusedDay - 2);
          indexs.push(focusedDay - 3);
          indexs.push(focusedDay - 4);
        }
        else {
          indexs.push(focusedDay + 2);
          if (focusedDay - 1 === 0) {
            indexs.push(focusedDay + 3);
            indexs.push(focusedDay + 4);
          }
          else {
            indexs.push(focusedDay - 2);
            if (focusedDay + 3 === length)
              indexs.push(focusedDay - 3);
            else {
              indexs.push(focusedDay + 3);
            }
          }
        }
      }
    }
  }

  var diff = false;
  headers.each(function (index) {
    if (index >= length)
      return

    if (indexs.indexOf(index) === -1) {
      if ($(this).css('display') === 'block')
        diff = true;

      $(this).css('display', 'none');
      $(days[index * sides]).css('display', 'none');
      if (sides === 2)
        $(days[index * sides + 1]).css('display', 'none');
    }
    else {
      if ($(this).css('display') === 'none')
        diff = true;

      $(this).css('display', 'block');
      $(days[index * sides]).css('display', 'block');
      if (sides === 2)
        $(days[index * sides + 1]).css('display', 'block');
    }
  });

  // On ne change pas de jour focus si l'affichage ne change pas, par contre on le réduit au min pour appliquer un changement (éviter d'appuyer 5 fois sur le bouton pour rien par ex)
  if (diff)
    window.focusedDay = focusedDay;
  else {
    if (window.focusedDay - focusedDay === 1) {
      for (var i = focusedDay - 1; i > 0; i--) {
        setCalendar(i);
        if (focusedDay - window.focusedDay != 1)
          break;
      }
    }
    else if (focusedDay - window.focusedDay === 1) {
      for (var i = focusedDay + 1; i < length; i++) {
        setCalendar(i);
        if (window.focusedDay - focusedDay != 1)
          break;
      }
    }
  }
}
