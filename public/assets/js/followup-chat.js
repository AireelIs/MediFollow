/* Private follow-up conversation; refreshes only while open and the page is visible. */
'use strict';
(() => {
  const root=document.querySelector('[data-chat]');if(!root)return;
  const form=root.querySelector('[data-chat-form]'),input=form.querySelector('textarea'),send=form.querySelector('button');
  const log=root.querySelector('[data-chat-log]'),empty=root.querySelector('[data-chat-empty]');
  const status=root.querySelector('[data-chat-status]'),earlier=root.querySelector('[data-chat-earlier]'),badge=root.querySelector('[data-chat-unread]');
  const endpoint=form.getAttribute('action');
  const nodes=new Map();let newest=0,oldest=0,ack=0,loaded=false,loading=false,sending=false,stopped=false,timer;
  let pendingText='',pendingNonce='',sendError=false;
  form.hidden=false;
  const nonce=()=>Array.from(crypto.getRandomValues(new Uint8Array(16)),v=>v.toString(16).padStart(2,'0')).join('');
  async function request(fields=null,query='') {
    const response=await fetch(endpoint+query,fields?{method:'POST',credentials:'same-origin',headers:{Accept:'application/json'},body:new URLSearchParams({csrf:root.dataset.csrf,...fields})}:{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
    if(response.redirected){stopped=true;throw new Error('Your session expired. Keep your draft and sign in again.');}
    let data;try{data=await response.json();}catch(_){throw new Error('Messages could not be loaded. Please try again.');}
    if(response.status===404){stopped=true;clearTimeout(timer);root.hidden=true;send.disabled=true;window.location.reload();throw new Error('This chat is closed.');}
    if(!response.ok)throw new Error(data.error || 'Messages are temporarily unavailable.');return data;
  }
  function render(messages,forceBottom=false,older=false) {
    const nearBottom=log.scrollHeight-log.scrollTop-log.clientHeight<70;
    const priorHeight=log.scrollHeight,priorTop=log.scrollTop;
    for(const message of messages){
      if(nodes.has(message.id))continue;
      const article=document.createElement('article');article.className='chat-message'+(message.mine?' mine':'');article.dataset.messageId=String(message.id);
      const meta=document.createElement('div');meta.className='chat-message-meta';
      const name=document.createElement('strong');name.textContent=message.mine?'You':message.name;
      const role=document.createElement('span');role.textContent=message.role;
      meta.append(name,role);
      const text=document.createElement('p');text.className='chat-message-text';text.textContent=message.body;
      const date=document.createElement('time');date.dateTime=message.datetime;date.textContent=message.time;
      article.append(meta,text,date);
      const next=Array.from(nodes.keys()).sort((a,b)=>a-b).find(id=>id>message.id);
      if(next)log.insertBefore(article,nodes.get(next));else log.append(article);
      nodes.set(message.id,article);oldest=oldest?Math.min(oldest,message.id):message.id;
    }
    empty.hidden=nodes.size>0;
    if(older)log.scrollTop=priorTop+(log.scrollHeight-priorHeight);
    else if(forceBottom || nearBottom)log.scrollTop=log.scrollHeight;
  }
  async function markRead(){
    if(!root.open || document.hidden || !newest || newest<=ack)return;
    try{await request({action:'read',last_seen:String(newest)});ack=newest;badge.hidden=true;}catch(_){/* A later refresh retries read acknowledgement. */}
  }
  function schedule(){clearTimeout(timer);if(root.open && !document.hidden && !stopped)timer=setTimeout(sync,10000);}
  async function sync(){
    if(loading || !root.open || document.hidden || stopped)return;
    loading=true;
    try{
      let rounds=0,more;
      do{
        const initialWindow=!loaded || newest===0;
        const data=await request(null,loaded?'&after='+newest:'');
        render(data.messages,!loaded);if(initialWindow)earlier.hidden=!data.has_more;
        for(const message of data.messages)newest=Math.max(newest,message.id);
        more=loaded && data.has_more;loaded=true;rounds++;
      }while(more && rounds<5);
      if(!sending && !sendError)status.textContent='';await markRead();
    }catch(error){status.textContent=error.message;}
    finally{loading=false;schedule();}
  }
  earlier.addEventListener('click',async()=>{
    if(!oldest || loading)return;loading=true;earlier.disabled=true;
    try{const data=await request(null,'&before='+oldest);render(data.messages,false,true);earlier.hidden=!data.has_more;status.textContent='';}
    catch(error){status.textContent=error.message;}
    finally{loading=false;earlier.disabled=false;schedule();}
  });
  form.addEventListener('submit',async event=>{
    event.preventDefault();if(sending || stopped)return;
    const body=input.value.trim();if(!body)return;
    if(body!==pendingText || !pendingNonce){pendingText=body;pendingNonce=nonce();}
    sending=true;sendError=false;send.disabled=true;status.textContent='Sending…';
    try{
      const data=await request({action:'send',body,nonce:pendingNonce});render([data.message],true);
      if(input.value.trim()===body)input.value='';pendingNonce='';pendingText='';status.textContent='';
      await sync();
    }catch(error){sendError=true;status.textContent=error.message;}
    finally{sending=false;send.disabled=stopped;}
  });
  input.addEventListener('keydown',event=>{if(event.key==='Enter' && !event.shiftKey && !event.isComposing){event.preventDefault();if(!event.repeat)form.requestSubmit(send);}});
  root.addEventListener('toggle',()=>{clearTimeout(timer);if(root.open)sync();});
  document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden && root.open)sync();});
  window.addEventListener('hashchange',()=>{if(location.hash==='#chat'){root.open=true;sync();}});
  window.addEventListener('pagehide',()=>clearTimeout(timer));
  window.addEventListener('pageshow',event=>{if(event.persisted && root.open)sync();});
  if(location.hash==='#chat'){root.open=true;sync();}else if(root.open){sync();}
})();
