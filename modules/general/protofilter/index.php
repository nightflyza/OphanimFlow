<?php

if (cfr('ROOT')) {
    set_time_limit(0);
    $result = '';
    $form = '';

    $classifier = new OphanimClassifier();
    $baseStruct = $classifier->getBaseStruct();

    $direction = ubRouting::get('direction', 'vf');
    $protoFilter = ubRouting::get('proto', 'int');
    $depth = 0;
    if (ubRouting::checkPost('depth')) {
        $depth = ubRouting::post('depth', 'int');
    } else {
        if (ubRouting::checkGet('depth')) {
            $depth = ubRouting::get('depth', 'int');
        }
    }

    if (!$depth) {
        $depth = 3;
    }
    //search form anyway

    //download
    $form .= wf_tag('div', false, 'bgc-white p-20 bd') . __('Download') . wf_delimiter();
    foreach ($baseStruct as $protoId => $protoName) {
        $reportUrl = '?module=protofilter&direction=R&depth=' . $depth . '&proto=' . $protoId;
        $form .= wf_AjaxLink($reportUrl, $protoName, 'reportcontainer', false, 'btn btn-primary btn-color') . ' ';
    }
    $form .= wf_tag('div', true);

    $form .= wf_delimiter();
    $form .= wf_tag('div', false, 'bgc-white p-20 bd') . __('Upload') . wf_delimiter();
    //upload
    foreach ($baseStruct as $protoId => $protoName) {
        $reportUrl = '?module=protofilter&direction=S&depth=' . $depth . '&proto=' . $protoId;
        $form .= wf_AjaxLink($reportUrl, $protoName, 'reportcontainer', false, 'btn btn-secondary btn-color') . ' ';
    }
    $form .= wf_tag('div', true);


    $form .= wf_AjaxLoader();
    $form .= wf_AjaxContainer('reportcontainer', '', '');

    $depthsAvail = array(
        1 => '1 ' . __('hour'),
        3 => '3 ' . __('hours'),
        8 => '8 ' . __('hours'),
        24 => '24 ' . __('hours'),
        48 => '48 ' . __('hours'),
    );

    $form .= wf_delimiter();
    $inputs = wf_SelectorAC('depth', $depthsAvail, __('depth'), $depth, true);
    $form .= wf_Form('', 'POST', $inputs, '');

    show_window(__('Top by protocol') . ' (' . __('last') . ' ' . $depth . ' ' . __('hours') . ')', $form);

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
            $netDescFlag = false;
            $userDataFlag = false;
            if ($ubillingConfig->getAlterParam('CHARTS_NETDESC')) {
                $netLib = new OphanimNetLib(true);
                $netDescFlag = true;
            }

            if ($ubillingConfig->getAlterParam('UBILLING_URL') and $ubillingConfig->getAlterParam('UBILLING_API_KEY')) {
                $userDataFlag = true;
                $ubUserData = new UbUserData();
            }

            //rendering report here
            $protoDesc = strtoupper($baseStruct[$protoFilter]);
            $cells = wf_TableCell('#');
            $cells .= wf_TableCell(__('Host'));
            if ($netDescFlag) {
                $cells .= wf_TableCell(__('Network'));
            }

            if ($userDataFlag) {
                $cells .= wf_TableCell(__('Address'));
                $cells .= wf_TableCell(__('Real Name'));
                $cells .= wf_TableCell(__('Phones'));
            }
            $cells .= wf_TableCell($protoDesc . ' ' . __('traffic'));
            $rows = wf_TableRow($cells, 'row1');
            $i = 0;
            foreach ($ipByteCount as $eachIp => $eachByteCount) {
                $position = ($i) ? $i : '';
                $hostName = ($eachIp == '0.0.0.0') ? __('All hosts') : $eachIp;
                $hostLink = wf_Link('?module=index&ip=' . $eachIp, $hostName);
                $cells = wf_TableCell($position);
                $cells .= wf_TableCell($hostLink);
                if ($netDescFlag) {
                    $hostDesc = $netLib->getIpNetDescription($eachIp);
                    $cells .= wf_TableCell($hostDesc);
                }
                if ($userDataFlag) {
                    $userAddress = '-';
                    $userRealName = '-';
                    $userPhones = '-';
                    if ($eachIp != '0.0.0.0') {
                        $userData = $ubUserData->getUserData($eachIp);
                        if (!empty($userData)) {
                            $userAddress = $userData['fulladress'];
                            $userRealName = $userData['realname'];
                            $userPhones = $userData['phone'].' '.$userData['mobile'];
                        }
                    }
                    $cells .= wf_TableCell($userAddress);
                    $cells .= wf_TableCell($userRealName);
                    $cells .= wf_TableCell($userPhones);
                }
                $cells .= wf_TableCell(zb_convert_size($eachByteCount));
                $rows .= wf_TableRow($cells, 'row5');
                $i++;
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
