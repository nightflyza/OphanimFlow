<?php

$endpointsAllowedHostsRaw = $ubillingConfig->getAlterParam('ENDPOINTS_HOSTS');
if (!empty($endpointsAllowedHostsRaw)) {
    $endpointsAllowedHostsRaw = explode(',', $endpointsAllowedHostsRaw);
    $endpointsAllowedHosts = array();
    foreach ($endpointsAllowedHostsRaw as $io => $each) {
        $ip = trim($each);
        $endpointsAllowedHosts[$ip] = $io;
    }
    if (!isset($endpointsAllowedHosts[$_SERVER['REMOTE_ADDR']])) {
        die('GETTRAFF:DENIED');
    }
}

$year = (ubRouting::get('year', 'int')) ? ubRouting::get('year', 'int') : curyear();
$month = (ubRouting::get('month', 'int')) ? ubRouting::get('month', 'int') : date("n");
$ip = (ubRouting::get('ip', 'fi', FILTER_VALIDATE_IP)) ? ubRouting::get('ip', 'fi', FILTER_VALIDATE_IP) : '';


$harvester = new OphanimHarvester();

$trafCounters = $harvester->getTraffCounters($year, $month, $ip);

header('Content-type: application/json');
die(json_encode($trafCounters));
