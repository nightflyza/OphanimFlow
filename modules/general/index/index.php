<?php

if (cfr('ROOT')) {
  $dashBoard = new OphanimDash();

  $ip =  (ubRouting::checkPost($dashBoard::PROUTE_IP)) ? ubRouting::post($dashBoard::PROUTE_IP) : '';
  $period =  (ubRouting::checkPost($dashBoard::PROUTE_PERIOD)) ? ubRouting::post($dashBoard::PROUTE_PERIOD) : '';
  if (empty($ip)) {
    //get fallback
    if (ubRouting::checkGet($dashBoard::PROUTE_IP)) {
      $ip = ubRouting::get($dashBoard::PROUTE_IP);
    }
  }

  //per IP search form
  show_window(__('Per host data'), $dashBoard->renderIpSelectForm($ip));

  //basic dashboard

  if (!$ip) {
    //dashboard
    show_window(__('System info'), $dashBoard->renderSystemInfo());
    show_window(__('Traffic summary'), $dashBoard->renderTrafProtos());
  }

  if ($ip) {
    //per-host charts
    show_window('Charts', $dashBoard->renderIpGraphs($ip, $period));
  }
} else {
  show_error(__('Access denied'));
}
