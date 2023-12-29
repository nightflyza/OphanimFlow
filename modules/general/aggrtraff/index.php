<?php

$servicesAllowedHost = $ubillingConfig->getAlterParam('SERVICES_HOST');
if (!empty($servicesAllowedHost)) {
    if ($_SERVER['REMOTE_ADDR'] != $servicesAllowedHost) {
        die('AGGRTRAFF:DENIED');
    }
}

$pid = new StarDust('AGGRTRAFF');

if ($pid->notRunning()) {
    set_time_limit(0);
    $pid->start();
    $harvester = new OphanimHarvester();
    $harvester->runTrafficProcessing();
    $pid->stop();
    die('AGGRTRAFF:OK');
} else {
    die('AGGRTRAFF:SKIP');
}
