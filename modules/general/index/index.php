<?php

$dashBoard=new OphanimDash();

$ip = '';
$period = 'day';
if (ubRouting::checkPost($dashBoard::PROUTE_IP)) {
  $ip = ubRouting::post($dashBoard::PROUTE_IP);
}

if (ubRouting::checkPost($dashBoard::PROUTE_PERIOD)) {
  $period = ubRouting::post($dashBoard::PROUTE_PERIOD);
}



//per IP search form
show_window(__('Per-IP data'), $dashBoard->renderIpSelectForm());

//basic dashboard

if (!$ip) {
  //dashboard
  show_window(__('System info'), $dashBoard->renderSystemInfo());
  show_window(__('Traffic summary'),$dashBoard->renderTrafProtos());
}

if ($ip) {
 
  show_window('Charts', $dashBoard->renderIpGraphs($ip,$period));
}
