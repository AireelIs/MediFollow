<?php
// Shared across browser sessions. Keep the file outside the public web folder.
// A short, exclusive lock makes simultaneous attempts count correctly.
function loginRetryAfter(string $role, string $identifier): int
{
    $directory = getenv('MEDIFOLLOW_RATE_DIR') ?: sys_get_temp_dir() . '/medifollow-login-' . substr(hash('sha256', dirname(__DIR__)), 0, 16);
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Login protection storage is unavailable.');
    }
    $file = @fopen($directory . '/attempts.json', 'c+');
    if (!$file) throw new RuntimeException('Login protection storage is unavailable.');
    try {
        if (!flock($file, LOCK_EX)) throw new RuntimeException('Login protection lock failed.');
        $contents = stream_get_contents($file);
        $entries = $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($entries)) throw new RuntimeException('Invalid login protection storage.');
        $now = time();
        foreach ($entries as $key => $entry) {
            if ($entry['until'] <= $now) unset($entries[$key]);
        }
        // REMOTE_ADDR comes from the server. Untrusted forwarded headers are ignored.
        $limits = [
            'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') => 60,
            'account:' . $role . ':' . strtolower($identifier) => 5,
        ];
        $retry = 0;
        foreach ($limits as $identity => $limit) {
            $key = hash('sha256', $identity);
            if (($entries[$key]['count'] ?? 0) >= $limit) {
                $retry = max($retry, $entries[$key]['until'] - $now);
            }
        }
        if ($retry === 0) {
            foreach ($limits as $identity => $limit) {
                $key = hash('sha256', $identity);
                $entries[$key] ??= ['count' => 0, 'until' => $now + 60];
                $entries[$key]['count']++;
            }
            $encoded = json_encode($entries, JSON_THROW_ON_ERROR);
            rewind($file);
            if (!ftruncate($file, 0) || fwrite($file, $encoded) !== strlen($encoded) || !fflush($file)) {
                throw new RuntimeException('Login protection could not save.');
            }
        }
        return $retry;
    } finally {
        fclose($file); // Also releases the exclusive lock.
    }
}
