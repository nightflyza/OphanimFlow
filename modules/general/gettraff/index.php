<?php

$year = (ubRouting::get('year', 'int')) ? ubRouting::get('year', 'int') : curyear();
$month = (ubRouting::get('month', 'int')) ? ubRouting::get('month', 'int') : date("m");
$ip = (ubRouting::get('ip', 'fi', FILTER_VALIDATE_IP)) ? ubRouting::get('ip', 'fi', FILTER_VALIDATE_IP) : '';


$harvester = new OphanimHarvester();

$trafCounters = $harvester->getTraffCounters($year,$month,$ip);

header('Content-type: application/json');
die(json_encode($trafCounters));
