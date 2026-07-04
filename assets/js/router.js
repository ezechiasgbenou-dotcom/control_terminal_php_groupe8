/* ============================================
   LIEN — Routeur SPA (hash routing)
   Navigation sans rechargement entre les pages.
   Chaque page est un "partial" chargé dans #view.
   ============================================ */

const Router = (() => {

  const routes = {
    'accueil':       'partials/accueil.html',
    'profil':        'partials/profil.html',
    'amis':          'partials/amis.html',
    'chat':          'partials/chat.html',
    'enregistres':   'partials/enregistres.html',
    'parametres':    'partials/parametres.html',
    'profil-public': 'partials/profil-public.html',
  };

  function parseHash() {
    const raw = location.hash.replace(/^#\/?/, '') || 'accueil';
    const qIdx = raw.indexOf('?');
    const page   = qIdx >= 0 ? raw.slice(0, qIdx) : raw;
    const params = new URLSearchParams(qIdx >= 0 ? raw.slice(qIdx + 1) : '');
    return { page, params };
  }

  async function load(page, params) {
    if (typeof window.__pageCleanup === 'function') {
      window.__pageCleanup();
      window.__pageCleanup = null;
    }

    // Remet le layout par défaut avant chaque page
    if (typeof window.setLayout === 'function') window.setLayout('feed');

    document.querySelectorAll('[data-route-style]').forEach(s => s.remove());

    const partial = routes[page] || routes['accueil'];
    const view    = document.getElementById('view');

    view.innerHTML = `
      <div style="padding:24px; max-width:900px; margin:0 auto">
        <div class="skeleton" style="height:200px;border-radius:12px;margin-bottom:16px"></div>
        <div class="skeleton" style="height:120px;border-radius:12px;margin-bottom:16px"></div>
        <div class="skeleton" style="height:160px;border-radius:12px"></div>
      </div>`;

    try {
      const res = await fetch(partial + '?_=' + Date.now());
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const html = await res.text();

      const tpl = document.createElement('div');
      tpl.innerHTML = html;

      tpl.querySelectorAll('style').forEach(style => {
        const s = document.createElement('style');
        s.setAttribute('data-route-style', '1');
        s.textContent = style.textContent;
        document.head.appendChild(s);
        style.remove();
      });

      window.__routeParams = params;
      view.innerHTML = tpl.innerHTML;

      view.querySelectorAll('script').forEach(old => {
        old.remove();
        const s = document.createElement('script');
        s.textContent = old.textContent;
        document.body.appendChild(s);
      });

      document.querySelectorAll('[data-page]').forEach(el => {
        el.classList.toggle('active', el.dataset.page === page);
      });

      window.scrollTo(0, 0);

    } catch (err) {
      view.innerHTML = `
        <div style="padding:48px;text-align:center;color:var(--color-text-muted)">
          <i class="ph ph-warning-circle" style="font-size:48px;display:block;margin-bottom:12px;color:var(--color-danger)"></i>
          <p>Impossible de charger cette page.</p>
          <button class="btn btn-ghost" style="margin-top:16px" onclick="Router.navigate('accueil')">
            Retour à l'accueil
          </button>
        </div>`;
      console.error('[Router]', err);
    }
  }

  function navigate(page, params) {
    params = params || {};
    const qs = new URLSearchParams(params).toString();
    location.hash = page + (qs ? '?' + qs : '');
  }

  function init() {
    window.addEventListener('hashchange', function() {
      const r = parseHash();
      load(r.page, r.params);
    });
    const r = parseHash();
    load(r.page, r.params);
  }

  return { init: init, navigate: navigate };
})();
