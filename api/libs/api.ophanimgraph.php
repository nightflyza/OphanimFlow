<?php

/**
 * Performs charts data preprocessing and rendering
 */
class OphanimGraph {

    /**
     * Default graphs interval
     * 
     * @var int
     */
    protected $interval = 300;

    /**
     * Custom graphs palette
     * 
     * @var string
     */
    protected $palette = 'OphanimFlow';

    /**
     * Bandwidthd-like legacy custom palette overrides
     * 
     * @var array
     */
    protected $colorOverrides = array(
        1 => array('r' => 240, 'g' => 0, 'b' => 0),
        2 => array('r' => 140, 'g' => 0, 'b' => 0),
        3 => array('r' => 0, 'g' => 220, 'b' => 0),
        5 => array('r' => 252, 'g' => 93, 'b' => 0),
        6 => array('r' => 240, 'g' => 240, 'b' => 0),
        7 => array('r' => 174, 'g' => 174, 'b' => 174),
        8 => array('r' => 0, 'g' => 0, 'b' => 245),
        11 => array('r' => 54, 'g' => 49, 'b' => 22),
    );

    /**
     * Charts debug flag
     * 
     * @var bool
     */
    protected $debug = true;

    public function __construct() {
        global $ubillingConfig;
        $this->debug = $ubillingConfig->getAlterParam('CHARTS_DEBUG');
    }

    /**
     * Converts byte counters into speed in Mbit/s for 5 mins
     *
     * @param int $bytes
     * @param bool $check
     * 
     * @return float
     */
    public function bytesToSpeed($bytes, $check = false) {
        $result = 0;
        if ($check) {
            if (!is_numeric($bytes)) {
                $bytes = trim($bytes);
            }
        }

        if ($bytes != 0) {
            $result = ($bytes * 8) / $this->interval / 1048576; //mbits per 5 minutes
        }

        return ($result);
    }

    /**
     * Converts byte counters into Mbytes
     *
     * @param int $bytes
     * 
     * @return float
     */
    public function bytesToMb($bytes) {
        $result = 0;
        if ($bytes != 0) {
            $result = $bytes / 1048576; //mbytes
        }
        return ($result);
    }

    /**
     * Returns raw chart data as array of filtered by date record lines
     *
     * @param string $ip
     * @param string $direction
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return array
     */
    public function getChartData($ip, $direction, $dateFrom, $dateTo) {
        global $ubillingConfig;
        $result = array();
        $tsFrom = strtotime($dateFrom);
        $tsTo = strtotime($dateTo);
        $delimiter = OphanimClassifier::DELIMITER;
        $source = OphanimClassifier::DATA_PATH . $direction . '_' . $ip;

        if (file_exists($source)) {
            if ($ubillingConfig->getAlterParam('SPEED_LOAD')) {
                $depthLimit = (($tsTo - $tsFrom) / 300) + 200;
                $depthLimit = round($depthLimit);
                $tailPath = $ubillingConfig->getAlterParam('TAIL_PATH');
                $command = $tailPath . ' -n ' . $depthLimit . ' ' . $source;
                $resultRaw = shell_exec($command);
                $resultRaw = explodeRows($resultRaw);
                if (!empty($resultRaw)) {
                    foreach ($resultRaw as $io => $eachLine) {
                        if (!empty($eachLine)) {
                            $eachLine = explode($delimiter, $eachLine);
                            if ($eachLine[0] >= $tsFrom and $eachLine[0] <= $tsTo) {
                                $result[] = $eachLine;
                            }
                        }
                    }
                }
            } else {
                $handle = fopen($source, 'r');
                while (!feof($handle)) {
                    $buffer = fgets($handle, 4096);
                    if (!empty($buffer)) {
                        $eachLine = explode($delimiter, $buffer);
                        if ($eachLine[0] >= $tsFrom and $eachLine[0] <= $tsTo) {
                            $result[] = $eachLine;
                        }
                    }
                }
                fclose($handle);
            }
        }
        return ($result);
    }

    /**
     * Parses raw traffic data per line and returns it prepared for charting as datetime=>speedsArr
     *
     * @param array $rawData
     * @param bool $allocTimeline
     * 
     * @return array
     */
    protected function parseSpeedData($rawData, $allocTimeline = false) {
        $result = array();
        global $ubillingConfig;
        $validationFlag = $ubillingConfig->getAlterParam('VALIDATE_COUNTERS');
        $validationFlag = ($validationFlag) ? true : false;

        if ($allocTimeline) {
            $result = allocDayTimeline();
        }
        if (!empty($rawData)) {
            $dataSize = sizeof($rawData);
            foreach ($rawData as $io => $eachLine) {
                $xAxis = ($dataSize < 287) ? date("H:i", $eachLine[0]) : date("d/m/Y H:i", $eachLine[0]);
                $tmpResult = array();
                foreach ($eachLine as $lnIdx => $lineData) {
                    if ($lnIdx > 0) {
                        $tmpResult[] = $this->bytesToSpeed($lineData, $validationFlag);
                    }
                }
                $result[$xAxis] = $tmpResult;
            }
        }
        return ($result);
    }

    /**
     * Renders PNG chart for some IP
     *
     * @param string $ip
     * @param string $direction
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $width
     * @param string $height
     * @param string $titleAppend
     * 
     * @return void
     */
    public function renderGraph($ip, $direction, $dateFrom, $dateTo, $width = '', $height = '', $titleAppend = '') {
        $chartTitle = $ip;
        if ($direction == 'R') {
            $chartTitle = $ip . ' ' . __('Download');
        }
        if ($direction == 'S') {
            $chartTitle = $ip . ' ' . __('Upload');
        }
        if ($titleAppend) {
            $chartTitle .= $titleAppend;
        }

        $chartWidth = ($width) ? $width : 1540;
        $chartHeight = ($height) ? $height : 400;

        $chartMancer = new ChartMancer();
        $classifier = new OphanimClassifier();
        $legend = $classifier->getBaseStruct();

        $chartMancer->setImageWidth($chartWidth);
        $chartMancer->setImageHeight($chartHeight);

        $chartMancer->setPalette($this->palette);
        $chartMancer->setOverrideColors($this->colorOverrides);
        $chartMancer->setDebug($this->debug);
        $chartMancer->setChartLegend($legend);
        $chartMancer->setChartYaxisName(__('Mbit/s'));
        $chartMancer->setDisplayPeakValue(true);
        $chartMancer->setChartTitle($chartTitle);

        $chartDataRaw = $this->getChartData($ip, $direction, $dateFrom, $dateTo);
        $speedData = $this->parseSpeedData($chartDataRaw, false);

        if (sizeof($speedData) >= 288) {
            $chartMancer->setXLabelLen(10);
            $chartMancer->setXLabelsCount(12);
            $chartMancer->setCutSuffix('');
        }

        $chartMancer->renderChart($speedData);
    }
}
