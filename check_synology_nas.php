<?php
/* 
Synology-NAS Plugin/Check for Nagios/Icinga2

Version: master

V4n1X (C)2019

 */
error_reporting(E_ERROR);

$host = $argv[1];
$community = "public";

$critical = false;
$warning = false;

$output = "";

try {

$systemStatus = snmpget($host, $community, ".1.3.6.1.4.1.6574.1.1.0");
$systemStatus = str_replace("INTEGER: ", "", $systemStatus);

if(!$systemStatus) {
    fwrite(STDOUT, "KRITISCH: Verbindung konnte nicht hergestellt werden.");
  	exit(2);
}

if($systemStatus == 2) {
  $critical = true;
  $systemStatus = "Festplattendefekt";
  $output .= "Festplattendefekt" . " - ";
} else {
  $systemStatus = "Normal";
}

$temperature = snmpget($host, $community, ".1.3.6.1.4.1.6574.1.2.0");
$temperature = str_replace("INTEGER: ", "", $temperature);

if($temperature >= 45) {
  $critical = true;
  $output .= "Hohe Temperatur (" . $temperature . "°C) - ";
}


$powerStatus = snmpget($host, $community, ".1.3.6.1.4.1.6574.1.3.0");
$powerStatus = str_replace("INTEGER: ", "", $powerStatus);

if($powerStatus == 2) {
  $critical = true;
  $output .= "Netzteil defekt" . " - ";
} else {
  $powerStatus = "Normal";
}

$systemFanStatus = snmpget($host, $community, ".1.3.6.1.4.1.6574.1.4.1.0");
$systemFanStatus = str_replace("INTEGER: ", "", $systemFanStatus);

if($systemFanStatus == 2) {
  $critical = true;
  $output .= "Lüfter defekt" . " - ";
} else {
  $systemFanStatus = "Normal";
}

$cpuFanStatus = snmpget($host, $community, ".1.3.6.1.4.1.6574.1.4.2.0");
$cpuFanStatus = str_replace("INTEGER: ", "", $cpuFanStatus);

if($cpuFanStatus == 2) {
  $critical = true;
  $output .= "CPU-Lüfter defekt" . " - ";
} else {
  $cpuFanStatus = "Normal";
}


$raidStatus = snmpget($host, $community, ".1.3.6.1.4.1.6574.3.1.1.3.0");
$raidStatus = str_replace("INTEGER: ", "", $raidStatus);

if($raidStatus != 1) {
  $critical = true;
  $raidStatus = getRaidStatus($raidStatus);
  $output .= "RAID-Status: " . $raidStatus . " - ";
}

$modelName = snmpget($host, $community, ".1.3.6.1.4.1.6574.1.5.1.0");
$modelName = str_replace("STRING: ", "", $modelName);
$modelName = str_replace("\"", "", $modelName);


for($i = 1; $i < 5; $i++) {

$diskStatus = snmpget($host, $community, ".1.3.6.1.4.1.6574.2.1.1.5." . $i);

if(!$diskStatus || empty($diskStatus)) {
  continue;
}

$diskStatus = str_replace("INTEGER: ", "", $diskStatus);

if($diskStatus != 1) {
  $output .= "Festplattenstatus (" . $i . "): " . getDiskStatus($diskStatus) . " - ";
  $critical = true;
}

}

$output = rtrim($output, " - ");

if($critical) {
  fwrite(STDOUT, $output);
	exit(2);
}

if($warning) {
  fwrite(STDOUT, $output);
	exit(1);
}

fwrite(STDOUT, "<b>Modell:</b> " . $modelName . " ⚊ <b>Status:</b> " . $systemStatus . " ⚊ <b>RAID:</b> " . getRaidStatus($raidStatus) . " ⚊ <b>Power:</b> " . $powerStatus . " - <b>Lüfter:</b> " . $systemFanStatus . " ⚊ <b>CPU-Lüfter:</b> " . $cpuFanStatus);
exit(0);

} catch (Exception $e) {
  fwrite(STDOUT, "KRITISCH: Verbindung konnte nicht hergestellt werden.");
	exit(2);
}


function getRaidStatus($code) {

  $status = "";

  switch ($code) {

    case 1:
    $status = "Normal";
    break;

    case 11:
    $status = "Degraded";
    break;

    case 12:
    $status = "Crashed";
    break;

  }

  return $status;

}

function getDiskStatus($code) {

  $status = "";

  switch ($code) {

    case 1:
    $status = "Normal";
    break;

    case 2:
    $status = "Initialisiert, ohne Daten";
    break;

    case 3:
    $status = "Keine System Partition gefunden";
    break;

    case 4:
    $status = "Systempartitionen beschädigt";
    break;

    case 5:
    $status = "Defekt";
    break;

    default:
    $status = $code;
    break;

  }

  return $status;

}

?>
