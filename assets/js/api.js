/* ============================================
   LIEN — Client API (sessionStorage + fetch)
   ============================================ */

const API_BASE = (function() {
  var path = window.location.pathname;
  // Si on est dans /vues/, on remonte au-dessus
  if (path.indexOf('/vues/') !== -1) {
    return path.split('/vues/')[0] + '/api';
  }
  // Sinon (app.html, index.html à la racine)
  return path.replace(/\/[^/]*$/, '') + '/api';
})();

function getToken()      { return sessionStorage.getItem('lien_token'); }
function getStoredUser() {
  var raw = sessionStorage.getItem('lien_user');
  return raw ? JSON.parse(raw) : null;
}
function setSession(token, user) {
  sessionStorage.setItem('lien_token', token);
  sessionStorage.setItem('lien_user', JSON.stringify(user));
}
function clearSession() {
  sessionStorage.removeItem('lien_token');
  sessionStorage.removeItem('lien_user');
}

async function apiCall(endpoint, options) {
  options = options || {};
  var method = options.method || 'GET';
  var body   = options.body   || null;

  var headers = {};
  var token = getToken();
  if (token) headers['Authorization'] = 'Bearer ' + token;

  var payload = body;
  if (body && !(body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  var res = await fetch(API_BASE + '/' + endpoint, {
    method: method,
    headers: headers,
    body: payload
  });

  var data;
  try { data = await res.json(); }
  catch(e) { data = { success: false, message: 'Réponse invalide du serveur.' }; }

  if (res.status === 401) {
    clearSession();
    // Depuis app.html : retour à index.html
    // Depuis back-office : retour à connexion.html
    if (window.location.pathname.indexOf('/vues/back-office/') !== -1) {
      window.location.href = 'connexion.html';
    } else {
      window.location.href = guessRootUrl() + 'index.html';
    }
  }

  if (!data.success) throw new Error(data.message || 'Une erreur est survenue.');
  return data;
}

function guessRootUrl() {
  var path = window.location.pathname;
  if (path.indexOf('/vues/back-office/') !== -1) return '../../';
  return ''; // app.html et index.html sont à la racine
}

function requireClientAuth() {
  if (!getToken() || !getStoredUser()) {
    window.location.href = guessRootUrl() + 'index.html';
  }
}

function requireStaffAuth() {
  var user = getStoredUser();
  if (!getToken() || !user || ['admin','moderator'].indexOf(user.role) === -1) {
    window.location.href = 'connexion.html';
  }
  return user;
}

async function logout() {
  try { await apiCall('auth/logout.php', { method: 'POST' }); } catch(e) {}
  clearSession();
  window.location.href = guessRootUrl() + 'index.html';
}

function timeAgo(dateStr) {
  var diff = (Date.now() - new Date(dateStr.replace(' ','T'))) / 1000;
  if (diff < 60)     return "À l'instant";
  if (diff < 3600)   return 'Il y a ' + Math.floor(diff/60) + ' min';
  if (diff < 86400)  return 'Il y a ' + Math.floor(diff/3600) + ' h';
  if (diff < 604800) return 'Il y a ' + Math.floor(diff/86400) + ' j';
  return new Date(dateStr.replace(' ','T')).toLocaleDateString('fr-FR');
}

function avatarUrl(path) {
  if (!path) return guessRootUrl() + 'assets/images/default-avatar.png';
  if (path.startsWith('http')) return path;
  return guessRootUrl() + path;
}
