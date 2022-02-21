/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any; // eslint-disable-line @typescript-eslint/no-explicit-any

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/search.php') {
    return;
  }
  // scroll to anchor if there is a search
  const params = new URLSearchParams(document.location.search.slice(1));
  if (params.has('type')) {
    window.location.hash = '#anchor';
  }

  if ((document.getElementById('searchin') as HTMLSelectElement).value === 'database') {
    document.getElementById('searchStatus').toggleAttribute('hidden', true);
    document.getElementById('searchCategory').toggleAttribute('hidden', false);
  }

  document.getElementById('searchin').addEventListener('change', event => {
    const value = (event.target as HTMLSelectElement).value;
    if (value === 'experiments') {
      document.getElementById('searchStatus').toggleAttribute('hidden', false);
      document.getElementById('searchCategory').toggleAttribute('hidden', true);
    }
    if (value === 'database') {
      document.getElementById('searchStatus').toggleAttribute('hidden', true);
      document.getElementById('searchCategory').toggleAttribute('hidden', false);
    }
    if (value !== 'database' && value !== 'experiments') {
      document.getElementById('searchStatus').toggleAttribute('hidden', true);
      document.getElementById('searchCategory').toggleAttribute('hidden', true);
    }
  });

  const extendedArea = (document.getElementById('extendedArea') as HTMLTextAreaElement);

  // Submit form with ctrl+enter from within textarea
  extendedArea.addEventListener('keydown', event => {
    if ((event.keyCode == 10 || event.keyCode == 13) && (event.ctrlKey || event.metaKey)) {
      (document.getElementById('searchButton') as HTMLButtonElement).click();
    }
  });

  function getOperator(): string {
    const operatorSelect = document.getElementById('dateOperator') as HTMLSelectElement;
    return operatorSelect.value;
  }

  // a filter helper can be a select or an input (for date), so we need a function to get its value
  function getFilterValueFromElement(element: HTMLElement): string {
    if (element instanceof HTMLSelectElement) {
      if (element.options[element.selectedIndex].dataset.action === 'clear') {
        return '';
      }
      if (element.id === 'dateOperator') {
        const date = (document.getElementById('date') as HTMLInputElement).value;
        const dateTo = (document.getElementById('dateTo') as HTMLInputElement).value;
        if (date === '') {
          return '';
        }
        if (dateTo === '') {
          return getOperator() + date;
        }
        return date + '..' + dateTo;
      }
      return `${element.options[element.selectedIndex].innerText}`;
    }
    if (element instanceof HTMLInputElement) {
      if (element.id === 'date') {
        if (element.value === '') {
          return '';
        }
        const dateTo = (document.getElementById('dateTo') as HTMLInputElement).value;
        if (dateTo === '') {
          return getOperator() + element.value;
        }
        return element.value + '..' + dateTo;
      }
      if (element.id === 'dateTo') {
        const date = (document.getElementById('date') as HTMLInputElement).value;
        if (date === '') {
          return '';
        }
        if (element.value === '') {
          return getOperator() + date;
        }
        return date + '..' + element.value;
      }
    }
    return '😶';
  }

  // add a change event listener to all elements that helps constructing the query string
  document.querySelectorAll('.filterHelper').forEach(el => {
    el.addEventListener('change', event => {
      const elem = event.currentTarget as HTMLElement;
      const curVal = extendedArea.value;

      const hasInput = curVal.length != 0;
      const hasSpace = curVal.endsWith(' ');
      const addSpace = hasInput ? (hasSpace ? '' : ' ') : '';

      // look if the filter key already exists in the extendedArea
      // paste the regex on regex101.com to understand it, note that here \ need to be escaped
      const regex = new RegExp(elem.dataset.filter + ':(?:(?:"((?:\\\\"|(?:(?!")).)+)")|(?:\'((?:\\\\\'|(?:(?!\')).)+)\')|[\\S]+)\\s?');
      const found = curVal.match(regex);
      // don't add quotes unless we need them (space exists)
      let quotes = '';
      const filterValue = getFilterValueFromElement(elem);
      if (filterValue.includes(' ')) {
        quotes = '"';
      }
      // default value is clearing everything
      let filter = '';
      // but if we have a correct value, we add the filter
      if (filterValue !== '') {
        filter = `${elem.dataset.filter}:${quotes}${filterValue}${quotes}`;
      }

      // add additional filter at cursor position
      if (key.ctrl || key.command) {
        const pos = extendedArea.selectionStart;
        const val = extendedArea.value;
        const start = val.substring(0, pos);
        const end = val.substring(pos, val.length);
        const hasSpaceBefore = val.substring(pos - 1, pos) === ' ';
        const hasSpaceAfter = val.substring(pos, pos + 1) === ' ';
        const insert = (hasSpaceBefore ? '' : pos === 0 ? '' : ' ') + filter + (hasSpaceAfter ? '' : ' ');
        extendedArea.value = start + insert + end;
        return;
      }

      if (found) {
        extendedArea.value = curVal.replace(regex, filter + (filter === '' ? '' : ' '));
      } else {
        extendedArea.value = extendedArea.value + addSpace + filter;
      }
    });
  });
});
