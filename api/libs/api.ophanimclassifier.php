<?php

/**
 * Basic traffic classifier implementation
 */
class OphanimClassifier {

    /**
     * Contains classified counters base structure [0..11]
     * 
     * @var array
     */
    protected $baseStruct = array(
        'time' => 0,
        'total' => 0,
        'icmp' => 0,
        'udp' => 0,
        'tcp' => 0,
        'mail' => 0,
        'dns' => 0,
        'vpn' => 0,
        'ftp' => 0,
        'web' => 0,
        'proxy' => 0,
        'quic' => 0,
        'sip' => 0,
    );

    /**
     * Contains TCP proto identifiers as port=>proto
     *
     * @var array
     */
    protected $tcpProto = array(
        80 => 'web',
        443 => 'web',
        21 => 'ftp',
        20 => 'ftp',
        110 => 'mail',
        25 => 'mail',
        143 => 'mail',
        587 => 'mail',
        53 => 'dns',
        3128 => 'proxy',
        8080 => 'proxy',
        8888 => 'proxy',
        1080 => 'proxy',
    );

    /**
     * Contains UDP proto identifiers as port=>proto
     *
     * @var array
     */
    protected $udpProto = array(
        53 => 'dns',
        1701 => 'vpn',
        51820 => 'vpn',
        443 => 'quic',
        80 => 'quic',
        5060=> 'sip',
    );



    /**
     * some predefined stuff here
     */
    const DATA_PATH = 'gdata/';
    const LR_PATH = 'exports/';
    const DELIMITER = ';';
    const TABLE_RAW_IN = 'raw_in';
    const TABLE_RAW_OUT = 'raw_out';


    public function __construct() {
    }

    /**
     * Drops datasource table data
     *
     * @param string $dataSource
     * 
     * @return void
     */
    protected function flushSource($dataSource) {
        nr_query('TRUNCATE TABLE `' . $dataSource . '`');
    }

    /**
     * Returns record base struct
     *
     * @return array
     */
    public function getBaseStruct() {
        $result = array();
        if (!empty($this->baseStruct)) {
            foreach ($this->baseStruct as $class => $io) {
                if ($class != 'time') {
                    $result[] = $class;
                }
            }
        }
        return ($result);
    }

    /**
     * Loads and returns all aggregated flows data in source and as ip=>baseStruct
     *
     * @param string $dataSource
     * @param string $ipColumn
     * @param string $portColumn
     * 
     * @return array
     */
    public function aggregateSource($dataSource, $ipColumn, $portColumn) {
        $result = array();
        $rawData = array();
        $databaseLayer = new NyanORM($dataSource);
        $rawData = $databaseLayer->getAll();

        if (!empty($rawData)) {
            foreach ($rawData as $io => $each) {
                $ip = $each[$ipColumn];
                $time = $each['stamp_inserted'];
                $proto = $each['ip_proto'];
                $port = $each[$portColumn];

                if (!isset($result[$ip][$time])) {
                    $result[$ip][$time] = $this->baseStruct;
                }

                $result[$ip][$time]['time'] = $time;
                $result[$ip][$time]['total'] += $each['bytes'];

                switch ($proto) {
                    case 'icmp':
                        $result[$ip][$time]['icmp'] += $each['bytes'];
                        break;

                    case 'tcp':
                        $result[$ip][$time]['tcp'] += $each['bytes'];
                        if (isset($this->tcpProto[$port])) {
                            $result[$ip][$time][$this->tcpProto[$port]] += $each['bytes'];
                        }
                        break;

                    case 'udp':
                        $result[$ip][$time]['udp'] += $each['bytes'];
                        if (isset($this->udpProto[$port])) {
                            $result[$ip][$time][$this->udpProto[$port]] += $each['bytes'];
                        }
                        break;

                    case 'gre':
                        $result[$ip][$time]['vpn'] += $each['bytes'];
                        break;

                    case 'esp':
                        $result[$ip][$time]['vpn'] += $each['bytes'];
                        break;

                    default:

                        break;
                }
            }
        }
        $this->flushSource($dataSource);
        return ($result);
    }

    /**
     * Saves per-IP aggregated charts data 
     * 
     * @param string $direction
     * @param array $aggregatedData
     * 
     * @return void
     */
    public function saveAggregatedData($direction, $aggregatedData) {
        if (!empty($aggregatedData)) {
            $fnamePrefix = self::DATA_PATH . $direction . '_';
            foreach ($aggregatedData as $eachIp => $eachTimeStamp) {
                $fileToSave = $fnamePrefix . $eachIp;
                $line = '';
                if (!empty($eachTimeStamp)) {
                    foreach ($eachTimeStamp as $timeStamp => $lineData) {
                        $line = '';
                        $line .= implode(self::DELIMITER, $lineData);
                        $line .= PHP_EOL;
                        file_put_contents($fileToSave, $line, FILE_APPEND);
                    }
                }
            }
        }
    }

    /**
     * Saves last run totals data, also as 0.0.0.0 chart data
     * 
     * @param string $direction
     * @param array $aggregatedData
     * 
     * @return void
     */
    public function saveLastRunData($direction, $aggregatedData) {
        if (!empty($aggregatedData)) {
            //raw last run data
            $fnameLr = self::LR_PATH . 'LR_' . $direction;
            $dataToSaveLr = json_encode($aggregatedData);
            file_put_contents($fnameLr, $dataToSaveLr);

            //total charts data
            $trafTotals = array();
            $baseStruct = $this->getBaseStruct();
            $tSctruct = array('time' => 0);

            foreach ($baseStruct as $io => $each) {
                $tSctruct[$each] = 0;
            }

            if (!empty($aggregatedData)) {
                foreach ($aggregatedData as $eachIp => $eachTs) {
                    if (!empty($eachTs)) {
                        foreach ($eachTs as $eachTimestamp => $eachBytes) {
                            if (!isset($trafTotals[$eachTimestamp])) {
                                $trafTotals[$eachTimestamp]['time'] = $eachTimestamp;
                                $trafTotals[$eachTimestamp] += $tSctruct;
                            }

                            if (!empty($eachBytes)) {
                                foreach ($eachBytes as $eachProto => $eachCounters) {
                                    if ($eachProto !== 'time') {
                                        $trafTotals[$eachTimestamp][$eachProto] += $eachCounters;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($trafTotals)) {
            $fnameTotal = self::DATA_PATH . $direction . '_' . '0.0.0.0';
            $line = '';
            if (!empty($trafTotals)) {
                foreach ($trafTotals as $io => $eachTotalsLine) {
                    $line .= implode(self::DELIMITER, $eachTotalsLine);
                    $line .= PHP_EOL;
                }
            }

            file_put_contents($fnameTotal, $line, FILE_APPEND);
        }
    }
}
