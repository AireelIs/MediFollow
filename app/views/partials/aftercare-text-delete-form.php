<?php
if (!defined('MEDIFOLLOW_RECORD')) { http_response_code(404); exit; }
if ($account['role'] === 'doctor' && (int)$careEntry['doctor_id'] === (int)$account['id']): ?>
<form class="voice-delete-form aftercare-text-delete-form" action="aftercare-text-delete.php" method="post" onsubmit="return confirm('Delete these written instructions? Any audio will remain. This cannot be undone.');">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="aftercare_id" value="<?= (int)$careEntry['aftercare_id'] ?>">
    <button class="voice-delete-button" type="submit" aria-label="Delete written instructions from <?= e(recordTime($careEntry['created_at'])) ?>"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v5M14 11v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Delete text</span></button>
</form>
<?php endif; ?>
