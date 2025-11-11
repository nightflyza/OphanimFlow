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

set_time_limit(60);
$graph = new OphanimGraph();

$ip = ubRouting::get('ip', 'fi', FILTER_VALIDATE_IP);
$direction = (ubRouting::get('dir', 'vf') == 'S') ? 'S' : 'R';
$w = (ubRouting::get('w', 'int')) ? ubRouting::get('w', 'int') : 1540;
$h = (ubRouting::get('h', 'int')) ? ubRouting::get('h', 'int') : 400;

$customTitle = '';
if ($ubillingConfig->getAlterParam('CHARTS_NETDESC')) {
    $netLib = new OphanimNetLib();
    $netDesc = $netLib->getIpNetDescription($ip);
    if ($netDesc) {
        $customTitle .= ' (' . $netDesc . ')';
    }
}

//day by default
$dateFrom = date("Y-m-d 00:00:00");
$dateTo = date ("Y-m-d H:i:s"); //till now

$preallocTimelineFlag = ($ubillingConfig->getAlterParam('CHARTS_PREALLOC_TIMELINE')) ? true : false;
if ($preallocTimelineFlag) {
    $dateTo = date("Y-m-d 23:59:59"); //full day timeline
}

if (ubRouting::checkGet('period')) {
    $period = ubRouting::get('period');
    switch ($period) {
        case '24h':
            $dateFrom = date("Y-m-d H:i:s", strtotime("-24 hour", time()));
            $dateTo = date("Y-m-d H:i:s");
            break;

        case '48h':
            $dateFrom = date("Y-m-d H:i:s", strtotime("-48 hour", time()));
            $dateTo = date("Y-m-d H:i:s");
            break;

        case 'hour':
            $dateFrom = date("Y-m-d H:i:s", strtotime("-1 hour", time()));
            $dateTo = date("Y-m-d H:i:s");
            break;

        case 'day':
            $dateFrom = date("Y-m-d 00:00:00");
            $dateTo = date ("Y-m-d H:i:s");
            if ($preallocTimelineFlag) {
                $dateTo = date("Y-m-d 23:59:59"); 
            }
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

        case 'explict':
            if (ubRouting::checkGet(array('from', 'to'))) {
                $timeStampFrom = ubRouting::get('from');
                $timeStampTo = ubRouting::get('to');
                if ($timeStampFrom < $timeStampTo) {
                    $dateFrom = date("Y-m-d H:i:s", $timeStampFrom);
                    $dateTo = date("Y-m-d H:i:s", $timeStampTo);
                }
            }
            break;
    }
}

$graph->renderGraph($ip, $direction, $dateFrom, $dateTo, $w, $h, $customTitle);
