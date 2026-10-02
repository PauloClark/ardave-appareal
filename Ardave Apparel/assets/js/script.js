function initPageNavigation() {
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');

  if (!navToggle || !navLinks) return;

  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
  });
}

function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', event => {
      event.preventDefault();
      const target = document.querySelector(anchor.getAttribute('href'));
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
}

function setActiveNav() {
  const currentSegment = window.location.pathname.split('/').filter(Boolean).pop()?.toLowerCase() || 'index.php';
  const navLinks = document.querySelectorAll('.navbar-premium .premium-nav-link, .navbar-premium .nav-link');

  navLinks.forEach(link => {
    const href = link.getAttribute('href') || '';
    if (!href) return;

    const hrefPath = link.pathname || href;
    const targetSegment = hrefPath.split('/').filter(Boolean).pop()?.toLowerCase() || 'index.php';
    const isActive = currentSegment === targetSegment || (currentSegment === '' && targetSegment === 'index.php');

    link.classList.toggle('active', isActive);
  });
}

function cleanupAuthBlockingLayers() {
  if (!document.body.classList.contains('auth-page')) return;

  document.querySelectorAll('.page-transition-overlay').forEach(el => el.remove());
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
  document.body.classList.remove('modal-open');
  document.querySelectorAll('.modal.show').forEach(modal => {
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  });
}

function createTransitionOverlay(message = 'Preparing your experience') {
  if (document.body.classList.contains('auth-page')) return null;

  const overlay = document.createElement('div');
  overlay.className = 'page-transition-overlay active';
  overlay.innerHTML = `
    <div class="transition-shell animate__animated animate__fadeIn">
      <div class="transition-logo">A</div>
      <div class="transition-spinner"></div>
      <div class="transition-copy">
        <span class="loading-dots">Loading</span>
        <small>${message}</small>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);
  return overlay;
}

function startPageTransition(targetUrl, message = 'Preparing your experience') {
  const overlay = createTransitionOverlay(message);
  if (!overlay) {
    window.location.assign(targetUrl);
    return null;
  }

  window.setTimeout(() => {
    window.location.assign(targetUrl);
  }, 720);

  return overlay;
}

function attachPageTransition() {
  // Don't attach transitions on auth pages
  if (document.body.classList.contains('auth-page')) return;
  
  document.querySelectorAll('a[href]').forEach(link => {
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;
    if (href.startsWith('http://') || href.startsWith('https://')) {
      const sameOrigin = new URL(href, window.location.href).origin === window.location.origin;
      if (!sameOrigin) return;
    }

    link.addEventListener('click', event => {
      const target = link.getAttribute('target');
      if (target === '_blank') return;
      if (link.classList.contains('dropdown-item') && link.closest('.dropdown-toggle')) return;
      if (link.closest('.dropdown-menu')) return;

      const isLogout = link.classList.contains('logout-trigger');
      if (isLogout) return;

      const isInternalPage = /\.php($|\?)/i.test(href) || href === '/' || href.endsWith('/');
      if (!isInternalPage) return;

      event.preventDefault();
      startPageTransition(href);
    });
  });
}

function attachRippleEffect() {
  document.querySelectorAll('.btn, button').forEach(element => {
    element.addEventListener('click', function (event) {
      if (element.closest('.dropdown-menu')) return;
      const rect = element.getBoundingClientRect();
      const ripple = document.createElement('span');
      ripple.className = 'ripple-effect';
      ripple.style.left = `${event.clientX - rect.left}px`;
      ripple.style.top = `${event.clientY - rect.top}px`;
      ripple.style.width = `${Math.max(rect.width, rect.height)}px`;
      ripple.style.height = `${Math.max(rect.width, rect.height)}px`;
      element.appendChild(ripple);
      window.setTimeout(() => ripple.remove(), 600);
    });
  });
}

function initLogoutFlow() {
  const logoutForm = document.getElementById('logoutForm');
  const modalElement = document.getElementById('logoutModal');
  const confirmButton = document.getElementById('confirmLogoutBtn');

  if (!logoutForm || !modalElement || !confirmButton) return;

  const modal = new bootstrap.Modal(modalElement);

  logoutForm.addEventListener('submit', event => {
    event.preventDefault();
    modal.show();
  });

  confirmButton.addEventListener('click', () => {
    modal.hide();
    const overlay = createTransitionOverlay('Logging out...');
    window.setTimeout(() => {
      logoutForm.submit();
    }, 2400);
  });
}

function initPageScripts() {
  cleanupAuthBlockingLayers();
  document.querySelectorAll('.page-transition-overlay').forEach(el => el.remove());

  initPageNavigation();
  initSmoothScroll();
  setActiveNav();
  attachPageTransition();
  attachRippleEffect();
  initLogoutFlow();
  syncCartBadge();
  document.body.classList.add('page-ready');
}

function syncCartBadge() {
  try {
    const cartKey = 'ardaveCart';
    const stored = localStorage.getItem(cartKey);
    const cart = stored ? JSON.parse(stored) : [];
    const totalCount = cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const countElement = document.querySelector('.cart-badge, .cart-count');
    if (countElement) {
      countElement.textContent = totalCount;
    }
  } catch (e) {
    // Silently fail if localStorage is not available
  }
}

window.addEventListener('storage', syncCartBadge);
window.addEventListener('cartUpdated', syncCartBadge);

window.domApp = {
  initPageNavigation,
  initSmoothScroll,
  initPageScripts,
};

if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', initPageScripts);
} else {
  initPageScripts();
}
