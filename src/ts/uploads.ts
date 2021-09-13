/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-jeditable/src/jquery.jeditable.js';
import '@fancyapps/fancybox/dist/jquery.fancybox.js';
import { Target } from './interfaces';
import { notif, displayMolFiles, display3DMolecules, getEntity } from './misc';
import { displayPlasmidViewer } from './ove';
import i18next from 'i18next';
import Upload from './Upload.class';

document.addEventListener('DOMContentLoaded', () => {
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  const pages = ['edit', 'view'];
  if (!pages.includes(about.page)) {
    return;
  }

  displayMolFiles();
  display3DMolecules();
  displayPlasmidViewer(about);
  const entity = getEntity();
  const UploadC = new Upload(entity);

  // make file comments editable
  $(document).on('mouseenter', '.file-comment', function() {
    ($('.editable') as any).editable(function(input: string) {
      UploadC.update(input, $(this).data('id'), Target.Comment);
      return (input);
    }, {
      tooltip : i18next.t('upload-file-comment'),
      placeholder: i18next.t('upload-file-comment'),
      name : 'fileComment',
      onedit: function() {
        if ($(this).text() === 'Click to add a comment') {
          $(this).text('');
        }
      },
      onblur : 'submit',
      style : 'display:inline',
    });
  });

  // Export mol in png
  $(document).on('click', '.saveAsImage', function() {
    const molCanvasId = $(this).data('canvasid');
    const png = (document.getElementById(molCanvasId) as HTMLCanvasElement).toDataURL();
    $.post('app/controllers/EntityAjaxController.php', {
      saveAsImage: true,
      realName: $(this).data('name'),
      content: png,
      id: about.id,
      type: about.type,
    }).done(function(json) {
      notif(json);
      if (json.res) {
        $('#filesdiv').load('?mode=edit&id=' + $('#info').data('id') + ' #filesdiv > *', function() {
          displayMolFiles();
        });
      }
    });
  });

  function processNewFilename(event, original: HTMLElement, parent: HTMLElement): void {
    if (event.key === 'Enter' || event.type === 'blur') {
      const newFilename = (event.target as HTMLInputElement).value;
      UploadC.update(newFilename, event.target.dataset.id, Target.RealName).then(json => {
        event.target.remove();
        // change the link text with the new one
        original.textContent = json.res ? newFilename : original.textContent;
        parent.prepend(original);
      });
    }
  }

  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // RENAME UPLOAD
    if (el.matches('[data-action="rename-upload"]')) {
      // find the corresponding filename element
      // we replace the parent span to also remove the link for download
      const filenameLink = document.getElementById('upload-filename_' + el.dataset.id);
      const filenameInput = document.createElement('input');
      filenameInput.dataset.id = el.dataset.id;
      filenameInput.value = filenameLink.textContent;
      const parentSpan = filenameLink.parentElement;
      filenameInput.addEventListener('blur', event => {
        processNewFilename(event, filenameLink, parentSpan);
      });
      filenameInput.addEventListener('keypress', event => {
        processNewFilename(event, filenameLink, parentSpan);
      });
      filenameLink.replaceWith(filenameInput);

    // REPLACE UPLOAD
    } else if (el.matches('[data-action="replace-upload"]')) {
      document.getElementById('replaceUploadForm_' + el.dataset.uploadid).hidden = false;

    // DESTROY UPLOAD
    } else if (el.matches('[data-action="destroy-upload"]')) {
      const uploadId = parseInt(el.dataset.uploadid);
      if (confirm(i18next.t('generic-delete-warning'))) {
        UploadC.destroy(uploadId).then(json => {
          if (json.res) {
            $('#filesdiv').load('?mode=edit&id=' + entity.id + ' #filesdiv > *');
          }
        });
      }
    }
  });

  // ACTIVATE FANCYBOX
  $('[data-fancybox]').fancybox();

  // Create an observer instance linked to the callback function(mutationList, observer)
  const filesDivObserver = new MutationObserver(() => {
    displayMolFiles();
    display3DMolecules(true);
    displayPlasmidViewer(about);
  });

  // Start observing the target node for configured mutations
  filesDivObserver.observe(document.getElementById('filesdiv'), {childList: true});
});
