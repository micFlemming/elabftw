/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import EntityClass from './Entity.class';
import { EntityType } from './interfaces';
import { notif } from './misc';
import i18next from 'i18next';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap/js/src/modal.js';
import { Calendar } from '@fullcalendar/core';
import bootstrapPlugin from '@fullcalendar/bootstrap';
import caLocale from '@fullcalendar/core/locales/ca';
import deLocale from '@fullcalendar/core/locales/de';
import enLocale from '@fullcalendar/core/locales/en-gb';
import esLocale from '@fullcalendar/core/locales/es';
import frLocale from '@fullcalendar/core/locales/fr';
import idLocale from '@fullcalendar/core/locales/id';
import itLocale from '@fullcalendar/core/locales/it';
import jaLocale from '@fullcalendar/core/locales/ja';
import koLocale from '@fullcalendar/core/locales/ko';
import nlLocale from '@fullcalendar/core/locales/nl';
import plLocale from '@fullcalendar/core/locales/pl';
import ptLocale from '@fullcalendar/core/locales/pt';
import ptbrLocale from '@fullcalendar/core/locales/pt-br';
import ruLocale from '@fullcalendar/core/locales/ru';
import skLocale from '@fullcalendar/core/locales/sk';
import slLocale from '@fullcalendar/core/locales/sl';
import zhcnLocale from '@fullcalendar/core/locales/zh-cn';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import dayGridPlugin from '@fullcalendar/daygrid';
import { config } from '@fortawesome/fontawesome-svg-core';
import Tab from './Tab.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/team.php') {
    return;
  }

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  const info = document.getElementById('info').dataset;

  // use this setting to prevent bug in fullcalendar
  // see https://github.com/fullcalendar/fullcalendar/issues/5544
  // FIXME: this line fixes the issue above but will mess up the notification bell active status!
  config.autoReplaceSvg = 'nest';

  // if we show all items, they are not editable
  let editable = true;
  let selectable = true;
  if (info.all) {
    editable = false;
    selectable = false;
  }
  // get the start parameter from url and use that as start time if it's there
  const params = new URLSearchParams(document.location.search.substring(1));
  const start = params.get('start');
  let selectedDate = new Date().valueOf();
  if (start !== null) {
    selectedDate = new Date(decodeURIComponent(start)).valueOf();
  }

  // bind to the element #scheduler
  const calendarEl: HTMLElement = document.getElementById('scheduler');

  // SCHEDULER
  const calendar = new Calendar(calendarEl, {
    plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin, bootstrapPlugin ],
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'timeGridWeek,listWeek,dayGridMonth',
    },
    themeSystem: 'bootstrap',
    // i18n
    // all available locales
    locales: [ caLocale, deLocale, enLocale, esLocale, frLocale, itLocale, idLocale, jaLocale, koLocale, nlLocale, plLocale, ptLocale, ptbrLocale, ruLocale, skLocale, slLocale, zhcnLocale ],
    // selected locale
    locale: info.calendarlang,
    initialView: 'timeGridWeek',
    // allow selection of range
    selectable: selectable,
    // draw an event while selecting
    selectMirror: true,
    // if no item is selected, the calendar is not editable
    editable: editable,
    // allow "more" link when too many events
    dayMaxEventRows: true,
    // set the date loaded
    initialDate: selectedDate,
    // display a line for the time of now
    nowIndicator: true,
    // load the events as JSON
    eventSources: [
      {
        url: 'app/controllers/SchedulerController.php',
        extraParams: {
          item: info.item,
        },
      },
    ],
    // first day is monday
    firstDay: 1,
    // remove possibility to book whole day, might add it later
    allDaySlot: false,
    // adjust the background color of event to the color of the item type
    eventBackgroundColor: $('#dropdownMenu1 > span:nth-child(1)').css('color'),
    // selection
    select: function(info): void {
      if (!editable) { return; }
      const title = prompt(i18next.t('comment-add'));
      if (!title) {
        // make the selected area disappear
        calendar.unselect();
        return;
      }
      // get the item id from url
      const params = new URLSearchParams(document.location.search.slice(1));
      const itemid = parseInt(params.get('item'), 10);
      if (!Number.isSafeInteger(itemid)) {
        calendar.unselect();
        return;
      }
      $.post('app/controllers/SchedulerController.php', {
        create: true,
        start: info.startStr,
        end: info.endStr,
        title: title,
        item: itemid,
      }).done(function(json) {
        notif(json);
        if (json.res) {
          // FIXME: it would be best to just properly render the event instead of reloading the whole page
          window.location.replace(`team.php?tab=1&item=${itemid}&start=${encodeURIComponent(info.startStr)}`);
        }
      });
    },
    // on click activate modal window
    eventClick: function(info): void {
      if (!editable) { return; }
      $('[data-action="scheduler-rm-bind"]').hide();
      ($('#eventModal') as JQuery).modal('toggle');
      // delete button in modal
      $('#deleteEvent').on('click', function(): void {
        $.post('app/controllers/SchedulerController.php', {
          destroy: true,
          id: info.event.id,
        }).done(function(json) {
          notif(json);
          if (json.res) {
            info.event.remove();
            ($('#eventModal') as JQuery).modal('toggle');
          }
        });
      });
      // fill the bound div
      $('#eventTitle').text(info.event.title);
      if (info.event.extendedProps.experiment != null) {
        $('#eventBoundExp').html('Event is bound to an <a href="experiments.php?mode=view&id=' + info.event.extendedProps.experiment + '">experiment</a>.');
        $('[data-action="scheduler-rm-bind"][data-type="experiment"]').show();
      }
      if (info.event.extendedProps.item_link != null) {
        $('#eventBoundDb').html('Event is bound to an <a href="database.php?mode=view&id=' + info.event.extendedProps.item_link + '">item</a>.');
        $('[data-action="scheduler-rm-bind"][data-type="item_link"]').show();
      }
      // bind an experiment to the event
      $('[data-action="scheduler-bind-entity"]').on('click', function(): void {
        const entityid = parseInt(($('#' + $(this).data('input')).val() as string), 10);
        if (entityid > 0) {
          $.post('app/controllers/SchedulerController.php', {
            bind: true,
            id: info.event.id,
            entityid: entityid,
            type: $(this).data('type'),
          }).done(function(json) {
            notif(json);
            if (json.res) {
              $('#bindinput').val('');
              ($('#eventModal') as JQuery).modal('toggle');
              window.location.replace('team.php?tab=1&item=' + $('#info').data('item') + '&start=' + encodeURIComponent(info.event.start.toString()));
            }
          });
        }
      });
      // remove the binding
      $('[data-action="scheduler-rm-bind"]').on('click', function(): void {
        $.post('app/controllers/SchedulerController.php', {
          unbind: true,
          id: info.event.id,
          type: $(this).data('type'),
        }).done(function(json) {
          ($('#eventModal') as JQuery).modal('toggle');
          notif(json);
          window.location.replace('team.php?tab=1&item=' + $('#info').data('item') + '&start=' + encodeURIComponent(info.event.start.toString()));
        });
      });
      // BIND AUTOCOMPLETE
      // TODO refactor this
      // NOTE: previously the input div had ui-front jquery ui class to make the autocomplete list show properly, but with the new item input below
      // it didn't work well, so now the automplete uses appendTo option
      const cacheExp = {};
      $('#bindexpinput').autocomplete({
        appendTo: '#binddivexp',
        source: function(request: Record<string, string>, response: (data) => void): void {
          const term = request.term;
          if (term in cacheExp) {
            response(cacheExp[term]);
            return;
          }
          $.getJSON('app/controllers/EntityAjaxController.php?source=experiments', request, function(data) {
            cacheExp[term] = data;
            response(data);
          });
        },
      });
      const cacheDb = {};
      $('#binddbinput').autocomplete({
        appendTo: '#binddivdb',
        source: function(request: Record<string, string>, response: (data) => void): void {
          const term = request.term;
          if (term in cacheDb) {
            response(cacheDb[term]);
            return;
          }
          $.getJSON('app/controllers/EntityAjaxController.php?source=items', request, function(data) {
            cacheDb[term] = data;
            response(data);
          });
        },
      });

    },
    // on mouse enter add shadow and show title
    eventMouseEnter: function(info): void {
      if (editable) {
        $(info.el).css('box-shadow', '5px 4px 4px #474747');
      }
      $(info.el).attr('title', info.event.title);
    },
    // remove the box shadow when mouse leaves
    eventMouseLeave: function(info): void {
      $(info.el).css('box-shadow', 'unset');
    },
    // a drop means we change start date
    eventDrop: function(info): void {
      if (!editable) { return; }
      $.post('app/controllers/SchedulerController.php', {
        updateStart: true,
        delta: info.delta,
        id: info.event.id,
      }).done(function(json) {
        if (!json.res) {
          info.revert();
        }
        notif(json);
      });
    },
    // a resize means we change end date
    eventResize: function(info): void {
      if (!editable) { return; }
      $.post('app/controllers/SchedulerController.php', {
        updateEnd: true,
        end: info.endDelta,
        id: info.event.id,
      }).done(function(json) {
        if (!json.res) {
          info.revert();
        }
        notif(json);
      });
    },
  });

  // only try to render if we actually have some bookable items
  if (calendarEl.dataset.render === 'true') {
    calendar.render();
    calendar.updateSize();
  }

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    const TemplateC = new EntityClass(EntityType.Template);
    // IMPORT TPL
    if (el.matches('[data-action="import-template"]')) {
      TemplateC.duplicate(parseInt(el.dataset.id)).then(json => notif(json));

    // DESTROY TEMPLATE
    } else if (el.matches('[data-action="destroy-template"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        TemplateC.destroy(parseInt(el.dataset.id))
          .then(() => window.location.replace('team.php?tab=3'))
          .catch((e) => notif({'res': false, 'msg': e.message}));
      }
    }
  });
});
