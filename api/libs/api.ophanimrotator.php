
<?php
/**
 * This class is responsible for managing storage space by trimming data files when the free space falls below a reserved percentage.
 */
class OphanimRotator {

    /**
     * Contains default per-host data files path
     *
     * @var string
     */
    protected $dataPath = '';
    /**
     * Contains reserved space percent
     *
     * @var int
     */
    protected $reservedPercent = 10;
    /**
     * Currently storage mountpoint usage bytes
     *
     * @var int
     */
    protected $storageUsed = 0;
    /**
     * Free storage bytes count
     *
     * @var int
     */
    protected $storageFree = 0;
    /**
     * Total storage size in bytes
     *
     * @var int
     */
    protected $storageTotal = 0;
    /**
     * Reserved storage bytes count
     *
     * @var int
     */
    protected $storageReserved = 0;
    /**
     * Total per-host data size in bytes
     *
     * @var int
     */
    protected $gdataTotalSize = 0;
    /**
     * Per host data files count
     *
     * @var int
     */
    protected $gdataCount = 0;
    /**
     * Array of all host data files as filePath=>size
     *
     * @var array
     */
    protected $gdataStats = array();
    /**
     * Just logging debug flag
     *
     * @var bool
     */
    protected $debugFlag = false;
    /**
     * Contains debug log path
     */
    const LOG_PATH = 'exports/rotator.log';

    public function __construct() {
        $this->setOptions();
        $this->loadStorageStats();
        $this->loadGdataStats();
    }

    /**
     * Sets some options
     *
     * @global object $ubillingConfig The global configuration object.
     * 
     * @return void
     */
    protected function setOptions() {
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();
        $this->dataPath = OphanimClassifier::DATA_PATH;
        if (isset($altCfg['STORAGE_RESERVED_SPACE'])) {
            $this->reservedPercent = $altCfg['STORAGE_RESERVED_SPACE'];
        }
        if (isset($altCfg['ROTATOR_DEBUG'])) {
            if ($altCfg['ROTATOR_DEBUG']) {
                $this->debugFlag = true;
            }
        }
    }

    /**
     * Loads the storage statistics for the specified data path.
     *
     * This function calculates the total disk space, free disk space, used disk space,
     * and reserved disk space based on the provided data path and reserved percentage.
     *
     * @return void
     */
    protected  function loadStorageStats() {
        $diskTotalSpace = disk_total_space($this->dataPath);
        $diskFreeSpace = disk_free_space($this->dataPath);

        $this->storageTotal = $diskTotalSpace;
        $this->storageFree = $diskFreeSpace;
        $this->storageUsed = $diskTotalSpace - $diskFreeSpace;
        $this->storageReserved = ($this->reservedPercent / 100) * $this->storageTotal;
    }

    /**
     * Loads per host data file statistics 
     * 
     * @return void
     */
    protected function loadGdataStats() {
        $allGdata = rcms_scandir($this->dataPath);
        if (!empty($allGdata)) {
            foreach ($allGdata as $io => $each) {
                if (ispos($each, 'R_') or ispos($each, 'S_')) {
                    $this->gdataCount++;
                    $eachPath = $this->dataPath . $each;
                    $eachSize = filesize($eachPath);
                    $this->gdataTotalSize += $eachSize;
                    $this->gdataStats[$eachPath] = $eachSize;
                }
            }
        }
    }

    /**
     * Runs rotator process
     *
     * @return void
     */
    public function run() {
        if ($this->storageFree < $this->storageReserved) {
            $spaceToTrim = $this->storageReserved - $this->storageFree;
            $maxAllowedSize = round(($this->gdataTotalSize - $spaceToTrim) / $this->gdataCount);
            $trimToMb = round(($maxAllowedSize / 1024 / 1024));
            if ($maxAllowedSize > 0) {
                if (!empty($this->gdataStats)) {
                    foreach ($this->gdataStats as $filePath => $fileSize) {
                        if ($fileSize > $maxAllowedSize) {
                            zb_TrimTextLog($filePath, $trimToMb);
                            if ($this->debugFlag) {
                                $logString = curdatetime() . ' TRIM ' . $filePath . ' ' . $fileSize . ' >= ' . $maxAllowedSize . '(' . $trimToMb . 'Mb)' . PHP_EOL;
                                file_put_contents(self::LOG_PATH, $logString, FILE_APPEND);
                            }
                        } else {
                            if ($this->debugFlag) {
                                $logString = curdatetime() . ' IGNORED ' . $filePath . ' ' . $fileSize . ' <= ' . $maxAllowedSize . '(' . $trimToMb . 'Mb)' . PHP_EOL;
                                file_put_contents(self::LOG_PATH, $logString, FILE_APPEND);
                            }
                        }
                    }
                }
            }
        } else {
            if ($this->debugFlag) {
                $logString = curdatetime() . 'ROTATOR SKIPPED ' . $this->storageFree . ' FREE > ' . $this->storageReserved . ' RESERVED' . PHP_EOL;
                file_put_contents(self::LOG_PATH, $logString, FILE_APPEND);
            }
        }
    }
}
