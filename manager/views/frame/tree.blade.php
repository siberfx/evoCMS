<?php

use EvolutionCMS\Facades\ManagerTheme;

// invoke OnManagerTreeInit event
$evtOut = evo()->invokeEvent('OnManagerTreeInit', $_REQUEST);
if (is_array($evtOut)) {
    echo implode("\n", $evtOut);
}
?>
<div class="treeframebody">
    <div id="treeMenu">

        <a class="treeButton" id="treeMenu_expandtree" onclick="modx.tree.expandTree();"
           title="{{ __('global.expand_tree') }}">
            <i class="{{ ManagerTheme::getStyle('icon_arrow_down_circle') }}"></i>
        </a>

        <a class="treeButton" id="treeMenu_collapsetree" onclick="modx.tree.collapseTree();"
           title="{{ __('global.collapse_tree') }}">
            <i class="{{ ManagerTheme::getStyle('icon_arrow_up_circle') }}"></i>
        </a>

        @if(evo()->hasPermission('new_document'))
            <a class="treeButton" id="treeMenu_addresource"
               onclick="modx.tabs({url:'{{ MODX_MANAGER_URL }}?a=4', title: '{{ __('global.add_resource') }}'});"
               title="{{ __('global.add_resource') }}">
                <i class="{{ ManagerTheme::getStyle('icon_document') }}"></i>
            </a>
            <a class="treeButton" id="treeMenu_addweblink"
               onclick="modx.tabs({url:'{{ MODX_MANAGER_URL }}?a=72', title: '{{ __('global.add_weblink') }}'});"
               title="{{ __('global.add_weblink') }}">
                <i class="{{ ManagerTheme::getStyle('icon_chain') }}"></i>
            </a>
        @endif

        <a class="treeButton" id="treeMenu_refreshtree" onclick="modx.tree.restoreTree();"
           title="{{ __('global.refresh_tree') }}">
            <i class="{{ ManagerTheme::getStyle('icon_refresh') }}"></i>
        </a>

        <a class="treeButton" id="treeMenu_sortingtree" onclick="modx.tree.showSorter(event);"
           title="{{ __('global.sort_tree') }}">
            <i class="{{ ManagerTheme::getStyle('icon_sort') }}"></i>
        </a>

        @if(evo()->hasPermission('edit_document') && evo()->hasPermission('save_document'))
            <a class="treeButton" id="treeMenu_sortingindex"
               onclick="modx.tabs({url: '{{ MODX_MANAGER_URL }}?a=56&id=0', title: '{{ __('global.sort_menuindex') }}'});"
               title="{{ __('global.sort_menuindex') }}">
                <i class="{{ ManagerTheme::getStyle('icon_sort_num_asc') }}"></i>
            </a>
        @endif

        @if(config('global.use_browser') && evo()->hasPermission('assets_images'))
            <a class="treeButton" id="treeMenu_openimages"
               title="{{ __('global.images_management') }}&#013;{{ __('global.em_button_shift') }}">
                <i class="{{ ManagerTheme::getStyle('icon_camera') }}"></i>
            </a>
        @endif

        @if(config('global.use_browser') && evo()->hasPermission('assets_files'))
            <a class="treeButton" id="treeMenu_openfiles"
               title="{{ __('global.files_management') }}&#013;{{ __('global.em_button_shift') }}">
                <i class="{{ ManagerTheme::getStyle('icon_files') }}"></i>
            </a>
        @endif

        @if(evo()->hasPermission('edit_template') || evo()->hasPermission('edit_snippet') || evo()->hasPermission('edit_chunk') || evo()->hasPermission('edit_plugin'))
            <a class="treeButton" id="treeMenu_openelements"
               title="{{ __('global.element_management') }}&#013;{{ __('global.em_button_shift') }}">
                <i class="{{ ManagerTheme::getStyle('icon_elements') }}"></i>
            </a>
        @endif

        @if(evo()->hasPermission('empty_trash'))
            <a class="treeButton treeButtonDisabled" id="treeMenu_emptytrash"
               title="{{ __('global.empty_recycle_bin_empty') }}">
                <i class="{{ ManagerTheme::getStyle('icon_trash') }}"></i>
            </a>
        @endif

        <a class="treeButton" id="treeMenu_theme_dark" onclick="modx.tree.toggleTheme(event)"
           title="{{ __('global.manager_theme_mode_title') }}"><i
                    class="{{ ManagerTheme::getStyle('icon_theme') }}"></i></a>

    </div>

    <div id="treeHolder">
        <?php
        // invoke OnManagerTreePrerender event
        $evtOut = evo()->invokeEvent('OnManagerTreePrerender', $_REQUEST);
        if (is_array($evtOut)) {
            echo implode("\n", $evtOut);
        }
        $siteName = config('global.site_name');
        ?>
        <div id="node0" class="rootNode">
            <a class="node" onclick="modx.tree.treeAction(event, 0)" data-id="0" data-title-esc="{{ $siteName }}">
                <span class="icon">
                    <i class="{{ ManagerTheme::getStyle('icon_sitemap') }}"></i>
                </span>
                <span class="title">{{ $siteName }}</span>
            </a>
            <div id="treeloader">
                <i class="{{ ManagerTheme::getStyle('icon_cog') }} {{ ManagerTheme::getStyle('icon_spin') }}"></i>
            </div>
        </div>
        <div id="treeRoot"></div>

        <?php
        // invoke OnManagerTreeRender event
        $evtOut = evo()->invokeEvent('OnManagerTreeRender', $_REQUEST);
        if (is_array($evtOut)) {
            echo implode("\n", $evtOut);
        }
        ?>
    </div>
</div>
