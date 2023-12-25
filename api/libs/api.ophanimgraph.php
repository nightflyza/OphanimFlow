<?php

class OphanimGraph {

    /**
     * Default graphs interval
     * 
     * @var int
     */
    protected $interval=300;


    /**
     * Custom graphs palette
     * 
     * @var string
     */
    protected $palette='OphanimFlow';

    /**
     * Bandwidthd-like legacy custom palette overrides
     * 
     * @var array
     */
    protected $colorOverrides=array(
                            1=>array('r'=>240,'g'=>0,'b'=>0),
                            2=>array('r'=>140,'g'=>0,'b'=>0),
                            3=>array('r'=>0,'g'=>220,'b'=>0),
                            5=>array('r'=>252,'g'=>93,'b'=>0),
                            6=>array('r'=>240,'g'=>240,'b'=>0),
                            7=>array('r'=>174,'g'=>174,'b'=>174),
                            8=>array('r'=>0,'g'=>0,'b'=>245),
                        );

    /**
     * Charts debug flag
     * 
     * @var bool
     */                        
    protected $debug=true;

    public function __construct() {
        
    }

    protected function bytesToSpeed($bytes) {
        $result=0;
        if (!is_numeric($bytes)) {
            $bytes=trim($bytes);
        }
        if ($bytes!=0) {
            $result=($bytes*8)/$this->interval/1048576; //per 5 minutes
        }

        return($result);
    }

    protected function bytesToMb($bytes) {
        $result=0;
        if ($bytes!=0) {
            $result=$bytes/1048576; //mbytes
        }
        return($result);
    }

    protected function getChartData($ip,$direction,$dateFrom,$dateTo) {
        $result=array();
        $tsFrom=strtotime($dateFrom);
        $tsTo=strtotime($dateTo);
        $delimiter=OphanimClassifier::DELIMITER;
        $source=OphanimClassifier::DATA_PATH.$direction.'_'.$ip;
        if (file_exists($source)) {
            $handle=fopen($source,'r');
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                if (!empty($buffer)) {
                    $eachLine=explode($delimiter,$buffer);
                    if ($eachLine[0]>=$tsFrom AND $eachLine[0]<=$tsTo) {
                    $result[]=$eachLine;
                 }
                }
            }
            fclose($handle);
        }
        return($result);
    }
    
    protected function parseSpeedData($rawData,$allocTimeline=false) {
        $result=array();
        if ($allocTimeline) {
             $result=allocDayTimeline();
        }
        if (!empty($rawData)) {
            foreach ($rawData as $io=>$eachLine) {
                $xAxis=date("H:i",$eachLine[0]);
                $tmpResult=array();
                foreach ($eachLine as $lnIdx=>$lineData) {
                    if ($lnIdx>0) {
                    $tmpResult[]=$this->bytesToSpeed($lineData);
                    }
                }
                $result[$xAxis]=$tmpResult;
            }
        }
        return($result);
    }


    public function renderGraph($ip,$direction,$dateFrom,$dateTo) {
            $chartTitle=$ip;
            if ($direction=='R') {
                $chartTitle=$ip.' '.__('Download');
            }
            if ($direction=='S') {
                $chartTitle=$ip.' '.__('Upload');
            }
            $chartMancer=new ChartMancer();
            $classifier=new OphanimClassifier();
            $legend=$classifier->getBaseStruct();
            
            
            $chartMancer->setPalette($this->palette);
            $chartMancer->setOverrideColors($this->colorOverrides);
            $chartMancer->setDebug(true);
            $chartMancer->setChartLegend($legend);
            $chartMancer->setChartYaxisName(__('Mbit/s'));
            $chartMancer->setDisplayPeakValue(true);
            $chartMancer->setChartTitle($chartTitle);

            $chartDataRaw=$this->getChartData($ip,$direction,$dateFrom,$dateTo);
            $speedData=$this->parseSpeedData($chartDataRaw,false);
           
            $chartMancer->renderChart($speedData);
}

}
