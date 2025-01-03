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
        die('ROTATOR:DENIED');
    }
}

$pid = new StarDust('ROTATOR');

if ($pid->notRunning()) {
    set_time_limit(0);
    $rotator=new OphanimRotator();
    $pid->start();
    $rotator->run();
    $pid->stop();
    die('ROTATOR:OK');
} else {
    die('ROTATOR:SKIP');
}



