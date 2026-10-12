/* Uma Lafatik — live order tracking (polls order status) */
(function () {
  'use strict';

  var orderId = (window.UMALF_TRACK && window.UMALF_TRACK.order_id) || null;
  if (!orderId) return;

  var STATUS_STEPS = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed'];
  var STEP_LABELS = {
    pending: 'Order received',
    confirmed: 'Order confirmed',
    preparing: 'Preparing your food',
    ready: 'Ready for collection',
    out_for_delivery: 'Out for delivery',
    completed: 'Delivered'
  };
  var STEP_DESC = {
    pending: 'The restaurant has received your order and is reviewing it.',
    confirmed: 'The chef has accepted your order.',
    preparing: 'Your dishes are being prepared with care.',
    ready: 'Your order is packed and ready.',
    out_for_delivery: 'Our courier is on the way to you.',
    completed: 'Enjoy your meal. Thank you for dining with us!'
  };

  var POLL_MS = 8000;
  var lastStatus = null;

  function render(status) {
    var badge = document.getElementById('trackStatus');
    if (badge) {
      badge.className = 'badge badge-' + status;
      badge.textContent = status.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    var timeline = document.getElementById('trackTimeline');
    if (timeline) {
      var currentIdx = STATUS_STEPS.indexOf(status);
      var html = '';
      STATUS_STEPS.forEach(function (step, i) {
        var state = i < currentIdx ? 'done' : (i === currentIdx ? 'current' : '');
        html +=
          '<li class="tl-step ' + state + '">' +
            '<span class="tl-dot">' +
              (i < currentIdx
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>'
                : '') +
            '</span>' +
            '<h4>' + STEP_LABELS[step] + '</h4>' +
            '<p>' + STEP_DESC[step] + '</p>' +
            (i === currentIdx ? '<span class="tl-time">In progress</span>' : '') +
          '</li>';
      });
      timeline.innerHTML = html;
    }

    if (lastStatus !== null && lastStatus !== status) {
      showToast('Status update: ' + STEP_LABELS[status]);
    }
    lastStatus = status;
  }

  function showToast(msg) {
    var t = document.createElement('div');
    t.className = 'flash flash-info';
    t.style.position = 'fixed';
    t.style.bottom = '1.4rem';
    t.style.right = '1.4rem';
    t.style.zIndex = '1600';
    t.style.maxWidth = '320px';
    t.style.boxShadow = '0 12px 30px rgba(36,30,26,0.2)';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () {
      t.style.transition = 'opacity 0.5s';
      t.style.opacity = '0';
      setTimeout(function () { t.remove(); }, 520);
    }, 4200);
  }

  function poll() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', UMALF_BASE_PATH + '/api/order-status.php?order_id=' + encodeURIComponent(orderId), true);
    xhr.onload = function () {
      if (xhr.status === 200) {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success) render(res.status);
        } catch (e) { /* ignore */ }
      }
    };
    xhr.send();
  }

  render(window.UMALF_TRACK.initial_status || 'pending');
  setInterval(poll, POLL_MS);
})();
