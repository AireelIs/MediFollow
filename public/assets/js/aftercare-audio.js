/* One continuous audio draft with pause/resume; uploaded only by the shared Save. */
'use strict';
(() => {
  const panel=document.querySelector('[data-audio-recorder]');
  if (!panel) return;
  const form=document.querySelector('[data-aftercare-form]');
  const start=panel.querySelector('[data-audio-start]');
  const stop=panel.querySelector('[data-audio-stop]');
  const buttonLabel=panel.querySelector('[data-audio-label]');
  const cancel=panel.querySelector('[data-audio-cancel]');
  const editor=document.querySelector('#aftercare-editor');
  const editButton=document.querySelector('[data-aftercare-edit]');
  const initialText=form.querySelector('[name=instruction]').value;
  let generation=0;
  function setEditorOpen(open) {
    if (!editor || !editButton) return;
    editor.open=open;editButton.setAttribute('aria-expanded',String(open));editButton.hidden=open;
  }
  if (editor && editButton) {
    editor.setAttribute('data-enhanced','true');
    setEditorOpen(editor.open);
    editButton.addEventListener('click',()=>{setEditorOpen(true);form.querySelector('[name=instruction]').focus();});
  }
  const save=form.querySelector('button[type=submit]');
  const discard=panel.querySelector('[data-audio-discard]');
  const preview=panel.querySelector('[data-audio-preview]');
  const status=panel.querySelector('[data-audio-status]');
  const timer=panel.querySelector('[data-audio-timer]');
  const draftPanel=panel.querySelector('[data-audio-draft]');
  const canRecord=window.isSecureContext && !!navigator.mediaDevices?.getUserMedia && !!window.MediaRecorder;
  const maxMs=120000, maxBytes=2*1024*1024;
  let recorder,stream,blob,url,tick,timeout,chunks=[],bytes=0;
  let phase='idle',activeMs=0,segmentStart=0,stopReason='',pendingSave=false,leaving=false,failed=false;
  const elapsed=()=>Math.min(maxMs,activeMs+(phase==='recording' ? Date.now()-segmentStart : 0));
  const format=ms=>{const s=Math.floor(ms/1000);return Math.floor(s/60)+':'+String(s%60).padStart(2,'0');};
  function showDraft(visible) {
    panel.dataset.draftReady=String(visible);
    draftPanel.setAttribute('aria-hidden',String(!visible));draftPanel.inert=!visible;
  }
  function controls() {
    panel.dataset.recorderState=phase;
    buttonLabel.textContent=phase==='recording'?'Pause':phase==='paused'?'Resume':'Record';
    const canStop=['recording','paused'].includes(phase);
    stop.disabled=!canStop;stop.setAttribute('aria-hidden',String(!canStop));stop.tabIndex=canStop?0:-1;
    start.setAttribute('aria-busy',String(phase==='requesting'));
    panel.dataset.recording=String(phase==='recording');
    panel.dataset.paused=String(phase==='paused');
    start.setAttribute('aria-label',phase==='recording'?'Pause recording':phase==='paused'?'Resume recording':'Add audio explanation');
    start.setAttribute('aria-pressed',String(phase==='recording'));
    start.hidden=phase==='draft';
    start.disabled=!canRecord || ['requesting','finishing','saving'].includes(phase);
    save.disabled=['requesting','finishing','saving'].includes(phase);
    discard.disabled=phase==='saving';
    cancel.disabled=['requesting','finishing','saving'].includes(phase);
  }
  function clearTimers(){clearInterval(tick);clearTimeout(timeout);}
  function release(){clearTimers();stream?.getTracks().forEach(track=>track.stop());stream=null;}
  function runTimer(){
    clearTimers();
    timer.textContent=format(elapsed())+' / 2:00';
    tick=setInterval(()=>{timer.textContent=format(elapsed())+' / 2:00';},250);
    timeout=setTimeout(()=>finish('limit'),Math.max(0,maxMs-elapsed()));
  }
  function clearDraft(){
    pendingSave=false;showDraft(false);preview.pause();preview.removeAttribute('src');preview.removeAttribute('data-recorded-seconds');preview.load();preview.hidden=true;
    if(url)URL.revokeObjectURL(url);url=null;blob=null;discard.hidden=true;
    phase='idle';activeMs=0;timer.textContent='';controls();
  }
  function finish(reason){
    if(!['recording','paused'].includes(phase))return;
    activeMs=elapsed();stopReason=reason;phase='finishing';clearTimers();controls();
    try{recorder.stop();}catch(_){pendingSave=false;release();clearDraft();status.textContent='Recording could not be finished. Please try again.';}
  }
  start.addEventListener('click',async()=>{
    if(phase==='recording'){
      try{recorder.pause();activeMs=elapsed();phase='paused';clearTimers();timer.textContent=format(activeMs)+' / 2:00';controls();status.textContent='';}
      catch(_){status.textContent='Could not pause. Please try again.';}
      return;
    }
    if(phase==='paused'){
      try{recorder.resume();segmentStart=Date.now();phase='recording';controls();runTimer();status.textContent='';}
      catch(_){status.textContent='Could not resume. Choose Save to keep this recording.';}
      return;
    }
    if(phase!=='idle' || !canRecord)return;
    const session=++generation;
    phase='requesting';controls();status.textContent='';
    try{
      stream=await navigator.mediaDevices.getUserMedia({audio:{channelCount:1,echoCancellation:true,noiseSuppression:true},video:false});
      if(leaving){release();return;}
      recorder=new MediaRecorder(stream,{audioBitsPerSecond:64000});
      chunks=[];bytes=0;activeMs=0;failed=false;pendingSave=false;stopReason='';
      stream.getAudioTracks?.().forEach(track=>track.addEventListener('ended',()=>{if(session===generation)finish('microphone');}));
      recorder.addEventListener('dataavailable',event=>{
        if(session!==generation)return;
        if(event.data.size){chunks.push(event.data);bytes+=event.data.size;}
        if(bytes>maxBytes)finish('size');
      });
      recorder.addEventListener('error',()=>{if(session!==generation)return;failed=true;pendingSave=false;release();clearDraft();status.textContent='Recording failed. Please try again.';});
      recorder.addEventListener('stop',()=>{
        if(session!==generation)return;
        activeMs=elapsed();release();
        if(leaving || failed)return;
        blob=new Blob(chunks,{type:recorder.mimeType || chunks[0]?.type || 'audio/webm'});
        if(blob.size<44 || blob.size>maxBytes){clearDraft();status.textContent='Recording could not be used. Try a shorter recording.';return;}
        url=URL.createObjectURL(blob);preview.src=url;preview.hidden=false;
        preview.setAttribute('data-recorded-seconds',String(Math.max(.1,activeMs/1000)));
        phase='draft';discard.hidden=false;showDraft(true);timer.textContent=format(activeMs)+' recorded';controls();
        status.textContent=['manual','limit'].includes(stopReason)?'':'The microphone stopped unexpectedly. Your draft is available below.';
        if(pendingSave){pendingSave=false;form.requestSubmit(save);}
      });
      recorder.start();segmentStart=Date.now();phase='recording';status.textContent='';controls();runTimer();
    }catch(error){release();clearDraft();status.textContent=error.name==='NotAllowedError'?'Microphone access was denied. Allow it in browser settings to record.':'Microphone unavailable. Check it and try again.';}
  });
  stop.addEventListener('click',()=>{
    if(!['recording','paused'].includes(phase))return;
    // Stop creates a local preview only. Only the Save handler may queue upload.
    pendingSave=false;
    finish('manual');
  });
  cancel.addEventListener('click',()=>{
    if(['requesting','finishing','saving'].includes(phase))return;
    generation++;pendingSave=false;
    if(recorder && ['recording','paused'].includes(recorder.state)) {try{recorder.stop();}catch(_) {}}
    release();clearDraft();status.textContent='';
    form.querySelector('[name=instruction]').value=initialText;
    setEditorOpen(false);editButton?.focus();
  });
  discard.addEventListener('click',()=>{clearDraft();status.textContent='';});
  form.addEventListener('submit',async event=>{
    event.preventDefault();
    if(['saving','requesting','finishing'].includes(phase) || pendingSave)return;
    if(['recording','paused'].includes(phase)){pendingSave=true;finish('manual');return;}
    const text=form.querySelector('[name=instruction]').value.trim();
    if(!text && !blob){status.textContent='Add instructions or record audio before saving.';return;}
    phase='saving';controls();status.textContent='Saving…';
    const payload=new FormData(form);if(blob)payload.append('recording',blob,'recording');
    try{
      const response=await fetch(form.getAttribute('action'),{method:'POST',body:payload,credentials:'same-origin',headers:{Accept:'application/json'}});
      if(response.redirected)throw new Error('Your session expired. Sign in again before saving.');
      const result=await response.json();
      if(!response.ok || !result.saved)throw new Error(result.error || 'Aftercare could not be saved.');
      blob=null;window.location.hash='aftercare';window.location.reload();
    }catch(error){phase=blob?'draft':'idle';controls();status.textContent=error instanceof SyntaxError?'Aftercare could not be saved. Please try again.':error.message || 'Connection failed. Try saving again.';}
  });
  window.addEventListener('pagehide',()=>{
    leaving=true;pendingSave=false;
    if(['recording','paused'].includes(phase))finish('manual');
    release();if(url)URL.revokeObjectURL(url);
  });
  window.addEventListener('pageshow',event=>{if(event.persisted){leaving=false;clearDraft();status.textContent='';}});
  controls();
  if(!canRecord)status.textContent='Audio recording needs HTTPS or localhost. You can still save written instructions.';
})();
