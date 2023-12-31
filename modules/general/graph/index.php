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
        die('GRAPH:DENIED');
    }
}

$graph = new OphanimGraph();

$ip = ubRouting::get('ip', 'fi', FILTER_VALIDATE_IP);
$direction = (ubRouting::get('dir', 'vf') == 'S') ? 'S' : 'R';
$w = (ubRouting::get('w', 'int')) ? ubRouting::get('w', 'int') : 1540;
$h = (ubRouting::get('h', 'int')) ? ubRouting::get('h', 'int') : 400;

//day by default
$dateFrom = date("Y-m-d 00:00:00");
$dateTo = date("Y-m-d H:i:s");

if (ubRouting::checkGet('period')) {
    $period = ubRouting::get('period');
    switch ($period) {
        case 'hour':
            $dateFrom = date("Y-m-d H:i:s", strtotime("-1 hour", time()));
            $dateTo = date("Y-m-d H:i:s");
            break;

        case 'day':
            $dateFrom = date("Y-m-d 00:00:00");
            $dateTo = date("Y-m-d H:i:s");
            break;

        case 'week':
            $dateFrom = date("Y-m-d H:i:s", strtotime("-7 day", time()));
            $dateTo = date("Y-m-d H:i:s");
            break;

        case 'month':
            $dateFrom = date("Y-m-d H:i:s", strtotime("-1 month", time()));
            $dateTo = date("Y-m-d H:i:s");
            break;

        case 'year':
            $dateFrom = date("Y-m-d H:i:s", strtotime("-1 year", time()));
            $dateTo = date("Y-m-d H:i:s");
            break;
    }
}

$graph->renderGraph($ip, $direction, $dateFrom, $dateTo, $w, $h);
