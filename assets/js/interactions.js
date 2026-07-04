/* ============================================
   LIEN — Interactions (likes, commentaires, sons)
   ============================================ */

/* --- Petit son de "like" généré (pas de fichier audio externe) --- */
function playLikeSound(){
  try{
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const now = ctx.currentTime;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(520, now);
    osc.frequency.exponentialRampToValueAtTime(880, now + 0.09);
    gain.gain.setValueAtTime(0.0001, now);
    gain.gain.exponentialRampToValueAtTime(0.18, now + 0.015);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.18);
    osc.connect(gain).connect(ctx.destination);
    osc.start(now);
    osc.stop(now + 0.2);
  }catch(e){ /* audio non disponible, on ignore silencieusement */ }
}

/* --- Petites particules qui jaillissent du bouton "like" --- */
function burstParticles(anchorEl){
  const burst = document.createElement('div');
  burst.className = 'like-burst';
  const rect = anchorEl.getBoundingClientRect();
  const parentRect = anchorEl.closest('.post').getBoundingClientRect();
  burst.style.left = (rect.left - parentRect.left + rect.width/2) + 'px';
  burst.style.top = (rect.top - parentRect.top + rect.height/2) + 'px';

  const icons = ['•','•','•','•','•','•'];
  icons.forEach((ch,i)=>{
    const span = document.createElement('span');
    span.textContent = ch;
    const angle = (Math.PI * 2 * i) / icons.length;
    const dist = 22 + Math.random()*10;
    span.style.setProperty('--dx', Math.cos(angle)*dist + 'px');
    span.style.setProperty('--dy', Math.sin(angle)*dist + 'px');
    span.style.animationDelay = (i*15)+'ms';
    burst.appendChild(span);
  });
  anchorEl.closest('.post').appendChild(burst);
  setTimeout(()=>burst.remove(), 700);
}

/* --- Bascule "j'aime" sur une publication --- */

async function toggleLike(button) {
  const post = button.closest('.post');
  const postId = post.dataset.postId;
  
  // 1. Bascule l'état actif du bouton
  const isLiked = button.classList.toggle('liked');
  
  // 2. Remplir ou vider l'icône de cœur instantanément
  const heartIcon = button.querySelector('i.ph-heart');
  if (heartIcon) {
    heartIcon.classList.toggle('ph-fill', isLiked);
  }

  // 3. Récupérer le compteur numérique caché (notre source de vérité)
  const hiddenCountEl = button.querySelector('.like-count') || post.querySelector('.like-count');
  
  if (hiddenCountEl) {
    // On lit le nombre actuel stocké dans le dataset
    let count = parseInt(hiddenCountEl.getAttribute('data-count'), 10) || 0;
    
    // On incrémente ou décrémente selon l'action
    count = isLiked ? count + 1 : Math.max(0, count - 1);
    
    // On sauvegarde la nouvelle valeur dans le dataset caché pour le prochain clic
    hiddenCountEl.setAttribute('data-count', count);

    // --- CAS 1 : PAGE D'ACCUEIL (Gestion de la phrase personnalisée) ---
    const likeSummary = post.querySelector('.like-summary');
    if (likeSummary) {
      if (count > 0) {
        if (isLiked) {
          likeSummary.textContent = (count - 1 === 0) 
            ? 'Vous' 
            : 'Vous et ' + (count - 1) + ' autre(s)';
        } else {
          likeSummary.textContent = count + ' réaction' + (count > 1 ? 's' : '');
        }
      } else {
        likeSummary.textContent = 'Aucune réaction';
      }
    }

    // --- CAS 2 : PAGE PROFIL (Gestion du label simple) ---
    const likeCountLabel = post.querySelector('.like-count-label');
    if (likeCountLabel) {
      likeCountLabel.textContent = count + ' réaction' + (count > 1 ? 's' : '');
    }
  }

  // 4. Déclencher les animations visuelles et sonores
  if (isLiked) {
    button.classList.add('like-pop');
    setTimeout(() => button.classList.remove('like-pop'), 420);
    if (typeof burstParticles === 'function') burstParticles(button);
    if (typeof playLikeSound === 'function') playLikeSound();
  }

  // 5. Envoi de la requête API en arrière-plan
  if (postId && typeof apiCall === 'function') {
    try {
      await apiCall('posts/like.php', { method: 'POST', body: { post_id: Number(postId) } });
    } catch (err) {
      showToast(err.message, 'ph-warning-circle');
    }
  }
}

/* --- Affiche ou masque la section des commentaires (Universel) --- */
window.toggleComments = function(button) {
  const post = button.closest('.post');
  if (!post) return;
  
  const commentsSection = post.querySelector('.comments');
  if (commentsSection) {
    // On vérifie le style réel (inline ou CSS via getComputedStyle)
    const isHidden = window.getComputedStyle(commentsSection).display === 'none';
    
    // Si c'est caché on affiche, si c'est affiché on cache
    commentsSection.style.display = isHidden ? 'block' : 'none';
  }
};

/* --- Ajout d'un commentaire sans recharger la page --- */
async function submitComment(form, event){
  event.preventDefault();
  const input = form.querySelector('input');
  const text = input.value.trim();
  if(!text) return;

  const post = form.closest('.post');
  const postId = post.dataset.postId;
  const list = form.closest('.comments').querySelector('.comment-list');
  const me = (typeof getStoredUser === 'function') ? getStoredUser() : null;

  // Ajout optimiste dans le DOM
  const item = document.createElement('div');
  item.className = 'comment';
  item.innerHTML = `
    <img class="avatar" src="${me ? avatarUrl(me.avatar) : 'assets/images/default-avatar.png'}" width="32" height="32" alt="">
    <div class="comment-bubble">
      <span class="name">${me ? me.first_name + ' ' + me.last_name : 'Vous'}</span>
      <span class="text"></span>
    </div>`;
  item.querySelector('.text').textContent = text;
  item.style.opacity = '0';
  list.appendChild(item);
  requestAnimationFrame(()=>{
    item.style.transition = 'opacity 220ms ease';
    item.style.opacity = '1';
  });
  input.value = '';

  // ← NOUVEAU : Met à jour le compteur immédiatement
  const countLabel = post.querySelector('.comment-count-label');
  if (countLabel) {
    const cur = parseInt(countLabel.textContent) || 0;
    const n = cur + 1;
    countLabel.textContent = n + ' commentaire' + (n > 1 ? 's' : '');
  }

  if (postId && typeof apiCall === 'function'){
    try{ await apiCall('posts/comment.php', { method:'POST', body:{ post_id: Number(postId), content: text } }); }
    catch(err){ showToast(err.message, 'ph-warning-circle'); }
  }
}

/* --- Toast de confirmation --- */
let toastTimer;
function showToast(message, icon='ph-check-circle'){
  let toast = document.querySelector('.toast');
  if(!toast){
    toast = document.createElement('div');
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.innerHTML = `<i class="ph ${icon}"></i><span></span>`;
  toast.querySelector('span').textContent = message;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=>toast.classList.remove('show'), 2600);
}

/* --- Auto-grow du composeur de publication --- */
document.addEventListener('input', (e)=>{
  if(e.target.matches('.composer textarea, .comment-form input')){
    e.target.style.height = 'auto';
    if(e.target.tagName === 'TEXTAREA'){
      e.target.style.height = e.target.scrollHeight + 'px';
    }
  }
});
