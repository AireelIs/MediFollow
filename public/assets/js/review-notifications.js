/* Active referrals remain indicated until completed, even after reading them. */
'use strict';
(()=>{
 const link=document.querySelector('[data-review-nav]');
 if(!link || !['patient','doctor'].includes(link.dataset.reviewRole))return;
 const badge=link.querySelector('[data-review-badge]');let timer,busy=false,stopped=false;
 function schedule(){clearTimeout(timer);if(!document.hidden && !stopped)timer=setTimeout(refresh,15000);}
 async function refresh(){
  if(busy || document.hidden || stopped)return;busy=true;
  try{
   const response=await fetch('review-notifications.php',{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
   if(response.redirected){stopped=true;return;}
   if(!response.ok)return;
   const data=await response.json();if(!Number.isInteger(data.active) || data.active<0)return;
   badge.textContent=String(data.active);badge.hidden=data.active===0;
   link.setAttribute('aria-label','Doctor review'+(data.active?', '+data.active+' active':''));
  }catch(_){/* Retry when the connection returns; preserve the last known count. */}
  finally{busy=false;schedule();}
 }
 document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden)refresh();});
 window.addEventListener('focus',refresh);window.addEventListener('pagehide',()=>clearTimeout(timer));
 window.addEventListener('pageshow',event=>{if(event.persisted)refresh();});
 refresh();
})();
