<?php

function bytesToSpeed($bytes) {
    $result=0;
    if (!is_numeric($bytes)) {
        $bytes=trim($bytes);
    }
    if ($bytes!=0) {
        $result=($bytes*8)/300/1048576; //per 5 minutes
    }

    return($result);
}

function bytesToMb($bytes) {
    $result=0;
    if ($bytes!=0) {
        $result=$bytes/1048576; //mbytes
    }
    return($result);
}

function getChartData($ip,$direction,$dateFrom,$dateTo) {
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

function parseSpeedData($rawData,$allocTimeline=false) {
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
                $tmpResult[]=bytesToSpeed($lineData);
                }
            }
            $result[$xAxis]=$tmpResult;
        }
    }
    return($result);
}

/**
 * testing controller here
 */


$ip='';
if (ubRouting::checkPost('ip')) {
    $ip=ubRouting::post('ip');
}

$dayAlloc= (ubRouting::checkPost('dayalloc')) ? true : false;

$ipsAvail=array();
$cache=new UbillingCache();
$ipsRaw=$cache->get('IPLIST',600);
if (empty($ipsRaw)) {
$ipsRaw=simple_queryall("select ip from traffstat order by dl DESC");
$cache->set('IPLIST',$ipsRaw,600);
}
if (!empty($ipsRaw)) {
    foreach ($ipsRaw as $io=>$each) {
        $ipsAvail[$each['ip']]=$each['ip'];
    }
}

$inputs=wf_SelectorSearchable('ip',$ipsAvail,'IP',$ip,false).' ';
$inputs.= wf_CheckInput('dayalloc','Allocate day timeline',false,$dayAlloc).' ';
$inputs.= wf_Submit('Search');
show_window('',wf_Form('','POST',$inputs,'glamour'));


$traffStatDb=new NyanORM(OphanimHarvester::TABLE_TRAFFSTAT);
$traffStatDb->where('ip','=',$ip);
$traffData=$traffStatDb->getAll();
if (!empty($traffData)) {
    show_success($ip.' Traffic summary - Downloaded: '.round(bytesToMb($traffData[0]['dl'])).' Mb Uploaded: '.round(bytesToMb($traffData[0]['ul'])).' Mb');
} else {
    show_warning('Nothing to show');
}

$classifier=new OphanimClassifier();
$legend=$classifier->getBaseStruct();

$chartMancer=new ChartMancer();
$chartMancer->setPalette('OphanimFlow');
$chartMancer->setDebug(true);
$chartMancer->setChartLegend($legend);
$chartMancer->setChartYaxisName('Mbit/s');
$chartMancer->setDisplayPeakValue(true);



$chartMancer->setChartTitle($ip.' Download');
$downloadRaw=getChartData($ip,'R','2023-12-16 00:00:00',date("Y-m-d H:i:s"));
$speedDataR=parseSpeedData($downloadRaw,$dayAlloc);
if (!empty($speedDataR)) {
$chartMancer->renderChart($speedDataR,'test.png');
deb(wf_img('test.png'));
}

$chartMancer->setChartTitle($ip.' Upload');
$uploadRaw=getChartData($ip,'S','2023-12-16 00:00:00',date("Y-m-d H:i:s"));
$speedDataS=parseSpeedData($uploadRaw,$dayAlloc);

if (!empty($speedDataS)) {
$chartMancer->renderChart($speedDataS,'test2.png');
deb(wf_img('test2.png'));
}
