<?php



set_time_limit(0);




$harvester=new OphanimHarvester();
$harvester->runTrafficProcessing();

$classifier=new OphanimClassifier();
$receivedData=$classifier->aggregateSource($classifier::TABLE_RAW_OUT,'ip_dst','port_src'); //dload
$classifier->saveAggregatedData('R',$receivedData);


$sentData=$classifier->aggregateSource($classifier::TABLE_RAW_IN,'ip_src','port_dst');
$classifier->saveAggregatedData('S',$sentData);
 
