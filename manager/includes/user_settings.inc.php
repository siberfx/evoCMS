<?php

use EvolutionCMS\Models\UserSetting;

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

// START HACK
if (isset ($modx)) {
    $user_id = evo()->getLoginUserID();
} else {
    $user_id = $_SESSION['mgrInternalKey'];
}
// END HACK

if (!empty($user_id)) {
    // Raymond: grab the user settings from the database.
    $userSettings = UserSetting::query()
        ->select('setting_name', 'setting_value')
        ->where('user', evo()->getLoginUserID())
        ->get()
        ->toArray();

    $which_browser_default = $which_browser;
    foreach ($userSettings as $row) {
        if ($row['setting_name'] == 'which_browser' && $row['setting_value'] == 'default') {
            $row['setting_value'] = $which_browser_default;
        }
        $settings[$row['setting_name']] = $row['setting_value'];
        if (isset(evo()->config)) {
            evo()->config[$row['setting_name']] = $row['setting_value'];
        }
    }
    extract($settings);
}
