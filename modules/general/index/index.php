<?php

$ip = '';
$period = 'day';
if (ubRouting::checkPost('ip')) {
    $ip = ubRouting::post('ip');
}

if (ubRouting::checkPost('period')) {
    $period = ubRouting::post('period');
}

$ipsAvail = array();
$cache = new UbillingCache();
$ipsRaw = $cache->get('IPLIST', 600);
if (empty($ipsRaw)) {
    $ipsRaw = simple_queryall("select ip from traffstat order by dl DESC");
    $cache->set('IPLIST', $ipsRaw, 600);
}
if (!empty($ipsRaw)) {
    foreach ($ipsRaw as $io => $each) {
        $ipsAvail[$each['ip']] = $each['ip'];
    }
}

$availPeriods = array('day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'year' => __('Year'));
$inputs = wf_SelectorSearchable('ip', $ipsAvail, 'IP', $ip, false) . ' ';
$inputs .= wf_Selector('period', $availPeriods, 'Period', $period) . ' ';
$inputs .= wf_Submit(__('Search'));

//per IP search form
show_window(__('Per-IP data'), wf_Form('', 'POST', $inputs, 'glamour'));

//basic dashboard

if (!$ip) {
    //dashboard
    $result = '';
    $loadAvg = sys_getloadavg();
    $loadAvgValue = round($loadAvg[0], 2) * 100;

    $totalSpace = disk_total_space('/');
    $freeSpace = disk_free_space('/');
    $diskPercent = zb_PercentValue($totalSpace, ($totalSpace - $freeSpace));

    $traffStatDb = new NyanORM(OphanimHarvester::TABLE_TRAFFSTAT);
    $traffStatDb->where('year', '=', curyear());
    $traffStatDb->where('month', '=', date("m"));
    $totalDownload = $traffStatDb->getFieldsSum('dl');

    $traffStatDb->where('year', '=', curyear());
    $traffStatDb->where('month', '=', date("m"));
    $totalUpload = $traffStatDb->getFieldsSum('ul');

    $totalTraffic = $totalDownload + $totalUpload;
    $downloadValue = zb_PercentValue($totalTraffic, $totalDownload);
    $uploadValue = zb_PercentValue($totalTraffic, $totalUpload);

    $result .= '
    
    <div class="masonry-item col-md-12">
    <div class="bgc-white p-20 bd">
      <div class="mT-30">
        <div class="peers mT-20 fxw-nw@lg+ jc-sb ta-c gap-10">
          <div class="peer">
            <div class="easy-pie-chart" data-size="100" data-percent="' . $loadAvgValue . '" data-bar-color="#f44336">
              <span></span>
            </div>
            <h6 class="fsz-sm">CPU</h6>
          </div>
          <div class="peer">
            <div class="easy-pie-chart" data-size="100" data-percent="' . $diskPercent . '" data-bar-color="#2196f3">
              <span></span>
            </div>
            <h6 class="fsz-sm">Disk</h6>
          </div>
          <div class="peer">
            <div class="easy-pie-chart" data-size="100" data-percent="' . $downloadValue . '" data-bar-color="#f44336">
              <span></span>
            </div>
            <h6 class="fsz-sm">Download ratio</h6>
          </div>
          <div class="peer">
            <div class="easy-pie-chart" data-size="100" data-percent="' . $uploadValue . '" data-bar-color="#ff9800">
              <span></span>
            </div>
            <h6 class="fsz-sm">Upload ratio</h6>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
        ';



    show_window(__('System info'), $result);
}

if ($ip) {
    $traffStatDb = new NyanORM(OphanimHarvester::TABLE_TRAFFSTAT);
    $traffStatDb->where('ip', '=', $ip);
    $traffData = $traffStatDb->getAll();
    if (!empty($traffData)) {
        show_success($ip . ' Traffic summary - Downloaded: ' . $traffData[0]['dl'] . ' bytes Uploaded: ' . $traffData[0]['ul'] . ' bytes');
    } else {
        show_warning(__('Nothing to show'));
    }

    $result = '';
    $result .= wf_img_sized('?module=graph&dir=R&period=' . $period . '&ip=' . $ip, '', '100%');
    $result .= wf_img_sized('?module=graph&dir=S&period=' . $period . '&ip=' . $ip, '', '100%');
    show_window('Charts', $result);
}
