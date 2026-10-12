/* Uma Lafatik — main site behaviour */
(function () {
  'use strict';

  var header = document.getElementById('siteHeader');
  var toTop = document.getElementById('toTop');

  function onScroll() {
    if (header) header.classList.toggle('scrolled', window.scrollY > 40);
    if (toTop) toTop.classList.toggle('show', window.scrollY > 600);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  if (toTop) {
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* Mobile navigation */
  var navToggle = document.getElementById('navToggle');
  var nav = document.getElementById('navLinks');
  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      navToggle.classList.toggle('open', open);
      navToggle.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
    });
    nav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        nav.classList.remove('open');
        navToggle.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }

  /* Dashboard sidebar toggle (customer + admin) */
  var dashToggle = document.getElementById('dashToggle');
  var dashSide = document.getElementById('dashSide');
  if (dashToggle && dashSide) {
    dashToggle.addEventListener('click', function () {
      var open = dashSide.classList.toggle('open');
      dashToggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', function (e) {
      if (dashSide.classList.contains('open') &&
          !dashSide.contains(e.target) && !dashToggle.contains(e.target)) {
        dashSide.classList.remove('open');
        dashToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* Scroll reveal */
  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  }

  /* Gallery lightbox */
  var lightbox = null;
  var lbImg = null;
  var lbCaption = null;
  document.querySelectorAll('.gallery-item').forEach(function (item) {
    item.addEventListener('click', function () {
      if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.setAttribute('role', 'dialog');
        lightbox.setAttribute('aria-modal', 'true');
        lightbox.setAttribute('aria-label', 'Image viewer');
        lightbox.innerHTML =
          '<button class="lightbox-close" aria-label="Close">&times;</button>' +
          '<p class="lightbox-caption"></p>';
        document.body.appendChild(lightbox);
        lbImg = null;
        lbCaption = lightbox.querySelector('.lightbox-caption');
        lightbox.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', function (e) {
          if (e.target === lightbox) closeLightbox();
        });
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') closeLightbox();
        });
      }
      var src = item.querySelector('img, svg');
      var clone = src ? src.cloneNode(true) : null;
      if (clone) {
        if (lbImg) { lbImg.remove(); }
        clone.style.maxWidth = 'min(1000px, 92vw)';
        clone.style.maxHeight = '84vh';
        clone.style.borderRadius = '14px';
        lightbox.insertBefore(clone, lightbox.firstChild);
        lbImg = clone;
      }
      lbCaption.textContent = item.getAttribute('data-caption') || '';
      lightbox.classList.add('open');
      document.body.style.overflow = 'hidden';
    });
  });
  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
  }

  /* Flash messages auto-dismiss */
  document.querySelectorAll('.flash').forEach(function (f) {
    setTimeout(function () {
      f.style.transition = 'opacity 0.5s';
      f.style.opacity = '0';
      setTimeout(function () { f.remove(); }, 520);
    }, 5200);
  });

  /* Smooth anchor offset for fixed header */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      if (id.length > 1) {
        var target = document.querySelector(id);
        if (target) {
          e.preventDefault();
          var y = target.getBoundingClientRect().top + window.scrollY - 80;
          window.scrollTo({ top: y, behavior: 'smooth' });
        }
      }
    });
  });
})();
