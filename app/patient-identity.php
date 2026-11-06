<?php
function normalizedPatientIc(string $value): ?string
{
    $value=trim($value);
    if(!preg_match('/\A(?:[0-9]{12}|[0-9]{6}-[0-9]{2}-[0-9]{4})\z/',$value))return null;
    return str_replace('-','',$value);
}

function patientIcLabel(?string $ic): string
{
    if(!$ic)return 'IC not added';
    return 'IC · '.substr($ic,0,6).'-'.substr($ic,6,2).'-'.substr($ic,8,4);
}
