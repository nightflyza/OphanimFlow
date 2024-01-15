<?php

/**
 * Draft dashboard implementation
 */
class OphanimDash {

  /**
   * Contains default caching timeout
   * 
   * @var int
   */
  protected $cachingTimeout = 600;

  /**
   * System caching object instance
   * 
   * @var object
   */

  protected $cache = '';

  /**
   * traffic stats database abstraction layer 
   * 
   * @var object
   */
  protected $traffStatDb = '';

  /**
   * system messages helper placeholder
   * 
   * @var object
   */
  protected $messages = '';

  //some predefined stuff
  const PROUTE_IP = 'ip';
  const PROUTE_PERIOD = 'period';
  const URL_ME = '?module=index';

  const KEY_IPLIST = 'IPLIST';
  const KEY_SYSINFO = 'SYSINFO';
  const KEY_TRAFPROTO = 'TRAFFPROTO_';

  public function __construct() {
    $this->initMessages();
    $this->initCache();
    $this->initDb();
  }

  /**
   * Inits caching object instance
   *
   * @return void
   */
  protected function initCache() {
    $this->cache = new UbillingCache();
  }

  /**
   * Inits system messages helper
   *
   * @return void
   */
  protected function initMessages() {
    $this->messages = new UbillingMessageHelper();
  }

  /**
   * Inits traffic stats database abstraction layer
   *
   * @return void
   */
  protected function initDb() {
    $this->traffStatDb = new NyanORM(OphanimHarvester::TABLE_TRAFFSTAT);
  }


  /**
   * Renders IP selection form
   *
   * @param string $ip
   * @param string $period
   * 
   * @return string
   */
  public function renderIpSelectForm($ip = '', $period = '') {
    $result = '';
    $ipsAvail = array();
    $ipsAvail['0.0.0.0'] = __('All hosts');

    $ipsRaw = $this->cache->get(self::KEY_IPLIST, $this->cachingTimeout);
    if (empty($ipsRaw)) {
      $this->traffStatDb->selectable('ip');
      $this->traffStatDb->where('year', '=', date("Y"));
      $this->traffStatDb->where('month', '=', date("n"));
      $this->traffStatDb->orderBy('dl', 'DESC');
      $ipsRaw = $this->traffStatDb->getAll();
      $this->traffStatDb->selectable();
      $this->cache->set(self::KEY_IPLIST, $ipsRaw, $this->cachingTimeout);
    }
    if (!empty($ipsRaw)) {
      foreach ($ipsRaw as $io => $each) {
        $ipsAvail[$each['ip']] = $each['ip'];
      }
    }

    $availPeriods = array('hour' => __('Hour'), 'day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'year' => __('Year'));
    $inputs = wf_SelectorSearchable(self::PROUTE_IP, $ipsAvail, 'IP', $ip, false) . ' ';
    $inputs .= wf_SelectorSearchable(self::PROUTE_PERIOD, $availPeriods, 'Period', $period, false) . ' ';
    $inputs .= wf_Submit(__('Search'), '', 'class="btn btn-primary btn-color"');
    $result .= wf_Form(self::URL_ME, 'POST', $inputs, 'glamour');
    return ($result);
  }

  /**
   * Loads and returns traffic totals for some direction as proto=>counters
   *
   * @param string $direction
   * 
   * @return array
   */
  protected function loadTrafProtos($direction) {
    $trafTotals = array();
    $cachedData = $this->cache->get(self::KEY_TRAFPROTO . $direction, $this->cachingTimeout);

    if (empty($cachedData)) {

      if (file_exists(OphanimClassifier::LR_PATH . 'LR_' . $direction)) {
        $lrDown = file_get_contents(OphanimClassifier::LR_PATH . 'LR_' . $direction);
        $lrDown = json_decode($lrDown, true);

        $classifier = new OphanimClassifier();
        $baseStruct = $classifier->getBaseStruct();

        foreach ($baseStruct as $io => $each) {
          $trafTotals[$each] = 0;
        }


        if (!empty($lrDown)) {
          foreach ($lrDown as $eachIp => $eachTs) {
            if (!empty($eachTs)) {
              foreach ($eachTs as $eachTimestamp => $eachBytes) {
                if (!empty($eachBytes)) {
                  foreach ($eachBytes as $eachProto => $eachCounters) {
                    if ($eachProto != 'time') {
                      $trafTotals[$eachProto] += $eachCounters;
                    }
                  }
                }
              }
            }
          }
        }
      }
      $this->cache->set(self::KEY_TRAFPROTO . $direction, $trafTotals, $this->cachingTimeout);
    } else {
      $trafTotals = $cachedData;
    }
    return ($trafTotals);
  }

  /**
   * Renders chart in both direction for some IP
   *
   * @param string $ip
   * @param string $period
   * 
   * @return string
   */
  public function renderIpGraphs($ip, $period) {
    $result = '';
    if ($ip != '0.0.0.0') {
      $this->traffStatDb->where('ip', '=', $ip);
      $this->traffStatDb->where('year', '=', curyear());
      $this->traffStatDb->where('month', '=', date("n"));
      $traffData = $this->traffStatDb->getAll();
      if (!empty($traffData)) {
        $summary = $ip . ' ' . __('Traffic summary') . ' -  ' . __('Downloaded') . ': ' . zb_convert_size($traffData[0]['dl']) . ' ' . __('Uploaded') . ': ' . zb_convert_size($traffData[0]['ul']);
        $result .= $this->messages->getStyledMessage($summary, 'success');
      } else {
        $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
      }
      $result .= wf_delimiter();
    }

    $result .= wf_img_sized('?module=graph&dir=R&period=' . $period . '&ip=' . $ip, '', '100%');
    $result .= wf_img_sized('?module=graph&dir=S&period=' . $period . '&ip=' . $ip, '', '100%');
    return ($result);
  }

  /**
   * Converts bytes counters into mbit/s
   *
   * @param int $bytes
   * 
   * @return float
   */
  protected function bytesToSpeed($bytes) {
    $result = 0;
    if (!is_numeric($bytes)) {
      $bytes = trim($bytes);
    }
    if ($bytes != 0) {
      $result = ($bytes * 8) / 300 / 1048576; //mbits per 5 minutes
      //$result=$result/1024; //in gbits
    }

    return ($result);
  }

  /**
   * Renders system info panel
   *
   * @return string
   */
  public function renderSystemInfo() {
    $result = '';
    $sysInfoCached = $this->cache->get(self::KEY_SYSINFO, $this->cachingTimeout);

    $loadAvg = sys_getloadavg();
    $loadAvgValue = round($loadAvg[0], 2) * 100;

    $totalSpace = disk_total_space('/');
    $freeSpace = disk_free_space('/');
    $diskPercent = zb_PercentValue($totalSpace, ($totalSpace - $freeSpace));

    if (empty($sysInfoCached)) {
      $sysInfoCached = array();
      $this->traffStatDb->where('year', '=', curyear());
      $this->traffStatDb->where('month', '=', date("n"));
      $totalDownload = $this->traffStatDb->getFieldsSum('dl');

      $this->traffStatDb->where('year', '=', curyear());
      $this->traffStatDb->where('month', '=', date("n"));
      $totalUpload = $this->traffStatDb->getFieldsSum('ul');

      $totalTraffic = $totalDownload + $totalUpload;
      $downloadValue = zb_PercentValue($totalTraffic, $totalDownload);
      $uploadValue = zb_PercentValue($totalTraffic, $totalUpload);


      $sysInfoCached['la'] = $loadAvg;
      $sysInfoCached['fs'] = $freeSpace;
      $sysInfoCached['dl'] = $downloadValue;
      $sysInfoCached['ul'] = $uploadValue;
      $this->cache->set(self::KEY_SYSINFO, $sysInfoCached, $this->cachingTimeout);
    }

    $downloadValue = $sysInfoCached['dl'];
    $uploadValue = $sysInfoCached['ul'];


    $result .= '
          <div class="masonry-item col-md-12">
          <div class="bgc-white p-20 bd">
            <div class="mT-30">
              <div class="peers mT-20 fxw-nw@lg+ jc-sb ta-c gap-10">
                <div class="peer">
                  <div class="easy-pie-chart" data-size="100" data-percent="' . $loadAvgValue . '" data-bar-color="#f44336">
                    <span></span>
                  </div>
                  <h6 class="fsz-sm">' . __('System load') . '</h6>
                </div>
                <div class="peer">
                  <div class="easy-pie-chart" data-size="100" data-percent="' . $diskPercent . '" data-bar-color="#2196f3">
                    <span></span>
                  </div>
                  <h6 class="fsz-sm">' . __('Disk') . '</h6>
                </div>
                <div class="peer">
                  <div class="easy-pie-chart" data-size="100" data-percent="' . $downloadValue . '" data-bar-color="#f44336">
                    <span></span>
                  </div>
                  <h6 class="fsz-sm">' . __('Download ratio') . '</h6>
                </div>
                <div class="peer">
                  <div class="easy-pie-chart" data-size="100" data-percent="' . $uploadValue . '" data-bar-color="#ff9800">
                    <span></span>
                  </div>
                  <h6 class="fsz-sm">' . __('Upload ratio') . '</h6>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
      ';

    return ($result);
  }

  /**
   * Renders traffic summary protos stats
   *
   * @return void
   */
  public function renderTrafProtos() {
    $result = '';
    $downloadStats = $this->loadTrafProtos('R');
    $uploadStats = $this->loadTrafProtos('S');

    if (!empty($downloadStats) and !empty($uploadStats)) {
      $labelsR = '';
      $dataR = '';
      $dataS = '';
      $labelsTotal = '';
      $totalR = '';
      $totalS = '';
      $ipDlTop = '';
      $topHosts = 15;
      $ignoredProto = array('total', 'tcp', 'udp', 'web', 'quic');
      $ignoredProto = array_flip($ignoredProto);

      foreach ($downloadStats as $proto => $bytes) {
        if (!isset($ignoredProto[$proto])) {
          $labelsR .= "'" . $proto . "',";
          $dataR .= $this->bytesToSpeed($bytes) . ',';
        } else {
          $totalR .= $this->bytesToSpeed($bytes) . ',';
          $labelsTotal .= "'" . $proto . "',";
        }
      }

      foreach ($uploadStats as $proto => $bytes) {
        if (!isset($ignoredProto[$proto])) {
          $dataS .= $this->bytesToSpeed($bytes) . ',';
        } else {
          $totalS .= $this->bytesToSpeed($bytes) . ',';
        }
      }

      $ipListRaw = $this->cache->get(self::KEY_IPLIST, $this->cachingTimeout);
      if (!empty($ipListRaw)) {
        $i = 1;
        foreach ($ipListRaw as $io => $each) {
          if ($i <= $topHosts) {
            $ipLink = wf_Link(self::URL_ME . '&' . self::PROUTE_IP . '=' . $each['ip'], $each['ip']);
            $ipDlTop .= '<small class="fw-600 c-grey-700">' . $ipLink . '</small><br>';
            $i++;
          }
        }
      }

      $script = "
            <script src='modules/jsc/chart.js'></script> 
            <script>  
            const ctxt = document.getElementById('total-chart');
              new Chart(ctxt, {
                type: 'bar',
                data: {
                  labels: [" . $labelsTotal . "],

                  datasets: [{
                    label: '" . __('Download') . "',
                    borderWidth: 1,
                    data: [ " . $totalR . " ]
                  }, {
                    label: '" . __('Upload') . "',
                    borderWidth: 1,
                    data: [ " . $totalS . " ]
                  }]
                },
                options: {
                  responsive: true,
                  legend: {
                    position: 'bottom'
                  }
                 
                }
              });

             const ctx = document.getElementById('proto-chart');
              new Chart(ctx, {
                type: 'bar',
                data: {
                  labels: [" . $labelsR . "],

                  datasets: [{
                    label: '" . __('Download') . "',
                    borderWidth: 1,
                    data: [ " . $dataR . " ]
                  }, {
                    label: '" . __('Upload') . "',
                    borderWidth: 1,
                    data: [ " . $dataS . " ]
                  }]
                },
                options: {
                  responsive: true,
                  legend: {
                    position: 'bottom'
                  }
                 
                }
              });


            </script>
            ";

      $result .= '  <div class="row gap-20 masonry pos-r">';
      $result .= '<div class="masonry-sizer col-md-4 pos-a"></div>';
      $result .= '<div class="masonry-item col-md-4"> 
            <canvas id="total-chart" height="220"></canvas> 
            </div>';

      $result .= '<div class="masonry-sizer col-md-4 pos-a"></div>';
      $result .= '<div class="masonry-item col-md-4"> 
            <canvas id="proto-chart" height="220"></canvas> 
            </div>';

      $result .= '<div class="masonry-sizer col-md-4 pos-a"></div>';
      $result .= '<div class="masonry-item col-md-4"> 
            
            <h5 class="mB-5">Top hosts:</h5>
            <div class="layer w-100">
             ' . $ipDlTop . '
             </div>
            </div>';

      $result .= '</div>';
      $result .= $script;
    }
    return ($result);
  }
}
