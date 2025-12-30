<?php

if (cfr('ROOT')) {

    $settings = new OphanimMgr(true);

    if (ubRouting::checkPost($settings::PROUTE_NETW_CREATE)) {
        $netToCreate = ubRouting::post($settings::PROUTE_NETW_CREATE);
        $descrToCreate = ubRouting::post($settings::PROUTE_NETW_DESC);
        if (!$settings->isNetworkExists($netToCreate)) {
            $settings->createNetwork($netToCreate, $descrToCreate);
            ubRouting::nav($settings::URL_ME);
        } else {
            show_error(__('Network') . ' `' . $netToCreate . '` ' . __('already exists'));
        }
    }

    if (ubRouting::checkGet($settings::ROUTE_NETW_DEL)) {
        $netToDelete = ubRouting::get($settings::ROUTE_NETW_DEL);
        if ($settings->isNetworkIdExists($netToDelete)) {
            $settings->deleteNetwork($netToDelete);
            ubRouting::nav($settings::URL_ME);
        } else {
            show_error(__('Network') . ' [' . $netToDelete . '] ' . __('not exists'));
        }
    }

    if (ubRouting::checkGet($settings::ROUTE_RECONF)) {
        $reconfResult = $settings->rebuildConfigs();
        if (empty($reconfResult)) {
            ubRouting::nav($settings::URL_ME);
        } else {
            show_error(__('Fatal') . ': ' . $reconfResult);
        }
    }

    if (ubRouting::checkGet($settings::ROUTE_START)) {
        $startResult = $settings->startCollector();
        if (empty($startResult)) {
            ubRouting::nav($settings::URL_ME);
        } else {
            show_error($startResult);
        }
    }

    if (ubRouting::checkGet($settings::ROUTE_STOP)) {
        $settings->stopCollector();
        ubRouting::nav($settings::URL_ME);
    }

    show_window(__('NetFlow / IPFIX / sFlow collectors'), $settings->renderCollectorControls());
    show_window(__('Available networks'), $settings->renderNetworksList());
    show_window('', $settings->renderNetworkCreateForm());
} else {
    show_error(__('Access denied'));
}
