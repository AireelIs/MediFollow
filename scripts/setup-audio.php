<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/database.php';
try {
    database()->exec(file_get_contents(dirname(__DIR__) . '/database/aftercare-audio.sql'));
    require __DIR__ . '/link-aftercare-audio.php';
    echo "Private aftercare recording table is ready. Existing records were preserved.\n";
} catch (PDOException $error) {
    fwrite(STDERR, "Audio setup failed. Start MySQL and check database permissions.\n");
    exit(1);
}
