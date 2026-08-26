/* ARTISAN Chartered Accountants — site interactions */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;

  /* ---------- Mobile navigation ---------- */
  var navToggle = doc.querySelector('[data-nav-toggle]');
  var navClose = doc.querySelector('[data-nav-close]');
  var backdrop = doc.querySelector('[data-backdrop]');

  function setNav(open) {
    doc.body.classList.toggle('nav-open', open);
    doc.body.style.overflow = open ? 'hidden' : '';
    if (navToggle) navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  if (navToggle) navToggle.addEventListener('click', function () {
    setNav(!doc.body.classList.contains('nav-open'));
  });
  if (navClose) navClose.addEventListener('click', function () { setNav(false); });
  if (backdrop) backdrop.addEventListener('click', function () { setNav(false); });
  Array.prototype.forEach.call(doc.querySelectorAll('.mobile-panel a'), function (a) {
    a.addEventListener('click', function () { setNav(false); });
  });

  /* ---------- Sticky header shadow + back to top ---------- */
  var header = doc.querySelector('[data-header]');
  var toTop = doc.querySelector('[data-to-top]');
  function onScroll() {
    var y = window.pageYOffset || root.scrollTop;
    if (header) header.classList.toggle('is-stuck', y > 8);
    if (toTop) toTop.classList.toggle('is-visible', y > 600);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  if (toTop) toTop.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* ---------- Hero slider ---------- */
  var hero = doc.querySelector('[data-hero]');
  if (hero) {
    var slides = hero.querySelectorAll('[data-hero-slide]');
    var dots = hero.querySelectorAll('[data-hero-dot]');
    var titleEl = hero.querySelector('[data-hero-title]');
    var copyEl = hero.querySelector('[data-hero-copy]');
    var numEl = hero.querySelector('[data-hero-num]');
    var bar = hero.querySelector('[data-hero-bar]');
    var content = [];
    try { content = JSON.parse(hero.getAttribute('data-hero') || '[]'); } catch (e) { content = []; }
    var total = content.length || slides.length || dots.length || 1;
    var current = 0, timer = null;

    function show(i) {
      current = (i + total) % total;
      Array.prototype.forEach.call(slides, function (s, n) {
        s.classList.toggle('is-active', n === current);
      });
      Array.prototype.forEach.call(dots, function (d, n) {
        d.classList.toggle('is-active', n === current);
        d.setAttribute('aria-selected', n === current ? 'true' : 'false');
      });
      var item = content[current];
      if (item) {
        if (titleEl) titleEl.innerHTML = item.title;
        if (copyEl) copyEl.textContent = item.copy;
      }
      if (titleEl) {
        titleEl.classList.remove('hero-text-in');
        void titleEl.offsetWidth;
        titleEl.classList.add('hero-text-in');
      }
      if (copyEl) {
        copyEl.classList.remove('hero-copy-in');
        void copyEl.offsetWidth;
        copyEl.classList.add('hero-copy-in');
      }
      if (numEl) numEl.textContent = ('0' + (current + 1)).slice(-2);
      if (bar) { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = ''; }
    }
    function play() {
      stop();
      timer = window.setInterval(function () { show(current + 1); }, 6000);
    }
    function stop() { if (timer) window.clearInterval(timer); timer = null; }

    Array.prototype.forEach.call(dots, function (d, n) {
      d.addEventListener('click', function () { show(n); play(); });
    });
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', play);
    doc.addEventListener('visibilitychange', function () {
      if (doc.hidden) { stop(); } else { play(); }
    });
    show(0);
    if (total > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) play();
  }

  /* ---------- Accordions ---------- */
  Array.prototype.forEach.call(doc.querySelectorAll('[data-accordion]'), function (acc) {
    var items = acc.querySelectorAll('.acc-item');
    Array.prototype.forEach.call(items, function (item) {
      var trigger = item.querySelector('.acc-trigger');
      if (!trigger) return;
      trigger.addEventListener('click', function () {
        var open = item.classList.contains('is-open');
        if (acc.hasAttribute('data-accordion-single')) {
          Array.prototype.forEach.call(items, function (other) {
            other.classList.remove('is-open');
            var t = other.querySelector('.acc-trigger');
            if (t) t.setAttribute('aria-expanded', 'false');
          });
        }
        item.classList.toggle('is-open', !open);
        trigger.setAttribute('aria-expanded', !open ? 'true' : 'false');
        if (!open) {
          window.setTimeout(function () {
            var top = item.getBoundingClientRect().top + window.pageYOffset - 120;
            if (item.getBoundingClientRect().top < 90) window.scrollTo({ top: top, behavior: 'smooth' });
          }, 60);
        }
      });
    });
  });

  /* Open the accordion item referenced by the URL hash */
  if (window.location.hash) {
    var target = doc.querySelector(window.location.hash);
    if (target && target.classList.contains('acc-item')) {
      target.classList.add('is-open');
    }
  }

  /* ---------- Modals (quotation, subscribe, search) ----------
     [data-modal-open] with no value opens the first [data-modal]; with a value
     it opens [data-modal="<value>"]. */
  var lastFocus = null;
  var activeModal = null;
  function openModal(el) {
    if (!el) return;
    lastFocus = doc.activeElement;
    activeModal = el;
    el.classList.add('is-open');
    doc.body.style.overflow = 'hidden';
    var first = el.querySelector('input, select, textarea, button');
    if (first) first.focus();
  }
  function closeModal() {
    if (!activeModal) return;
    activeModal.classList.remove('is-open');
    activeModal = null;
    doc.body.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  }
  Array.prototype.forEach.call(doc.querySelectorAll('[data-modal-open]'), function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault(); setNav(false);
      var t = btn.getAttribute('data-modal-open');
      var m = t ? doc.querySelector('[data-modal="' + t + '"]') : doc.querySelector('[data-modal]');
      openModal(m);
    });
  });
  Array.prototype.forEach.call(doc.querySelectorAll('[data-modal-close]'), function (btn) {
    btn.addEventListener('click', closeModal);
  });
  doc.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeModal(); setNav(false); }
  });

  /* Keep the hero exactly one viewport tall, minus the (non-sticky) header,
     so the counters sit at the bottom edge with no white section peeking in. */
  var siteHeader = doc.querySelector('.site-header');
  function syncHeaderHeight() {
    if (siteHeader) {
      doc.documentElement.style.setProperty('--header-h', siteHeader.offsetHeight + 'px');
    }
  }
  syncHeaderHeight();
  window.addEventListener('resize', syncHeaderHeight);
  window.addEventListener('load', syncHeaderHeight);

  /* Site search — static site has no server search, so hand the query to a
     Google site: search in a new tab. */
  Array.prototype.forEach.call(doc.querySelectorAll('[data-search-form]'), function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = form.querySelector('input[name="q"]');
      var q = input ? input.value.trim() : '';
      if (!q) { if (input) input.focus(); return; }
      window.open('https://www.google.com/search?q=' +
        encodeURIComponent('site:artisan-ca.net ' + q), '_blank', 'noopener');
      closeModal();
    });
  });

  /* ---------- Forms ----------
     Static site: every form is composed into an email to the firm.
     Replace `handleSubmit` with a POST to your endpoint (PHP, Formspree,
     Netlify Forms, …) when a server-side handler is available.            */
  var MAIL_TO = 'info@artisancabd.com';

  function handleSubmit(form) {
    var honey = form.querySelector('.hp-field input');
    if (honey && honey.value) return; /* bot */

    var status = form.querySelector('[data-form-status]') ||
      (form.parentNode && form.parentNode.querySelector('[data-form-status]'));

    /* Forms that must never be emailed (credentials, uploads) need a real
       server-side handler — see README.md. */
    if (form.hasAttribute('data-no-mail')) {
      if (status) {
        status.textContent = form.getAttribute('data-pending') ||
          'This form needs a server-side handler before it can be submitted. Please contact the firm at ' + MAIL_TO + '.';
        status.classList.add('is-visible');
      }
      return;
    }

    var lines = [];
    Array.prototype.forEach.call(form.elements, function (el) {
      /* never put credentials or file paths into an email */
      if (!el.name || el.type === 'submit' || el.type === 'password' ||
          el.type === 'file' || el.closest('.hp-field')) return;
      var label = form.querySelector('label[for="' + el.id + '"]');
      var name = label ? label.textContent.replace('*', '').trim() : el.name;
      var value = el.type === 'checkbox' ? (el.checked ? 'Yes' : 'No') : el.value.trim();
      if (value) lines.push(name + ': ' + value);
    });

    var subject = form.getAttribute('data-subject') || 'Website enquiry';
    var href = 'mailto:' + MAIL_TO +
      '?subject=' + encodeURIComponent(subject) +
      '&body=' + encodeURIComponent(lines.join('\n'));
    window.location.href = href;

    if (status) {
      status.textContent = form.getAttribute('data-success') ||
        'Thank you — your details have been prepared in an email to ' + MAIL_TO + '. Please press send in your mail application to complete the request.';
      status.classList.add('is-visible');
    }
    form.reset();
  }

  Array.prototype.forEach.call(doc.querySelectorAll('[data-form]'), function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!form.checkValidity()) { form.reportValidity(); return; }
      handleSubmit(form);
    });
  });

  var reduceMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Gallery pagination ---------- */
  Array.prototype.forEach.call(doc.querySelectorAll('[data-gallery-pager]'), function (pager) {
    var items = Array.prototype.slice.call(pager.querySelectorAll('[data-gallery-items] > .gallery-item'));
    var pagesWrap = pager.querySelector('[data-gallery-pages]');
    var prev = pager.querySelector('[data-gallery-prev]');
    var next = pager.querySelector('[data-gallery-next]');
    var perPage = 6;
    var totalPages = Math.max(1, Math.ceil(items.length / perPage));
    var currentPage = 0;
    var buttons = [];

    if (!items.length || !pagesWrap) return;

    function renderPage(page) {
      currentPage = Math.max(0, Math.min(page, totalPages - 1));
      items.forEach(function (item, index) {
        var visible = index >= currentPage * perPage && index < (currentPage + 1) * perPage;
        item.hidden = !visible;
      });
      buttons.forEach(function (button, index) {
        button.classList.toggle('is-active', index === currentPage);
        button.setAttribute('aria-current', index === currentPage ? 'page' : 'false');
      });
      if (prev) prev.disabled = currentPage === 0;
      if (next) next.disabled = currentPage === totalPages - 1;
    }

    for (var i = 0; i < totalPages; i += 1) {
      var button = doc.createElement('button');
      button.type = 'button';
      button.className = 'gallery-page-number';
      button.textContent = String(i + 1);
      button.setAttribute('aria-label', 'Gallery page ' + (i + 1));
      button.addEventListener('click', (function (page) {
        return function () { renderPage(page); };
      })(i));
      pagesWrap.appendChild(button);
      buttons.push(button);
    }
    if (prev) prev.addEventListener('click', function () { renderPage(currentPage - 1); });
    if (next) next.addEventListener('click', function () { renderPage(currentPage + 1); });
    renderPage(0);
  });

  /* ---------- Count-up numbers ----------
     Markup always ships the final value, so the correct number shows even if
     this never runs. We only zero it out once we know we're going to animate. */
  function padTo(txt, n) { while (txt.length < n) { txt = '0' + txt; } return txt; }

  function countTarget(el) { return parseInt(el.getAttribute('data-count'), 10) || 0; }
  function countPad(el) { return parseInt(el.getAttribute('data-pad') || '0', 10); }

  /* Write the true value straight away, no animation frames involved. */
  function finalizeCount(el) {
    el.textContent = padTo(String(countTarget(el)), countPad(el));
    el.setAttribute('data-counted', '1');
    el.setAttribute('data-done', '1');
  }

  function runCount(el) {
    if (el.getAttribute('data-counted')) return;
    el.setAttribute('data-counted', '1');
    var target = countTarget(el);
    var pad = countPad(el);
    var dur = 1500;
    var startTs = null;
    function frame(ts) {
      if (startTs === null) startTs = ts;
      var p = Math.min((ts - startTs) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3); /* easeOutCubic */
      el.textContent = padTo(String(Math.round(target * eased)), pad);
      if (p < 1) window.requestAnimationFrame(frame);
      else finalizeCount(el);
    }
    window.requestAnimationFrame(frame);
  }

  var counters = doc.querySelectorAll('[data-count]');
  /* Only zero them out when we can actually animate. requestAnimationFrame is
     frozen in a background tab, so zeroing there would strand the page showing
     "0 years of practice" — wrong data is worse than no animation. */
  if (counters.length && !reduceMotion && !doc.hidden) {
    Array.prototype.forEach.call(counters, function (el) {
      el.textContent = padTo('0', countPad(el));
    });
    if ('IntersectionObserver' in window) {
      var cio = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting) return;
          runCount(e.target);
          cio.unobserve(e.target);
        });
      }, { threshold: 0.35 });
      Array.prototype.forEach.call(counters, function (el) { cio.observe(el); });
    } else {
      Array.prototype.forEach.call(counters, runCount);
    }
    /* Hard backstop: whatever happened above, show the real numbers. */
    window.setTimeout(function () {
      Array.prototype.forEach.call(counters, function (el) {
        if (!el.getAttribute('data-done')) finalizeCount(el);
      });
    }, 4000);

    /* requestAnimationFrame is frozen while a tab is in the background, so a
       counter can be left stranded mid-count. Settle it the moment the tab is
       looked at again rather than waiting on a throttled timer. */
    doc.addEventListener('visibilitychange', function () {
      if (doc.hidden) return;
      Array.prototype.forEach.call(counters, function (el) {
        if (el.getAttribute('data-counted') && !el.getAttribute('data-done')) {
          finalizeCount(el);
        }
      });
    });
  }

  /* ---------- Auto scroll-reveal ----------
     Most pages were only partly tagged by hand. Tag the remaining content
     blocks here, but ONLY those already below the fold — hiding something the
     user can already see would flash. Runs before the observer is created. */
  if (!reduceMotion) {
    var main = doc.getElementById('main');
    if (main) {
      var AUTO = [
        '.section > .container > .head',
        '.split > .tile', '.split > .split-4', '.split > .split-5',
        '.split > .split-6', '.split > .split-7', '.split > .split-8',
        '.photo-grid > *', '.post-grid > *', '.team-grid > *', '.contact-grid > *',
        '.sitemap-grid > *', '.client-grid > *', '.bento > *',
        '.accordion > .acc-item', '.rows > .row-item', '.experience-pillar',
        '.stats > .stat', '.logo-panel', '.article-figure', '.callout', '.toc',
        '.auth-card', '.pull', '.masthead-note'
      ].join(',');
      var fold = window.innerHeight * 0.92;
      Array.prototype.forEach.call(main.querySelectorAll(AUTO), function (el) {
        if (el.classList.contains('reveal')) return;
        if (el.closest && el.closest('.hero')) return; /* hero animates itself */
        if (el.getBoundingClientRect().top < fold) return; /* already on screen */
        el.classList.add('reveal');
        if (!el.getAttribute('data-delay') && el.parentNode) {
          var i = Array.prototype.indexOf.call(el.parentNode.children, el);
          if (i > 0) el.setAttribute('data-delay', String(Math.min(i, 5) * 70));
        }
      });
    }
  }

  /* ---------- Reveal on scroll ---------- */
  var revealables = doc.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealables.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        var delay = parseInt(el.getAttribute('data-delay') || '0', 10);
        window.setTimeout(function () { el.classList.add('is-in'); }, delay);
        io.unobserve(el);
      });
    }, { rootMargin: '0px 0px -40px 0px', threshold: 0 });
    Array.prototype.forEach.call(revealables, function (el) { io.observe(el); });
    /* failsafe: never leave content invisible if the observer misbehaves */
    window.setTimeout(function () {
      Array.prototype.forEach.call(revealables, function (el) { el.classList.add('is-in'); });
    }, 2500);
  } else {
    Array.prototype.forEach.call(revealables, function (el) { el.classList.add('is-in'); });
  }

  /* ---------- Marquee: duplicate logos for a seamless loop ---------- */
  Array.prototype.forEach.call(doc.querySelectorAll('[data-marquee]'), function (track) {
    track.innerHTML += track.innerHTML;
  });

  /* ---------- Resource search (client-side filter) ---------- */
  var search = doc.querySelector('[data-post-search]');
  if (search) {
    var cards = doc.querySelectorAll('[data-post-card]');
    var empty = doc.querySelector('[data-post-empty]');
    search.addEventListener('input', function () {
      var q = search.value.toLowerCase().trim();
      var hits = 0;
      Array.prototype.forEach.call(cards, function (card) {
        var match = card.textContent.toLowerCase().indexOf(q) > -1;
        card.style.display = match ? '' : 'none';
        if (match) hits++;
      });
      if (empty) empty.style.display = hits ? 'none' : '';
    });
  }



  /* ---------- Count-up figures (accounting KPIs) ---------- */
  function countUp(el) {
    var target = parseFloat(el.getAttribute('data-count'));
    if (isNaN(target)) return;
    var pad = parseInt(el.getAttribute('data-pad') || '0', 10);
    var suffix = el.getAttribute('data-suffix') || '';
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      el.textContent = String(target) + suffix;
      return;
    }
    var duration = 1400, start = null;
    function frame(now) {
      if (start === null) start = now;
      var p = Math.min((now - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      var value = Math.round(target * eased);
      var text = String(value);
      while (text.length < pad) text = '0' + text;
      el.textContent = text + suffix;
      if (p < 1) window.requestAnimationFrame(frame);
    }
    window.requestAnimationFrame(frame);
  }

  var counters = doc.querySelectorAll('[data-count]');
  if (counters.length) {
    if ('IntersectionObserver' in window) {
      var co = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          countUp(entry.target);
          co.unobserve(entry.target);
        });
      }, { threshold: 0.4 });
      Array.prototype.forEach.call(counters, function (el) { co.observe(el); });
    } else {
      Array.prototype.forEach.call(counters, countUp);
    }
  }

  /* ---------- Sector rail controls ---------- */
  var rail = doc.querySelector('[data-rail]');
  if (rail) {
    var step = function () {
      var card = rail.querySelector('.rail-card');
      return card ? card.offsetWidth + 14 : 320;
    };
    var prev = doc.querySelector('[data-rail-prev]');
    var next = doc.querySelector('[data-rail-next]');
    var railTimer = null;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function stopRail() {
      if (railTimer) window.clearInterval(railTimer);
      railTimer = null;
    }
    function animateRailCard(index) {
      var cards = rail.querySelectorAll('.rail-card');
      var card = cards[index];
      if (!card) return;
      Array.prototype.forEach.call(cards, function (item) { item.classList.remove('is-arriving'); });
      window.setTimeout(function () {
        card.classList.add('is-arriving');
        window.setTimeout(function () { card.classList.remove('is-arriving'); }, 750);
      }, 320);
    }
    function advanceRail() {
      var atEnd = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 8;
      var current = Math.round(rail.scrollLeft / step());
      var target = atEnd ? 0 : current + 1;
      rail.scrollTo({ left: target * step(), behavior: 'smooth' });
      animateRailCard(target);
    }
    function playRail() {
      stopRail();
      if (!reduceMotion && !doc.hidden) railTimer = window.setInterval(advanceRail, 4200);
    }
    function restartRail() { playRail(); }

    if (prev) prev.addEventListener('click', function () {
      var target = Math.max(0, Math.round(rail.scrollLeft / step()) - 1);
      rail.scrollTo({ left: target * step(), behavior: 'smooth' });
      animateRailCard(target);
      restartRail();
    });
    if (next) next.addEventListener('click', function () {
      var max = rail.querySelectorAll('.rail-card').length - 1;
      var target = Math.min(max, Math.round(rail.scrollLeft / step()) + 1);
      rail.scrollTo({ left: target * step(), behavior: 'smooth' });
      animateRailCard(target);
      restartRail();
    });
    rail.addEventListener('mouseenter', stopRail);
    rail.addEventListener('mouseleave', playRail);
    rail.addEventListener('focusin', stopRail);
    rail.addEventListener('focusout', playRail);
    rail.addEventListener('touchstart', stopRail, { passive: true });
    rail.addEventListener('touchend', playRail, { passive: true });
    doc.addEventListener('visibilitychange', function () {
      if (doc.hidden) stopRail(); else playRail();
    });
    playRail();
  }

  /* ---------- Current year ---------- */
  Array.prototype.forEach.call(doc.querySelectorAll('[data-year]'), function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
