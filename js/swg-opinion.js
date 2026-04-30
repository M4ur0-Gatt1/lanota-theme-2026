(function(){
  function qs(id){return document.getElementById(id);} 
  function show(el){ if(el){ el.style.display='block'; } }
  function hide(el){ if(el){ el.style.display='none'; } }

  function initForm(){
    const form = qs('opinion-submit-form');
    const message = qs('opinion-message');
    if(!form) return;
    form.addEventListener('submit', function(e){
      e.preventDefault();
      const title = (qs('opinion-title')?.value||'').trim();
      const content = (qs('opinion-content')?.value||'').trim();
      if(!content){
        if(message) message.textContent = 'El contenido no puede estar vacío';
        return;
      }
      if(content.length > 400){
        if(message) message.textContent = 'Máximo 400 caracteres';
        return;
      }
      const fd = new FormData();
      fd.append('action','create_debate');
      fd.append('nonce', lanota_2026_opinion.nonce);
      // Debate endpoint expects only content (and optional link); title is ignored
      fd.append('content', content);
      fetch(lanota_2026_opinion.ajax_url, { method:'POST', body: fd })
        .then(r=>r.json())
        .then(json=>{
          if(json.success){
            if(message) message.textContent = lanota_2026_opinion.successMessage;
            form.reset();
          } else {
            if(message) message.textContent = (json.data && json.data.message) ? json.data.message : 'Error al enviar';
          }
        })
        .catch(()=>{ if(message) message.textContent='Error de red'; });
    });
  }

  function enableForm(){
    hide(qs('opinion-paywall'));
    show(qs('opinion-form'));
    initForm();
  }

  function attachSubscribeHandler(subscriptions){
    const btn = qs('swg-subscribe-btn');
    if(!btn) return;
    btn.addEventListener('click', function(){
      try {
        subscriptions.showOffers({
          publicationId: lanota_2026_opinion.publicationId
        });
      } catch (e) {
        console.error('SWG showOffers error', e);
      }
    });
  }

  function checkEntitlements(subscriptions){
    try {
      subscriptions.getEntitlements().then(function(entitlements){
        if (entitlements && (entitlements.enablesThis() || entitlements.entitlements.length)){
          enableForm();
        } else {
          // keep paywall visible and allow subscribe
          attachSubscribeHandler(subscriptions);
        }
      }).catch(function(){
        attachSubscribeHandler(subscriptions);
      });
    } catch(e){
      attachSubscribeHandler(subscriptions);
    }
  }

  function bootSWG(){
    // SWG queues callbacks on window.SWG
    var q = self.SWG = self.SWG || [];
    q.push(function(subscriptions){
      // Initialize with publicationId
      try { subscriptions.init({publicationId: lanota_2026_opinion.publicationId}); } catch(e) {}
      checkEntitlements(subscriptions);
      // Also listen to login/subscribe events to re-check
      try {
        subscriptions.setOnLoginRequest(function(){ subscriptions.login({linkRequested: true}); });
        subscriptions.setOnEntitlementsResponse(function(){ checkEntitlements(subscriptions); });
      } catch(e){}
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    // Ensure elements exist before SWG flow
    if(qs('opinion-form') || qs('opinion-paywall')){
      bootSWG();
    }
  });
})();
