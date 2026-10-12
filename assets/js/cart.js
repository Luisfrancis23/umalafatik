/* Uma Lafatik — cart interactions (add / update / remove via AJAX) */
(function () {
  'use strict';

  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var LOGGED_IN = (window.UMALF && window.UMALF.logged_in) || false;

  function api(action, payload, onDone) {
    var data = new FormData();
    data.append('action', action);
    data.append('csrf_token', CSRF);
    for (var k in payload) { if (payload.hasOwnProperty(k)) data.append(k, payload[k]); }

    fetch(UMALF_BASE_PATH + '/api/cart.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: data
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) {
          if (res.login) {
            window.location.href = UMALF_BASE_PATH + '/customer/login.php?next=' + encodeURIComponent(window.location.pathname);
            return;
          }
          alert(res.message || 'Something went wrong. Please try again.');
          return;
        }
        onDone(res);
      })
      .catch(function () { alert('Network error. Please try again.'); });
  }

  function syncCartBadge(count) {
    var badges = document.querySelectorAll('.cart-count');
    badges.forEach(function (b) {
      if (count > 0) {
        b.textContent = count;
        b.setAttribute('aria-label', count + ' items in cart');
        b.style.display = '';
      } else {
        b.style.display = 'none';
      }
    });
  }

  /* ---- Add to cart buttons (menu cards) ---- */
  document.querySelectorAll('[data-add-id]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-add-id');
      if (!LOGGED_IN) {
        window.location.href = UMALF_BASE_PATH + '/customer/login.php?next=' + encodeURIComponent(window.location.pathname);
        return;
      }
      btn.disabled = true;
      api('add', { menu_item_id: id }, function (res) {
        syncCartBadge(res.count);
        btn.classList.add('added');
        var label = btn.querySelector('span');
        var original = btn.dataset.label || (label ? label.textContent : 'Add to Cart');
        btn.dataset.label = original;
        if (label) label.textContent = 'Added ✓';
        setTimeout(function () {
          btn.classList.remove('added');
          if (label) label.textContent = original;
          btn.disabled = false;
        }, 1400);
      });
    });
  });

  /* ---- Cart page: quantity steppers + remove ---- */
  function bindCartPage() {
    if (!document.body.classList.contains('cart-page')) return;

    document.querySelectorAll('[data-qty-inc]').forEach(function (btn) {
      btn.addEventListener('click', function () { changeQty(btn, +1); });
    });
    document.querySelectorAll('[data-qty-dec]').forEach(function (btn) {
      btn.addEventListener('click', function () { changeQty(btn, -1); });
    });
    document.querySelectorAll('[data-remove-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-remove-id');
        api('remove', { menu_item_id: id }, function () { refreshCart(); });
      });
    });
  }

  function changeQty(btn, delta) {
    var line = btn.closest('.cart-line');
    var id = line.getAttribute('data-item-id');
    var span = line.querySelector('.qty-stepper span');
    var qty = (parseInt(span.textContent, 10) || 1) + delta;
    if (qty < 0) qty = 0;
    api('update', { menu_item_id: id, quantity: qty }, function () { refreshCart(); });
  }

  function refreshCart() {
    api('summary', {}, function (res) {
      syncCartBadge(res.count);
      window.location.reload();
    });
  }

  bindCartPage();

  /* ---- Checkout form live totals ----
     (server renders totals; this only toggles delivery fee note) */
  var deliveryTypeInputs = document.querySelectorAll('input[name="delivery_type"]');
  deliveryTypeInputs.forEach(function (input) {
    input.addEventListener('change', function () {
      var addrFields = document.getElementById('deliveryFields');
      if (addrFields) {
        addrFields.style.display = document.querySelector('input[name="delivery_type"]:checked').value === 'delivery' ? '' : 'none';
      }
    });
  });
})();
