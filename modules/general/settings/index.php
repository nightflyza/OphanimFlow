<?php



$settings = new OphanimMgr();

if (ubRouting::checkPost($settings::PROUTE_NETW_CREATE)) {
    $netToCreate = ubRouting::post($settings::PROUTE_NETW_CREATE);
    if (!$settings->isNetworkExists($netToCreate)) {
        $settings->createNetwork($netToCreate);
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

show_window(__('Netflow / IPFIX collector'), $settings->renderCollectorControls());
show_window(__('Available networks'), $settings->renderNetworksList());
show_window('', $settings->renderNetworkCreateForm());


