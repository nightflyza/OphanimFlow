<?php

$servicesAllowedHost = $ubillingConfig->getAlterParam('SERVICES_HOST');
if (!empty($servicesAllowedHost)) {
    if ($_SERVER['REMOTE_ADDR'] != $servicesAllowedHost) {
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



