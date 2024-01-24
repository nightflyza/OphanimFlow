<?php

if (cfr('ROOT')) {
    set_time_limit(0);
    $result = '';
    $form = '';

    $classifier = new OphanimClassifier();
    $baseStruct = $classifier->getBaseStruct();

    $direction = ubRouting::get('direction', 'vf');
    $protoFilter = ubRouting::get('proto', 'int');
    $depth = ubRouting::get('depth', 'int');
    $depth = 3;
    //search form anyway
    $directionsAvail = array('R' => __('Download'), 'S' => __('Upload'));


    foreach ($baseStruct as $protoId => $protoName) {
        $reportUrl = '?module=protofilter&direction=R&proto=' . $protoId;
        $form .= wf_AjaxLink($reportUrl, $protoName, 'reportcontainer', false, 'btn btn-primary btn-color') . ' ';
    }

    $form .= wf_AjaxLoader();
    $form .= wf_AjaxContainer('reportcontainer', '', '');
    show_window(__('Top by protocol'), $form);

    //rendering report
    if ($direction and $depth) {

        $dateFrom = date("Y-m-d H:i:s", (time() - (3600 * $depth)));
        $dateTo = curdatetime();
        $filterOffset = $protoFilter + 1; //timestamp is at 0
        $grapher = new OphanimGraph();


        debarr($baseStruct);
        $allTracksData = rcms_scandir($classifier::DATA_PATH, $direction . '_*');
        $allTrackedIps = array();
        $ipByteCount = array();

        if (!empty($allTracksData)) {
            foreach ($allTracksData as $io => $eachFile) {
                $cleanIp = str_replace($direction . '_', '', $eachFile);
                $allTrackedIps[] = $cleanIp;
            }
        }


        if (!empty($allTrackedIps)) {
            foreach ($allTrackedIps as $idx => $eachIp) {
                $rawData = $grapher->getChartData($eachIp, $direction, $dateFrom, $dateTo);
                if (!empty($rawData)) {
                    foreach ($rawData as $io => $each) {
                        if (isset($each[$filterOffset])) {
                            $byteCount = $each[$filterOffset];
                            if ($byteCount) {
                                if (isset($ipByteCount[$eachIp])) {
                                    $ipByteCount[$eachIp] += $byteCount;
                                } else {
                                    $ipByteCount[$eachIp] = $byteCount;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($ipByteCount)) {
            arsort($ipByteCount);
            //rendering report here
            $protoDesc = strtoupper($baseStruct[$protoFilter]);
            $cells = wf_TableCell(__('Host'));
            $cells .= wf_TableCell($protoDesc . ' ' . __('traffic'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($ipByteCount as $eachIp => $eachByteCount) {
                $hostName = ($eachIp == '0.0.0.0') ? __('All hosts') : $eachIp;
                $hostLink = wf_Link('?module=index&ip=' . $eachIp, $hostName);
                $cells = wf_TableCell($hostLink);
                $cells .= wf_TableCell(zb_convert_size($eachByteCount));
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_delimiter();
            $result .= wf_TableBody($rows, '100%', 0, '');
        } else {
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        die($result);
    }
} else {
    show_error(__('Access denied'));
}
