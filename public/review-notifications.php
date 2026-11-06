<?php
require dirname(__DIR__).'/app/auth.php';
require dirname(__DIR__).'/app/database.php';
$lastActivity=$_SESSION['last_active'] ?? 0;
$account=requireAccount(['patient','doctor','clinic_staff']);
$_SESSION['last_active']=$lastActivity;
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='GET'){http_response_code(405);header('Allow: GET');echo json_encode(['error'=>'Method not allowed.']);exit;}
try{echo json_encode(['active'=>activeReviewCount(database(),$account)]);}
catch(PDOException $e){http_response_code(503);echo json_encode(['error'=>'Review count is temporarily unavailable.']);}
