<?php


class OphanimMgr
{

    /**
     * 
     * @var array
     */
    protected $altCfg = array();

    /**
     * 
     * @var object
     */
    protected $networksDb = '';

    /**
     * 
     * @var object
     */
    protected $messages = '';

    /**
     * 
     * @var array
     */
    protected $allNetworks = array();

    /**
     * 
     * @var int
     */
    protected $netsCount = 0;

    /**
     * 
     * @var int
     */
    protected $samplingRate = 100;

    /**
     * 
     * @var int
     */
    protected $port = 42112;

    //some predefined stuff here
    const CONF_PATH = '/etc/of.conf';
    const PRETAG_PATH = '/etc/pretag.map';
    const TEMPLATE_PATH = 'dist/collector/of.template';
    const PID_PATH = '/var/run/nfacctd.pid';
    const NFT_PATH = '/etc/netflow_templates';
    const TABLE_NETWORKS = 'networks';


    //and some routes
    const URL_ME = '?module=settings';
    const PROUTE_NETW_CREATE = 'newnetwork';
    const ROUTE_NETW_DEL = 'deletenetwork';
    const ROUTE_START='startcollector';
    const ROUTE_STOP='stopcollector';
    const ROUTE_RECONF='rebuildconfig';

    public function __construct()
    {
        $this->initMessages();
        $this->loadConfigs();
        $this->initNetsDb();
        $this->loadNetworks();
    }

    protected function initNetsDb()
    {
        $this->networksDb = new NyanORM(self::TABLE_NETWORKS);
    }

    protected function initMessages()
    {
        $this->messages = new UbillingMessageHelper();
    }

    protected function loadConfigs()
    {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->port=$this->altCfg['COLLECTOR_PORT'];
    }


    protected function loadNetworks()
    {
        $this->allNetworks = $this->networksDb->getAll('id');
        $this->netsCount = sizeof($this->allNetworks);
    }

    public function renderNetworksList()
    {
        $result = '';
        if (!empty($this->allNetworks)) {
            $cells = wf_TableCell(__('Network'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'table-light');
            foreach ($this->allNetworks as $io => $each) {
                $cells = wf_TableCell($each['network']);
                $actLinks = wf_JSAlertStyled(self::URL_ME . '&' . self::ROUTE_NETW_DEL . '=' . $each['id'], __('Delete'), __('Are you serious') . '?', 'btn cur-p btn-danger btn-color');
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, '');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    public function renderNetworkCreateForm()
    {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_NETW_CREATE, __('Network') . '/CIDR', '', false, '20', 'net-cidr') . ' ';
        $inputs .= wf_Submit(__('Create new'), '', 'class="btn btn-primary btn-color"');
        $result .= wf_delimiter();
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }


    public function isNetworkIdExists($networkId)
    {
        $result = false;
        if (isset($this->allNetworks[$networkId])) {
            $result = true;
        }
        return ($result);
    }

    public function isNetworkExists($network)
    {
        $result = false;
        if (!empty($this->allNetworks)) {
            foreach ($this->allNetworks as $io => $each) {
                if ($each['network'] == $network) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    public function createNetwork($network)
    {
        $netF = ubRouting::filters($network, 'mres');
        if (!$this->isNetworkExists($network)) {
            $this->networksDb->data('network', $netF);
            $this->networksDb->create();
        }
    }

    public function deleteNetwork($networkId)
    {
        $networkId = ubRouting::filters($networkId, 'int');
        if ($this->isNetworkIdExists($networkId)) {
            $this->networksDb->where('id', '=', $networkId);
            $this->networksDb->delete();
        }
    }

    public function generatePretagMap()
    {
        $result = '';
        if (!empty($this->allNetworks)) {
            $srcId = 1;
            foreach ($this->allNetworks as $io => $each) {
                $result .= "id=" . $srcId . " filter='src net " . $each['network'] . "'" . PHP_EOL;
                $srcId++;
            }

            $dstId = $this->netsCount + 1;
            foreach ($this->allNetworks as $io => $each) {
                $result .= "id=" . $dstId . " filter='dst net " . $each['network'] . "'" . PHP_EOL;
                $dstId++;
            }
        }
        return ($result);
    }

    public function generateConfig()
    {
        $result = '';
        if (!empty($this->allNetworks)) {
            $dbConfig = rcms_parse_ini_file('config/mysql.ini');
            $template = file_get_contents(self::TEMPLATE_PATH);
            $result = $template;
            $srcLo = 1;
            $srcHi = $this->netsCount;
            $dstLo = $this->netsCount + 1;
            $dstHi = $this->netsCount + $this->netsCount;
            $result = str_replace('{PORT}', $this->port, $result);
            $result = str_replace('{NETFLOW_TEMPLATES_PATH}', self::NFT_PATH, $result);
            $result = str_replace('{SAMPLING_RATE}', $this->samplingRate, $result);
            $result = str_replace('{PRETAG_PATH}', self::PRETAG_PATH, $result);
            $result = str_replace('{PID_PATH}', self::PID_PATH, $result);
            $result = str_replace('{SRC_LO}', $srcLo, $result);
            $result = str_replace('{SRC_HI}', $srcHi, $result);
            $result = str_replace('{DST_LO}', $dstLo, $result);
            $result = str_replace('{DST_HI}', $dstHi, $result);
            $result = str_replace('{MYSQLUSER}', $dbConfig['username'], $result);
            $result = str_replace('{MYSQLPASSWORD}', $dbConfig['password'], $result);
        }
        return ($result);
    }

    public function renderCollectorControls()
    {
        $result = '';
        if (file_exists(self::PID_PATH)) {
            $result.=$this->messages->getStyledMessage(__('Netflow collector is running at port').' :'.$this->port,'success');
        } else {
            $result.=$this->messages->getStyledMessage(__('Netflow collector stopped'),'warning');
            
        }
        $result.=wf_delimiter();
        return ($result);
    }
}
