<?php

$pid = new StarDust('AGGRTRAFF');

if ($pid->notRunning()) {
set_time_limit(0);
$pid->start();
$harvester=new OphanimHarvester();
$harvester->runTrafficProcessing();
$pid->stop();
    die('AGGRTRAFF:OK');
} else {
    die('AGGRTRAFF:SKIP');
}