<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/database.php';
$pdo=database();
$columns=$pdo->query('SHOW COLUMNS FROM patient')->fetchAll(PDO::FETCH_COLUMN);
if(!in_array('ic_number',$columns,true))$pdo->exec('ALTER TABLE patient ADD COLUMN ic_number CHAR(12) NULL DEFAULT NULL, ADD UNIQUE KEY unique_patient_ic (ic_number)');
$pdo->exec('ALTER TABLE patient MODIFY phone_number VARCHAR(15) NULL DEFAULT NULL');
echo "IC field ready. Existing records and legacy sign-in preserved until staff adds an IC.\n";
