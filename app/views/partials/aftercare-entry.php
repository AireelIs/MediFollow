<?php
if (!defined('MEDIFOLLOW_RECORD')) { http_response_code(404); exit; }
?>
<div class="care-entry">
    <?php if ($careEntry['instruction'] !== ''): ?>
        <p class="preserve-text"><?= e($careEntry['instruction']) ?></p>
        <?php require __DIR__.'/aftercare-text-delete-form.php'; ?>
    <?php endif; ?>
    <?php if ($careEntry['recordings']): ?>
        <p class="care-audio-label"><?= $careEntry['instruction'] !== '' ? 'Audio explanation' : 'Recorded instructions' ?></p>
    <?php endif; ?>
    <?php foreach ($careEntry['recordings'] as $voice): ?>
        <audio class="voice-player" controls preload="none" aria-label="Doctor’s recorded instructions" src="audio.php?id=<?= (int)$voice['audio_id'] ?>"></audio>
        <?php $deletableVoice=$voice;require __DIR__.'/audio-delete-form.php'; ?>
    <?php endforeach; ?>
    <p class="instruction-meta"><?= e($careEntry['doctor_name']) ?> <span aria-hidden="true">·</span> <?= e(recordTime($careEntry['created_at'])) ?></p>
</div>
