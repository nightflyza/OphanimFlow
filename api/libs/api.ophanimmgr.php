<?php

/**
 * Performs basic collector config and execution management
 */
class OphanimMgr {

    /**
     * Contains alter config as key=>value
     * 
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains tracked networks database abstraction layer
     * 
     * @var object
     */
    protected $networksDb = '';

    /**
     * Contains system messages helper instance
     * 
     * @var object
     */
    protected $messages = '';

    /**
     * Contains preloaded tracking networks list as id=>id/network
     * 
     * @var array
     */
    protected $allNetworks = array();

    /**
     * Count of tracked networks
     * 
     * @var int
     */
    protected $netsCount = 0;

    /**
     * Contains default sampling rate
     * 
     * @var int
     */
    protected $samplingRate = 100;

    /**
     * Contains default collector port
     * 
     * @var int
     */
    protected $port = 42112;

    /**
     * Contains default sFlow collector port
     * 
     * @var int
     */
    protected $sflowPort = 6343;

    /**
     * Contains networks table structure as field=>index
     *
     * @var array
     */
    protected $networksStruct = array();

    //some predefined stuff here
    const DB_PATCHES_PATH = 'dist/dumps/patches/';
    const DB_PATCHES_EXT = '.sql';
    const CONF_PATH = '/etc/of.conf';
    const SFLOW_CONF_PATH = '/etc/sof.conf';
    const PRETAG_PATH = '/etc/pretag.map';
    const TEMPLATE_PATH = 'dist/collector/of.template';
    const SFLOW_TEMPLATE_PATH = 'dist/collector/sof.template';
    const PID_PATH = '/var/run/nfacctd.pid';
    const SFLOW_PID_PATH = '/var/run/sfacctd.pid';
    const NFT_PATH = '/etc/netflow_templates';
    const TABLE_NETWORKS = 'networks';


    //and some routes
    const URL_ME = '?module=settings';
    const PROUTE_NETW_CREATE = 'newnetwork';
    const PROUTE_NETW_DESC = 'newnetworkdescr';
    const ROUTE_NETW_DEL = 'deletenetwork';
    const ROUTE_START = 'startcollector';
    const ROUTE_STOP = 'stopcollector';
    const ROUTE_RECONF = 'rebuildconfig';

    public function __construct($checkDatabaseStruct = false) {
        $this->initMessages();
        $this->loadConfigs();
        $this->initNetsDb();
        if ($checkDatabaseStruct) {
            $this->loadNetStruct();
        }
        $this->loadNetworks();
    }

    /**
     * Inits networks database abstraction layer
     *
     * @return void
     */
    protected function initNetsDb() {
        $this->networksDb = new NyanORM(self::TABLE_NETWORKS);
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
     * Loads required configs and sets some properties
     *
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->port = $this->altCfg['COLLECTOR_PORT'];
        $this->samplingRate = $this->altCfg['SAMPLING_RATE'];
        if (isset($this->altCfg['SFLOW_PORT'])) {
            $this->sflowPort = $this->altCfg['SFLOW_PORT'];
        }
    }

    /**
     * Loads tracking networks data from database
     *
     * @return void
     */
    protected function loadNetworks() {
        $this->allNetworks = $this->networksDb->getAll('id');
        $this->netsCount = sizeof($this->allNetworks);
    }

    /**
     * Loads networks database struct and applies some patches if required
     *
     * @return void
     */
    protected function loadNetStruct() {
        $patchesApplied = false;
        $structTmp = $this->networksDb->getTableStructure(true);
        $structTmp = array_flip($structTmp);
        $this->networksStruct = $structTmp;

        //0.0.2 patch
        if (!isset($this->networksStruct['descr'])) {
            debarr($this->networksStruct);
            $this->applyDbPatch('0.0.2');
            $patchesApplied = true;
        }

        //viewport refresh
        if ($patchesApplied) {
            ubRouting::nav(self::URL_ME);
        }
    }

    /**
     * Apllies database patch by its name
     *
     * @param type $patchName
     * 
     * @return void
     */
    protected function applyDbPatch($patchName) {
        if (!empty($patchName)) {
            $patchPath = self::DB_PATCHES_PATH . $patchName . self::DB_PATCHES_EXT;
            if (file_exists($patchPath)) {
                $patchContent = file_get_contents($patchPath);
                if (!empty($patchContent)) {
                    $patchContent = explode(';', $patchContent);
                    if (!empty($patchContent)) {
                        foreach ($patchContent as $io => $eachQuery) {
                            $eachQuery = trim($eachQuery);
                            if (!empty($eachQuery)) {
                                nr_query($eachQuery);
                                show_success(__('DB patch') . $patchName . ': ' . $eachQuery);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders available networks list
     *
     * @return string
     */
    public function renderNetworksList() {
        $result = '';

        if (!empty($this->allNetworks)) {
            $cells = wf_TableCell(__('Network'));
            if (isset($this->networksStruct['descr'])) {
                $cells .= wf_TableCell(__('Description'));
            }
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'table-light');
            foreach ($this->allNetworks as $io => $each) {
                $cells = wf_TableCell($each['network']);
                if (isset($this->networksStruct['descr'])) {
                    $cells .= wf_TableCell($each['descr']);
                }
                $delUrl = self::URL_ME . '&' . self::ROUTE_NETW_DEL . '=' . $each['id'];
                $actLinks = wf_JSAlertStyled($delUrl, __('Delete'), __('Are you serious') . '?', 'btn cur-p btn-danger btn-color');
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, '');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders new network creation form
     *
     * @return string
     */
    public function renderNetworkCreateForm() {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_NETW_CREATE, __('Network') . '/CIDR', '', false, '20', 'net-cidr') . ' ';
        $inputs .= wf_TextInput(self::PROUTE_NETW_DESC, __('Description'), '', false, '20', '') . ' ';
        $inputs .= wf_Submit(__('Create new'), '', 'class="btn btn-primary btn-color"');
        $result .= wf_delimiter();
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }


    /**
     * Checks is some network exists by its ID
     *
     * @param int $networkId
     * 
     * @return bool
     */
    public function isNetworkIdExists($networkId) {
        $result = false;
        if (isset($this->allNetworks[$networkId])) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Check is some network exists by its CIDR
     *
     * @param string $network
     * 
     * @return bool
     */
    public function isNetworkExists($network) {
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

    /**
     * Creates new network database record
     *
     * @param string $network
     * @param string $descr
     * 
     * @return void
     */
    public function createNetwork($network, $descr = '') {
        $netF = ubRouting::filters($network, 'mres');
        $descrF = ubRouting::filters($descr, 'mres');
        if (!$this->isNetworkExists($network)) {
            $this->networksDb->data('network', $netF);
            $this->networksDb->data('descr', $descrF);
            $this->networksDb->create();
        }
    }

    /**
     * Deletes some network from database
     *
     * @param int $networkId
     * 
     * @return void
     */
    public function deleteNetwork($networkId) {
        $networkId = ubRouting::filters($networkId, 'int');
        if ($this->isNetworkIdExists($networkId)) {
            $this->networksDb->where('id', '=', $networkId);
            $this->networksDb->delete();
        }
    }

    /**
     * Returns pretag map for existing networks
     *
     * @return string
     */
    public function generatePretagMap() {
        $result = '';
        $vlansFlag = false;
        if (isset($this->altCfg['CONSIDER_VLANS'])) {
            if ($this->altCfg['CONSIDER_VLANS']) {
                $vlansFlag = true;
            }
        }

        if (!empty($this->allNetworks)) {
            $srcId = 1;
            foreach ($this->allNetworks as $io => $each) {
                $result .= "id=" . $srcId . " filter='src net " . $each['network'] . "'" . PHP_EOL;
                if ($vlansFlag) {
                    $result .= "id=" . $srcId . " filter='vlan and src net " . $each['network'] . "'" . PHP_EOL;
                }
                $srcId++;
            }

            $dstId = $this->netsCount + 1;
            foreach ($this->allNetworks as $io => $each) {
                $result .= "id=" . $dstId . " filter='dst net " . $each['network'] . "'" . PHP_EOL;
                if ($vlansFlag) {
                    $result .= "id=" . $dstId . " filter='vlan and dst net " . $each['network'] . "'" . PHP_EOL;
                }
                $dstId++;
            }
        }

        return ($result);
    }

    /**
     * Generates collector config
     *
     * @return string
     */
    public function generateConfig() {
        $result = '';
        if (!empty($this->allNetworks)) {
            $dbConfig = rcms_parse_ini_file('config/mysql.ini');
            $template = file_get_contents(self::TEMPLATE_PATH);
            $result = $template;

            if ($this->netsCount == 1) {
                $srcRange = 1;
                $dstRange = 2;
            } else {
                $srcLo = 1;
                $srcHi = $this->netsCount;
                $dstLo = $this->netsCount + 1;
                $dstHi = $this->netsCount + $this->netsCount;
                $srcRange = $srcLo . '-' . $srcHi;
                $dstRange = $dstLo . '-' . $dstHi;
            }

            $result = str_replace('{PORT}', $this->port, $result);
            $result = str_replace('{NETFLOW_TEMPLATES_PATH}', self::NFT_PATH, $result);
            $result = str_replace('{SAMPLING_RATE}', $this->samplingRate, $result);
            $result = str_replace('{PRETAG_PATH}', self::PRETAG_PATH, $result);
            $result = str_replace('{PID_PATH}', self::PID_PATH, $result);
            $result = str_replace('{SRC_RANGE}', $srcRange, $result);
            $result = str_replace('{DST_RANGE}', $dstRange, $result);
            $result = str_replace('{MYSQLUSER}', $dbConfig['username'], $result);
            $result = str_replace('{MYSQLPASSWORD}', $dbConfig['password'], $result);
        }
        return ($result);
    }

    /**
     * Generates sFlow collector config
     *
     * @return string
     */
    public function generateSflowConfig() {
        $result = '';
        if (!empty($this->allNetworks)) {
            $dbConfig = rcms_parse_ini_file('config/mysql.ini');
            $template = file_get_contents(self::SFLOW_TEMPLATE_PATH);
            $result = $template;

            if ($this->netsCount == 1) {
                $srcRange = 1;
                $dstRange = 2;
            } else {
                $srcLo = 1;
                $srcHi = $this->netsCount;
                $dstLo = $this->netsCount + 1;
                $dstHi = $this->netsCount + $this->netsCount;
                $srcRange = $srcLo . '-' . $srcHi;
                $dstRange = $dstLo . '-' . $dstHi;
            }

            $result = str_replace('{PORT}', $this->sflowPort, $result);
            $result = str_replace('{SAMPLING_RATE}', $this->samplingRate, $result);
            $result = str_replace('{PRETAG_PATH}', self::PRETAG_PATH, $result);
            $result = str_replace('{PID_PATH}', self::SFLOW_PID_PATH, $result);
            $result = str_replace('{SRC_RANGE}', $srcRange, $result);
            $result = str_replace('{DST_RANGE}', $dstRange, $result);
            $result = str_replace('{MYSQLUSER}', $dbConfig['username'], $result);
            $result = str_replace('{MYSQLPASSWORD}', $dbConfig['password'], $result);
        }
        return ($result);
    }

    /**
     * Checks for running collector process
     *
     * @return bool
     */
    public function isCollectorRunning() {
        $result = false;
        if (file_exists(self::PID_PATH)) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Checks for running sFlow collector process
     *
     * @return bool
     */
    public function isSflowCollectorRunning() {
        $result = false;
        if (file_exists(self::SFLOW_PID_PATH)) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Renders collector process conrols depends on it state
     *
     * @return string
     */
    public function renderCollectorControls() {
        $result = '';
        $netflowRunning = $this->isCollectorRunning();
        $sflowRunning = $this->isSflowCollectorRunning();
        
        if ($netflowRunning) {
            $collectorLabel = '';
            $collectorLabel .= __('Netflow collector is running at port') . ' ' . $this->port . ', ' . __('sampling rate') . ': ' . $this->samplingRate;
            $result .= $this->messages->getStyledMessage($collectorLabel, 'success');
        } else {
            $result .= $this->messages->getStyledMessage(__('Netflow collector is stopped'), 'warning');
        }
        
        if ($sflowRunning) {
            $sflowLabel = '';
            $sflowLabel .= __('sFlow collector is running at port') . ' ' . $this->sflowPort . ', ' . __('sampling rate') . ': ' . $this->samplingRate;
            $result .= $this->messages->getStyledMessage($sflowLabel, 'success');
        } else {
            $result .= $this->messages->getStyledMessage(__('sFlow collector is stopped'), 'warning');
        }
        
        $result .= wf_delimiter();
        
        if ($netflowRunning or $sflowRunning) {
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_STOP . '=true', __('Stop collectors'), false, 'btn cur-p btn-danger btn-color');
        } else {
            if (!empty($this->allNetworks)) {
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_RECONF . '=true', __('Rebuild configuration'), false, 'btn cur-p btn-dark btn-color') . ' ';
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_START . '=true', __('Start collectors'), false, 'btn cur-p btn-success btn-color');
            }
        }
        $result .= wf_delimiter();
        return ($result);
    }


    /**
     * Rebuilds pretag map and collector config
     *
     * @return void/string 
     */
    public function rebuildConfigs() {
        $result = '';
        if (!$this->isCollectorRunning() and !$this->isSflowCollectorRunning()) {
            if (file_exists(self::CONF_PATH) and file_exists(self::PRETAG_PATH)) {
                if (is_writable(self::CONF_PATH) and is_writable(self::PRETAG_PATH)) {
                    if (!empty($this->allNetworks)) {
                        $pretagMap = $this->generatePretagMap();
                        $mainConf = $this->generateConfig();
                        $sflowConf = $this->generateSflowConfig();
                        file_put_contents(self::PRETAG_PATH, $pretagMap);
                        file_put_contents(self::CONF_PATH, $mainConf);
                        if (!file_exists(self::SFLOW_CONF_PATH) or is_writable(self::SFLOW_CONF_PATH)) {
                            file_put_contents(self::SFLOW_CONF_PATH, $sflowConf);
                        } else {
                            $result .= self::SFLOW_CONF_PATH . ' ' . __('is not writable');
                        }
                    } else {
                        $result .= __('Networks list is empty');
                    }
                } else {
                    $result .= self::CONF_PATH . ' ' . __('or') . ' ' . self::PRETAG_PATH . ' ' . __('is not writable');
                }
            } else {
                $result .= self::CONF_PATH . ' ' . __('or') . ' ' . self::PRETAG_PATH . ' ' . __('config files not exists');
            }
        } else {
            $result .= __('Collector is running now');
        }
        return ($result);
    }


    /**
     * Starts new collector process
     *
     * @return void/string
     */
    public function startCollector() {
        $result = '';
        if (!$this->isCollectorRunning()) {
            $command = $this->altCfg['SUDO_PATH'] . ' ' . $this->altCfg['COLLECTOR_PATH'] . ' -f ' . self::CONF_PATH;
            shell_exec($command);
            sleep(3);
            if (!$this->isCollectorRunning()) {
                $result .= __('Netflow collector startup failed by unknown reason') . ' ';
            }
        } else {
            $result .= __('Netflow collector is running now') . ' ';
        }
        
        if (isset($this->altCfg['SFLOW_COLLECTOR_PATH']) and !empty($this->altCfg['SFLOW_COLLECTOR_PATH'])) {
            if (!$this->isSflowCollectorRunning()) {
                $sflowCommand = $this->altCfg['SUDO_PATH'] . ' ' . $this->altCfg['SFLOW_COLLECTOR_PATH'] . ' -f ' . self::SFLOW_CONF_PATH;
                shell_exec($sflowCommand);
                sleep(3);
                if (!$this->isSflowCollectorRunning()) {
                    $result .= __('sFlow collector startup failed by unknown reason');
                }
            } else {
                $result .= __('sFlow collector is running now');
            }
        }
        
        return ($result);
    }

    /**
     * Brutally kills collector process
     *
     * @return void
     */
    public function stopCollector() {
        if ($this->isCollectorRunning()) {
            $command = $this->altCfg['SUDO_PATH'] . ' ' . $this->altCfg['KILLALL_PATH'] . ' -9  nfacctd';
            shell_exec($command);
            $pidRemove = $this->altCfg['SUDO_PATH'] . ' ' . $this->altCfg['RM_PATH'] . ' -fr ' . self::PID_PATH;
            shell_exec($pidRemove);
        }
        
        if ($this->isSflowCollectorRunning()) {
            $sflowCommand = $this->altCfg['SUDO_PATH'] . ' ' . $this->altCfg['KILLALL_PATH'] . ' -9  sfacctd';
            shell_exec($sflowCommand);
            $sflowPidRemove = $this->altCfg['SUDO_PATH'] . ' ' . $this->altCfg['RM_PATH'] . ' -fr ' . self::SFLOW_PID_PATH;
            shell_exec($sflowPidRemove);
        }
    }


    /**
     * Retrieves all networks as id=>networkData
     *
     * @return array
     */
    public function getAllNetworks() {
        return ($this->allNetworks);
    }
}
