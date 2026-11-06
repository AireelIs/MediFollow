<?php
// Reusable detail/create forms and their no-JavaScript fallbacks.
if (!defined('MEDIFOLLOW_DASHBOARD')) {
    http_response_code(404);
    exit;
}
?>
<?php function recordContent(array $r, bool $canUpdate, string $csrf): void { ?>
    <div class="followup-dialog-heading">
        <h2><?= $canUpdate ? 'Edit follow-up' : 'Follow-up details' ?></h2>
        <a class="record-open-link" href="record.php?id=<?= (int)$r['id'] ?>">Open record ↗</a>
    </div>
    <div class="followup-patient-summary">
        <strong><?= e($r['full_name']) ?></strong><span class="patient-ic muted"><?= e(patientIcLabel($r['ic_number'])) ?></span>
        <dl>
            <div><dt>Doctor</dt><dd><?= e($r['doctor_name']) ?></dd></div>
            <div><dt>Latest update</dt><dd><?= e($r['patient_condition'] ?? 'No update yet') ?></dd></div>
        </dl>
    </div>
    <?php if($canUpdate): ?>
        <form class="followup-form" action="index.php" method="post">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <label>
                Purpose
                <input name="purpose" maxlength="255" value="<?= e($r['reason']) ?>" required>
            </label>
            <div class="form-grid">
                <label>
                    Date
                    <input name="date" type="date" value="<?= e($r['follow_up_date']) ?>" required>
                </label>
                <label>
                    Time
                    <input name="time" type="time" value="<?= e(substr($r['followup_time'],0,5)) ?>" required>
                </label>
            </div>
            <label>
                Progress
                <select name="completed">
                    <option value="0" <?= $r['status']!=='Completed'?'selected':'' ?>>Pending</option>
                    <option value="1" <?= $r['status']==='Completed'?'selected':'' ?>>Completed</option>
                </select>
            </label>
            <div class="actions">
                <button type="button" class="secondary" data-close>Close</button>
                <button class="primary">Save changes</button>
            </div>
        </form>
    <?php else: ?>
        <dl class="followup-readonly-details">
            <div><dt>Date</dt><dd><?= e(prettyDate($r['follow_up_date'])) ?></dd></div>
            <div><dt>Time</dt><dd><?= e(substr($r['followup_time'],0,5)) ?></dd></div>
            <div class="followup-purpose"><dt>Purpose</dt><dd><?= e($r['reason']) ?></dd></div>
            <div class="followup-status"><dt>Status</dt><dd><span class="pill <?= e(strtolower(followUpStatus($r))) ?>"><?= e(followUpStatus($r)) ?></span></dd></div>
        </dl>
        <div class="actions followup-dialog-footer">
            <button class="secondary" type="button" data-close>Close</button>
        </div>
    <?php endif; ?>
<?php } ?>
<?php foreach($baseRecords as $r): ?>
    <template id="record-<?= (int)$r['id'] ?>">
        <?php recordContent($r,$canUpdate,$csrf); ?>
    </template>
<?php endforeach; ?>
<dialog id="record-dialog" aria-label="Follow-up details">
</dialog>
<?php if($canUpdate): ?>
    <?php function createContent(array $patients, array $doctors, string $csrf): void { ?>
        <h2>New follow-up</h2>
        <p class="muted">Record the follow-up agreed by the doctor for an existing patient.</p>
        <form class="followup-form" action="index.php" method="post">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="create">
            <label>
                Patient
                <select name="patient_id" required>
                    <option value="">Choose patient</option>
                    <?php foreach($patients as $patient): ?>
                        <option value="<?= (int)$patient['patient_id'] ?>"><?= e($patient['name']) ?> · <?= e(patientIcLabel($patient['ic_number'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Doctor
                <select name="doctor_id" required>
                    <option value="">Choose doctor</option>
                    <?php foreach($doctors as $doctor): ?>
                        <option value="<?= (int)$doctor['doctor_id'] ?>"><?= e($doctor['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Follow-up purpose
                <input name="purpose" maxlength="255" required>
            </label>
            <div class="form-grid">
                <label>
                    Follow-up date
                    <input type="date" name="date" value="<?= e(date('Y-m-d')) ?>" required>
                </label>
                <label>
                    Time
                    <input type="time" name="time" required>
                </label>
            </div>
            <div class="actions">
                <button type="button" class="secondary" data-close>Cancel</button>
                <button class="primary" <?= !$patients||!$doctors?'disabled':'' ?>>Create follow-up</button>
            </div>
        </form>
    <?php } ?>
    <dialog id="create-dialog" aria-label="Create follow-up">
        <?php createContent($patients,$doctors,$csrf); ?>
    </dialog>
    <?php if(isset($_GET['create'])): ?>
        <section class="panel attention" style="margin-top:20px">
            <?php createContent($patients,$doctors,$csrf); ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
<?php
// Show a full-page details section when the dialog link is opened without JavaScript.
$requestedId = filter_var(
    $_GET['record'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
if ($requestedId):
    foreach ($baseRecords as $r):
        if ((int) $r['id'] === $requestedId):
?>
    <section class="panel attention" style="margin-top:20px">
        <?php recordContent($r,$canUpdate,$csrf); ?>
    </section>
<?php endif;endforeach;endif; ?>
