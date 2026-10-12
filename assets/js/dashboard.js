/* Uma Lafatik — dashboard helpers (confirm modal, toggles, uploads) */
(function () {
  'use strict';

  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  /* ---------------------------------------------------------- modal */
  var backdrop = null;
  var modalTitle = null;
  var modalText = null;
  var modalConfirm = null;
  var modalCancel = null;
  var pendingAction = null;

  function ensureModal() {
    if (backdrop) return;
    backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.innerHTML =
      '<div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">' +
        '<h3 id="modalTitle"></h3>' +
        '<p></p>' +
        '<div class="modal-actions">' +
          '<button class="btn btn-ghost dark btn-sm" data-modal="cancel" type="button">Cancel</button>' +
          '<button class="btn btn-danger btn-sm" data-modal="confirm" type="button">Yes, continue</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(backdrop);
    modalTitle = backdrop.querySelector('#modalTitle');
    modalText = backdrop.querySelector('p');
    modalConfirm = backdrop.querySelector('[data-modal="confirm"]');
    modalCancel = backdrop.querySelector('[data-modal="cancel"]');
    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) close();
    });
    modalCancel.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
  }

  function close() {
    if (!backdrop) return;
    backdrop.classList.remove('open');
    pendingAction = null;
    document.body.style.overflow = '';
  }

  window.ulConfirm = function (title, text, onConfirm, confirmLabel) {
    ensureModal();
    modalTitle.textContent = title;
    modalText.textContent = text;
    modalConfirm.textContent = confirmLabel || 'Yes, continue';
    pendingAction = onConfirm;
    modalConfirm.onclick = function () {
      var fn = pendingAction;
      close();
      if (fn) fn();
    };
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  /* -------------------------------------------------- delete buttons */
  document.querySelectorAll('[data-confirm]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var msg = btn.getAttribute('data-confirm');
      var href = btn.getAttribute('href');
      e.preventDefault();
      window.ulConfirm('Please confirm', msg, function () {
        if (href && href !== '#') window.location.href = href;
      }, 'Delete');
    });
  });

  /* ------------------------------ availability / status toggle switches */
  document.querySelectorAll('[data-toggle-url]').forEach(function (input) {
    input.addEventListener('change', function () {
      var url = input.getAttribute('data-toggle-url');
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = url;
      form.style.display = 'none';
      var token = document.createElement('input');
      token.type = 'hidden';
      token.name = 'csrf_token';
      token.value = CSRF;
      var state = document.createElement('input');
      state.type = 'hidden';
      state.name = 'state';
      state.value = input.checked ? '1' : '0';
      form.appendChild(token);
      form.appendChild(state);
      document.body.appendChild(form);
      form.submit();
    });
  });

  /* ------------------------------ image upload preview */
  var fileInput = document.getElementById('itemImage');
  var preview = document.getElementById('imagePreview');
  if (fileInput && preview) {
    fileInput.addEventListener('change', function () {
      var file = fileInput.files && fileInput.files[0];
      if (!file) { preview.src = preview.dataset.placeholder || ''; return; }
      if (!file.type.match(/^image\/(jpeg|png|gif|webp)$/)) {
        alert('Only JPG, PNG, GIF or WebP images are allowed.');
        fileInput.value = '';
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        alert('Image must be 5 MB or smaller.');
        fileInput.value = '';
        return;
      }
      var reader = new FileReader();
      reader.onload = function (ev) { preview.src = ev.target.result; };
      reader.readAsDataURL(file);
    });
  }

  /* ------------------------------ dismiss flash */
  document.querySelectorAll('.flash').forEach(function (f) {
    setTimeout(function () {
      f.style.transition = 'opacity 0.5s';
      f.style.opacity = '0';
      setTimeout(function () { f.remove(); }, 520);
    }, 5200);
  });
})();
