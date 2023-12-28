<?php

class OphanimDash
{

    /**
     * 
     * @var int
     */
    protected $cachingTimeout = 600;
    /**
     * 
     * @var object
     */
    protected $cache = '';
    /**
     * 
     * @var object
     */
    protected $traffStatDb = '';
     /**
     * 
     * @var object
     */
    protected $messages='';

    //some predefined stuff
    const PROUTE_IP = 'ip';
    const PROUTE_PERIOD = 'period';

    const KEY_IPLIST = 'IPLIST';
    const KEY_SYSINFO = 'SYSINFO';
    const KEY_TRAFPROTO = 'TRAFFPROTO_';

    public function __construct()
    {
        $this->initMessages();
        $this->initCache();
        $this->initDb();
    }

    protected function initCache()
    {
        $this->cache = new UbillingCache();
    }

    protected function initMessages() {
        $this->messages=new UbillingMessageHelper();
    }

    protected function initDb()
    {
        $this->traffStatDb = new NyanORM(OphanimHarvester::TABLE_TRAFFSTAT);
    }


    public function renderIpSelectForm()
    {
        $result = '';
        $ipsAvail = array();
        $ipsAvail = array();

        $ipsRaw = $this->cache->get(self::KEY_IPLIST, $this->cachingTimeout);
        if (empty($ipsRaw)) {
            $this->traffStatDb->selectable('ip');
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

        $availPeriods = array('day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'year' => __('Year'));
        $inputs = wf_SelectorSearchable(self::PROUTE_IP, $ipsAvail, 'IP', ubRouting::post(self::PROUTE_IP), false) . ' ';
        $inputs .= wf_Selector(self::PROUTE_PERIOD, $availPeriods, 'Period', ubRouting::post(self::PROUTE_PERIOD), false) . ' ';
        $inputs .= wf_Submit(__('Search'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    protected function loadTrafProtos($direction)
    {
        $trafTotals = array();
        $cachedData=$this->cache->get(self::KEY_TRAFPROTO.$direction,$this->cachingTimeout);

        if (empty($cachedData)) {
            
        if (file_exists(OphanimClassifier::LR_PATH . 'LR_' . $direction)) {
            $lrDown = file_get_contents(OphanimClassifier::LR_PATH . 'LR_R');
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
        $this->cache->set(self::KEY_TRAFPROTO.$direction,$trafTotals,$this->cachingTimeout);
    } else {
        $trafTotals=$cachedData;
    }
        return ($trafTotals);
    }

    public function renderIpGraphs($ip,$period) {
        $result='';
        $this->traffStatDb->where('ip', '=', $ip);
        $this->traffStatDb->where('year', '=', curyear());
        $this->traffStatDb->where('month', '=', date("m"));
        $traffData = $this->traffStatDb->getAll();
        if (!empty($traffData)) {
          $summary=$ip . ' '.__('Traffic summary').' -  '.__('Downloaded').': ' . zb_convert_size($traffData[0]['dl']) . ' '.__('Uploaded').': ' . zb_convert_size($traffData[0]['ul']);
          $result.=$this->messages->getStyledMessage($summary,'success');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'),'warning');
        }
        $result.=wf_delimiter();
      
        
        $result .= wf_img_sized('?module=graph&dir=R&period=' . $period . '&ip=' . $ip, '', '100%');
        $result .= wf_img_sized('?module=graph&dir=S&period=' . $period . '&ip=' . $ip, '', '100%');
        return($result);
    }


    public function renderTrafProtos()
    {
        $result = '';
        $downloadStats=$this->loadTrafProtos('R');
        //TODO
        return ($result);
    }


    public function renderSystemInfo()
    {
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
            $this->traffStatDb->where('month', '=', date("m"));
            $totalDownload = $this->traffStatDb->getFieldsSum('dl');

            $this->traffStatDb->where('year', '=', curyear());
            $this->traffStatDb->where('month', '=', date("m"));
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

        return ($result);
    }
}
