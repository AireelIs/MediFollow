<?php
// Add the link, preserving original text/audio dates. Safe to run again.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/database.php';
$p=database();
$q=$p->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='aftercare_audio' AND COLUMN_NAME='aftercare_id'");
if (!(int)$q->fetchColumn()) {
    $p->exec('ALTER TABLE aftercare_audio ADD COLUMN aftercare_id INT NULL,
        ADD CONSTRAINT fk_audio_entry FOREIGN KEY (aftercare_id) REFERENCES aftercare_instruction(aftercare_id),
        ADD INDEX idx_audio_entry (aftercare_id)');
}
try {
    $p->beginTransaction();
    $rows=$p->query('SELECT audio_id,followup_id,doctor_id,created_at FROM aftercare_audio WHERE aftercare_id IS NULL FOR UPDATE')->fetchAll();
    foreach($rows as $audio) {
        // Only an exact timestamp/author/record match may share an existing entry.
        $q=$p->prepare('SELECT aftercare_id FROM aftercare_instruction WHERE followup_id=? AND doctor_id=? AND created_at=?');
        $q->execute([$audio['followup_id'],$audio['doctor_id'],$audio['created_at']]);$matches=$q->fetchAll();
        if(count($matches)===1) $entry=(int)$matches[0]['aftercare_id'];
        else {
            $q=$p->prepare('INSERT INTO aftercare_instruction (followup_id,doctor_id,instruction,created_at) VALUES (?,?,?,?)');
            $q->execute([$audio['followup_id'],$audio['doctor_id'],'',$audio['created_at']]);$entry=(int)$p->lastInsertId();
        }
        $q=$p->prepare('UPDATE aftercare_audio SET aftercare_id=? WHERE audio_id=?');$q->execute([$entry,$audio['audio_id']]);
    }
    $p->commit();echo "Aftercare text/audio links are ready. Existing content and dates preserved.\n";
} catch(Throwable $e){if($p->inTransaction())$p->rollBack();throw $e;}
