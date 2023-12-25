<?php

/**
 * testing controller here
 */

$ip='';
if (ubRouting::checkPost('ip')) {
    $ip=ubRouting::post('ip');
}

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
$inputs.= wf_Submit('Search');
show_window('',wf_Form('','POST',$inputs,'glamour'));


$traffStatDb=new NyanORM(OphanimHarvester::TABLE_TRAFFSTAT);
$traffStatDb->where('ip','=',$ip);
$traffData=$traffStatDb->getAll();
if (!empty($traffData)) {
    show_success($ip.' Traffic summary - Downloaded: '.$traffData[0]['dl'].' Mb Uploaded: '.$traffData[0]['ul'].' Mb');
} else {
    show_warning('Nothing to show');
}

if ($ip) {
    $result='';
    $result.=wf_img('?module=graph&dir=R&period=day&ip='.$ip);
    $result.=wf_img('?module=graph&dir=S&period=day&ip='.$ip);
    show_window('Charts',$result);
}
