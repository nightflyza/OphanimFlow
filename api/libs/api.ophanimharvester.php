<?php

/**
 * Performs traffic summary accounting for each of collected hosts
 */
class OphanimHarvester {

    /**
     * Contains current year number
     *
     * @var int
     */
    protected $currentYear = 0;

    /**
     * Contains current month number without leading zero
     *
     * @var int
     */
    protected $currentMonth = 0;

    /**
     * Contains current day of month number
     *
     * @var int
     */
    protected $currentDay = 0;

    /**
     * Contains aggregated hosts in database abstraction layer
     * 
     * @var object
     */
    protected $inDb = '';

    /**
     * Contains aggregated hosts out database abstraction layer
     * 
     * @var object
     */
    protected $outDb = '';

    /**
     * Contains traffic summary table database abstration layer
     * 
     * @var object
     */
    protected $traffDb = '';

    /**
     * Contains preloaded previous traffic summary data for current month as ip=>[dl/ul]
     *
     * @var array
     */
    protected $traffStats = array();

    /**
     * Contains loaded current run traffic as ip>in/out
     *
     * @var array
     */
    protected $currentTraff = array();


    /**
     * some predefined stuff here
     */
    const TABLE_HOST_IN = 'host_in';
    const TABLE_HOST_OUT = 'host_out';
    const TABLE_TRAFFSTAT = 'traffstat';

    public function __construct() {
        $this->setDates();
        $this->initDb();
        $this->loadTraffStats();
    }

    /**
     * Sets current datetime properties
     *
     * @return void
     */
    protected function setDates() {
        $this->currentYear = date("Y");
        $this->currentMonth = date("n");
        $this->currentDay = date("d");
    }

    /**
     * Inits database abstraction layers
     *
     * @return void
     */
    protected function initDb() {
        $this->inDb = new NyanORM(self::TABLE_HOST_IN);
        $this->outDb = new NyanORM(self::TABLE_HOST_OUT);
        $this->traffDb = new NyanORM(self::TABLE_TRAFFSTAT);
    }

    /**
     * Loads current month saved traffic stats
     *
     * @return void
     */
    protected function loadTraffStats() {
        $this->traffDb->where('year', '=', $this->currentYear);
        $this->traffDb->where('month', '=', $this->currentMonth);
        $this->traffStats = $this->traffDb->getAll('ip');
    }

    /**
     * Returns last run aggregates traffic stats as ip=>in/out
     *
     * @return array
     */
    protected function getLastTraff() {
        $tmp = array();
        $allOut = $this->outDb->getAll();
        $allIn = $this->inDb->getAll();

        if (!empty($allOut)) {
            foreach ($allOut as $io => $each) {
                $ip = $each['ip_dst'];
                if (isset($tmp[$ip]['in'])) {
                    $tmp[$ip]['in'] += $each['bytes'];
                } else {
                    $tmp[$ip]['in'] = $each['bytes'];
                    $tmp[$ip]['out'] = 0;
                }
            }
        }


        if (!empty($allIn)) {
            foreach ($allIn as $io => $each) {
                $ip = $each['ip_src'];
                if (isset($tmp[$ip]['out'])) {
                    $tmp[$ip]['out'] += $each['bytes'];
                } else {
                    $tmp[$ip]['out'] = $each['bytes'];
                    $tmp[$ip]['in'] = 0;
                }
            }
        }

        return ($tmp);
    }

    /**
     * Drops data from host in/out tables
     *
     * @return void
     */
    protected function flushLastTraff() {
        nr_query('TRUNCATE TABLE `' . self::TABLE_HOST_IN . '`');
        nr_query('TRUNCATE TABLE `' . self::TABLE_HOST_OUT . '`');
    }

    /**
     * Performs aggregated traffic processing, flushes raw data and updates summary database if required
     *
     * @return void
     */
    public function runTrafficProcessing() {
        $currentRunTraff = $this->getLastTraff();
        if (!empty($currentRunTraff)) {
            $this->flushLastTraff();
            foreach ($currentRunTraff as $eachIp => $eachTraff) {
                if (isset($this->traffStats[$eachIp])) {
                    $savedData = $this->traffStats[$eachIp];
                    $recordId = $savedData['id']; //current month
                    $newDl = $savedData['dl'] + $eachTraff['in'];
                    $newUl = $savedData['ul'] + $eachTraff['out'];
                    //updating existing record
                    if ($newDl != $savedData['dl'] or $newUl != $savedData['ul']) {
                        $this->traffDb->data('dl', $newDl);
                        $this->traffDb->data('ul', $newUl);
                        $this->traffDb->where('id', '=', $recordId);
                        $this->traffDb->save(true, true);
                    }
                } else {
                    //creating new record
                    $this->traffDb->data('ip', $eachIp);
                    $this->traffDb->data('month', $this->currentMonth);
                    $this->traffDb->data('year', $this->currentYear);
                    $this->traffDb->data('dl', $eachTraff['in']);
                    $this->traffDb->data('ul', $eachTraff['out']);
                    $this->traffDb->create();
                }
            }
        } else {
            show_warning('Nothing changed');
        }
    }

    /**
     * Returns traffic summary data for some period or current month
     *
     * @param string $year
     * @param string $month
     * @param string $ip
     * 
     * @return array
     */
    public function getTraffCounters($year = '', $month = '', $ip = '') {
        $year = ubRouting::filters($year, 'int');
        $month = ubRouting::filters($month, 'int');
        $ip = ubRouting::filters($ip, 'mres');

        $result = array();
        if ($year and $month) {
            $this->traffDb->where('year', '=', $year);
            $this->traffDb->where('month', '=', $month);
        } else {
            $this->traffDb->where('year', '=', $this->currentYear);
            $this->traffDb->where('month', '=', $this->currentMonth);
        }

        if ($ip) {
            $this->traffDb->where('ip', '=', $ip);
        }

        $rawResult = $this->traffDb->getAll();
        if (!empty($rawResult)) {
            foreach ($rawResult as $io => $each) {
                $result[$each['ip']]['dl'] = $each['dl'];
                $result[$each['ip']]['ul'] = $each['ul'];
            }
        }
        return ($result);
    }
}
