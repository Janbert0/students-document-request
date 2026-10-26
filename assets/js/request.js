/* New-request form: shows processing time and clearance needs for the chosen document. */
(function () {
  'use strict';

  var select = document.getElementById('document_type_id');
  var summary = document.getElementById('doc-summary');

  if (!select || !summary) {
    return;
  }

  var daysField = summary.querySelector('[data-field="days"]');
  var requirementsField = summary.querySelector('[data-field="requirements"]');

  function render() {
    var option = select.options[select.selectedIndex];

    if (!option || option.value === '') {
      summary.hidden = true;
      return;
    }

    var days = parseInt(option.getAttribute('data-days'), 10);
    daysField.textContent = days === 1 ? '1 working day' : days + ' working days';
    requirementsField.textContent = option.getAttribute('data-requirements') || 'None';
    summary.hidden = false;
  }

  select.addEventListener('change', render);
  render();
})();
