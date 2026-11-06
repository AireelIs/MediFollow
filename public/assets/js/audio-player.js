/* Small native-audio enhancement; the original controls remain if JavaScript is off. */
'use strict';
(() => {
  const players = document.querySelectorAll('audio.voice-player');
  const playIcon = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m9 5 11 7-11 7z" fill="currentColor"/></svg>';
  const pauseIcon = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M7 5h4v14H7zm6 0h4v14h-4z" fill="currentColor"/></svg>';
  const time = value => {
    const seconds = Number.isFinite(value) ? Math.max(0,Math.floor(value)) : 0;
    return Math.floor(seconds/60)+':'+String(seconds%60).padStart(2,'0');
  };
  players.forEach(audio => {
    const box = document.createElement('div'); box.className = 'compact-audio';
    box.setAttribute('role','group'); box.setAttribute('aria-label',audio.getAttribute('aria-label') || 'Audio recording');
    const play = document.createElement('button'); play.type='button'; play.className='compact-audio-play';
    play.innerHTML = playIcon; play.setAttribute('aria-label','Play recording');
    const seek = document.createElement('input'); seek.type='range'; seek.min='0'; seek.max='100'; seek.value='0'; seek.step='0.1'; seek.disabled=true;
    seek.setAttribute('aria-label','Playback position');
    const clock = document.createElement('span'); clock.className='compact-audio-time'; clock.textContent='0:00'; clock.setAttribute('aria-hidden','true');
    const error = document.createElement('span'); error.className='compact-audio-error'; error.setAttribute('role','status');
    const redo = audio.closest?.('[data-audio-draft]')?.querySelector('[data-audio-discard]');
    box.append(play,seek,clock);
    if (redo) box.append(redo);
    box.append(error); audio.after(box);
    audio.controls=false; audio.classList.add('audio-enhanced');
    function refresh() {
      box.hidden = audio.hidden || !audio.getAttribute('src');
      const duration = Number.isFinite(audio.duration) && audio.duration > 0 ? audio.duration : (Number(audio.getAttribute('data-recorded-seconds')) || (audio.seekable.length ? audio.seekable.end(audio.seekable.length-1) : 0));
      seek.disabled = !(duration>0); seek.max=String(duration || 100); seek.value=String(audio.currentTime || 0);
      seek.style.setProperty('--audio-progress', String(duration ? Math.min(100,(audio.currentTime || 0)/duration*100) : 0)+'%');
      seek.setAttribute('aria-valuetext',time(audio.currentTime)+(duration ? ' of '+time(duration) : ''));
      clock.textContent=time(audio.currentTime)+(duration ? ' / '+time(duration) : '');
      const playing=!audio.paused && !audio.ended;
      play.innerHTML=playing ? pauseIcon : playIcon;
      play.setAttribute('aria-label',playing ? 'Pause recording' : 'Play recording');
    }
    play.addEventListener('click',async () => {
      error.textContent='';
      if (!audio.paused) { audio.pause(); return; }
      players.forEach(other => { if (other !== audio) other.pause(); });
      try { await audio.play(); } catch (_) { error.textContent='Unable to play. Please try again.'; }
      refresh();
    });
    seek.addEventListener('input',() => {
      if (!seek.disabled) { audio.currentTime=Number(seek.value); refresh(); }
    });
    ['loadedmetadata','durationchange','timeupdate','play','pause','ended','emptied','progress'].forEach(event => audio.addEventListener(event,refresh));
    audio.addEventListener('error',() => { error.textContent='Audio unavailable. Refresh and try again.'; });
    new MutationObserver(() => { error.textContent=''; refresh(); }).observe(audio,{attributes:true,attributeFilter:['hidden','src','data-recorded-seconds']});
    refresh();
  });
})();
