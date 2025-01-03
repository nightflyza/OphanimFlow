<?php

$servicesAllowedHost = $ubillingConfig->getAlterParam('SERVICES_HOST');
if (!empty($servicesAllowedHost)) {
    if ($_SERVER['REMOTE_ADDR'] != $servicesAllowedHost) {
        die('AGGRGDATA:DENIED');
    }
}
$rotatorProcess = new StarDust('ROTATOR');
$pid = new StarDust('AGGRGDATA');

if ($pid->notRunning() and $rotatorProcess->notRunning()) {
    set_time_limit(0);
    $pid->start();
    $classifier = new OphanimClassifier();
    $receivedData = $classifier->aggregateSource($classifier::TABLE_RAW_OUT, 'ip_dst', 'port_src'); //dload
    $classifier->saveAggregatedData('R', $receivedData);
    $classifier->saveLastRunData('R', $receivedData);

    $sentData = $classifier->aggregateSource($classifier::TABLE_RAW_IN, 'ip_src', 'port_dst'); //upload
    $classifier->saveAggregatedData('S', $sentData);
    $classifier->saveLastRunData('S', $sentData);
    $pid->stop();

    die('AGGRGDATA:OK');
} else {
    die('AGGRGDATA:SKIP');
}
