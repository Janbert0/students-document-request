/* Shared behaviour: mobile navigation, confirmation prompts, and print buttons. */
(function () {
  'use strict';

  var menuToggle = document.querySelector('[data-menu-toggle]');
  var sidebar = document.getElementById('sidebar');

  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
      var isOpen = sidebar.classList.toggle('is-open');
      menuToggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  // Forms marked with data-confirm ask before submitting.
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-print]').forEach(function (button) {
    button.addEventListener('click', function () {
      window.print();
    });
  });
})();
