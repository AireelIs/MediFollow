<?php
if (!defined('MEDIFOLLOW_RECORD')) { http_response_code(404); exit; }
?>
<div class="aftercare-voice" data-audio-recorder data-followup="<?= (int)$id ?>">
    <div class="voice-actions voice-control-row">
        <div class="voice-control-buttons">
        <button class="secondary voice-toggle" type="button" data-audio-start aria-label="Add audio explanation" aria-pressed="false">
            <span class="voice-toggle-icon" aria-hidden="true"><svg class="voice-toggle-mic" width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="9" y="3" width="6" height="12" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M6 11v1a6 6 0 0 0 12 0v-1M12 18v3M9 21h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><span class="voice-toggle-stop"></span><span class="voice-toggle-resume"></span></span>
            <span class="voice-toggle-label" data-audio-label aria-hidden="true">Record</span>
        </button>
        <button class="voice-recording-stop" data-audio-stop type="button" disabled aria-hidden="true" tabindex="-1"><span aria-hidden="true"></span>Stop</button>
        </div>
        <span class="voice-recording-readout">
        <span class="voice-recording-indicator" aria-hidden="true"><span class="voice-recording-dot"></span></span>
        <span class="voice-timer" data-audio-timer aria-label="Recording duration"></span>
        </span>
        <button class="secondary aftercare-cancel" type="button" data-audio-cancel>Cancel</button>
        <button class="primary aftercare-save" type="submit">Save</button>
    </div>
    <div class="voice-preview-shell" data-audio-draft aria-hidden="true" inert>
        <div class="voice-preview-inner">
            <div class="voice-preview-content">
                <audio class="voice-player" data-audio-preview controls preload="none" aria-label="Preview unsaved voice instructions" hidden></audio>
                <button class="voice-redo" type="button" data-audio-discard aria-label="Discard recording and start again" hidden><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10a8 8 0 1 1 1 8M4 4v6h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Redo</span></button>
            </div>
        </div>
    </div>
    <p class="voice-status muted" data-audio-status role="status" aria-live="polite"></p>
    <noscript><p class="muted">Enable JavaScript to record audio. Written instructions can still be saved.</p></noscript>
</div>
