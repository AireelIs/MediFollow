<?php
// Follow-up permissions and detail queries shared by the full-record page.
function patientConditionChoices(): array
{
    return ['Much better', 'Better', 'Same', 'Worse', 'Much worse'];
}

function visibleFollowUp(PDO $pdo, array $account, int $id, bool $lock = false): ?array
{
    $sql = 'SELECT f.followup_id AS id, f.patient_id, f.doctor_id,
        f.followup_date AS follow_up_date, f.followup_time, f.purpose AS reason, f.status,
        p.name AS full_name, p.ic_number, d.name AS doctor_name
        FROM follow_up f JOIN patient p ON p.patient_id=f.patient_id
        JOIN doctor d ON d.doctor_id=f.doctor_id WHERE f.followup_id=?';
    $params = [$id];
    if ($account['role'] === 'patient') {
        $sql .= ' AND f.patient_id=?';
        $params[] = $account['id'];
    } elseif ($account['role'] === 'doctor') {
        $sql .= ' AND f.doctor_id=?';
        $params[] = $account['id'];
    }
    if ($lock) $sql .= ' FOR UPDATE';
    $query = $pdo->prepare($sql);
    $query->execute($params);
    return $query->fetch() ?: null;
}

function requestText(array $data, string $field): string
{
    return is_string($data[$field] ?? null) ? trim($data[$field]) : '';
}

function recordHistories(PDO $pdo, int $id): array
{
    $query = $pdo->prepare('SELECT a.instruction,a.created_at,a.aftercare_id,a.doctor_id,d.name AS doctor_name
        FROM aftercare_instruction a JOIN doctor d ON d.doctor_id=a.doctor_id
        WHERE a.followup_id=? ORDER BY a.created_at DESC,a.aftercare_id DESC');
    $query->execute([$id]);
    $aftercare = $query->fetchAll();

    $query = $pdo->prepare('SELECT update_id,`condition`,details,submitted_at FROM patient_status_update
        WHERE followup_id=? ORDER BY submitted_at DESC,update_id DESC');
    $query->execute([$id]);
    $updates = $query->fetchAll();

    return ['aftercare' => $aftercare, 'updates' => $updates, 'requests' => []];
}
