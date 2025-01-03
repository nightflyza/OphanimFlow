<?php

class OphanimRotator {

    protected $dataPath = '';
    protected $reservedPercent = 10;
    protected $storageUsed = 0;
    protected $storageFree = 0;
    protected $storageTotal = 0;
    protected $storageReserved = 0;
    protected $gdataTotalSize = 0;
    protected $gdataCount=0;
    protected $gdataStats = array();

    public function __construct() {
        $this->setOptions();
        $this->loadStorageStats();
        $this->loadGdataStats();
    }

    protected function setOptions() {
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();
        $this->dataPath = OphanimClassifier::DATA_PATH;
        if (isset($altCfg['STORAGE_RESERVED_SPACE'])) {
            $this->reservedPercent = $altCfg['STORAGE_RESERVED_SPACE'];
        }
    }

    protected  function loadStorageStats() {
        $diskTotalSpace = disk_total_space($this->dataPath);
        $diskFreeSpace = disk_free_space($this->dataPath);

        $this->storageTotal = $diskTotalSpace;
        $this->storageFree = $diskFreeSpace;
        $this->storageUsed = $diskTotalSpace - $diskFreeSpace;
        $this->storageReserved = ($this->reservedPercent / 100) * $this->storageTotal;
    }

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

    public function run() {
    
        if ($this->storageFree < $this->storageReserved) {
            $spaceToTrim = $this->storageReserved - $this->storageFree;
            deb('free: '. zb_convert_size($this->storageFree));
            deb('reserved'.zb_convert_size($this->storageReserved));
            deb('leak:'.zb_convert_size($spaceToTrim));
            $maxAllowedSize = ($this->gdataTotalSize-$spaceToTrim) / $this->gdataCount;
            deb('max size:'.zb_convert_size($maxAllowedSize));
            foreach ($this->gdataStats as $filePath => $fileSize) {
                if ($fileSize > $maxAllowedSize) {
                  //here cleanup
                }
            }
        }
    }
}
