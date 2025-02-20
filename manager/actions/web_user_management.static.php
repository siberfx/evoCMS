<?php

use EvolutionCMS\Facades\ManagerTheme;
use EvolutionCMS\Models\User;
use EvolutionCMS\Models\UserRole;
use EvolutionCMS\Support\ContextMenu;
use EvolutionCMS\Support\MakeTable;

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}
if (!evo()->hasPermission('edit_user')) {
    evo()->webAlertAndQuit(__('global.error_no_privileges'));
}

$query = [
    'search' => isset($_REQUEST['search']) && is_scalar($_REQUEST['search']) ? $_REQUEST['search'] : '',
    'role' => isset($_REQUEST['role']) && is_scalar($_REQUEST['role']) ? $_REQUEST['role'] : '',
];

$page = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] - 1 : 0;

$op = $_REQUEST['op'] ?? '';

switch ($op) {
    case 'search':
        $page = 0;
        break;
    case 'reset':
        $query = [
            'search' => '',
            'role' => '',
        ];
        $page = 0;
        break;
}

// context menu
$cm = new ContextMenu('cntxm', 150);
$cm->addItem(
    __('global.edit'),
    'js:menuAction(1)',
    ManagerTheme::getStyle('icon_edit'),
    (!evo()->hasPermission('edit_user') ? 1 : 0)
);
$cm->addItem(
    __('global.delete'),
    'js:menuAction(2)',
    ManagerTheme::getStyle('icon_trash'),
    (!evo()->hasPermission('delete_user') ? 1 : 0)
);
echo $cm->render();

// roles
$role_options =
    '<option value="0"' . ($query['role'] == '0' ? ' selected' : '') . '>' . __('global.no_user_role') .
    '</option>';
$roles = UserRole::query()->select('id', 'name')->get()->toArray();
foreach ($roles as $row) {
    $role_options .= '<option value="' . $row['id'] . '" ' .
        ($query['role'] != '' && $row['id'] == $query['role'] ? 'selected' : '') . '>' . e($row['name']) . '</option>';
}

// prepare data
$managerUsers = User::query()
    ->select(
        'users.id',
        'users.username',
        'user_attributes.fullname',
        'user_attributes.email',
        'user_attributes.blocked',
        'user_attributes.thislogin',
        'user_attributes.logincount',
        'user_attributes.blockeduntil',
        'user_attributes.blockedafter',
        'user_roles.name'
    )
    ->join('user_attributes', 'user_attributes.internalKey', '=', 'users.id')
    ->leftJoin('user_roles', 'user_roles.id', '=', 'user_attributes.role')
    ->orderBy('users.username', 'ASC');

if ($query['search'] != '') {
    $val = $query['search'];
    $managerUsers = $managerUsers->where(function ($q) use ($val) {
        $q->where('users.username', 'LIKE', $val . '%')
            ->orWhere('user_attributes.fullname', 'LIKE', '%' . $val . '%')
            ->orWhere('user_attributes.email', 'LIKE', '%' . $val . '%');
    });
}
if ($query['role'] != '') {
    $val = $query['role'];
    $managerUsers = $managerUsers->where(function ($q) use ($val) {
        $q->where('user_attributes.role', '=', $val);
    });
}

$maxpageSize = evo()->getConfig('number_of_results');
define('MAX_DISPLAY_RECORDS_NUM', $maxpageSize);

$numRecords = $managerUsers->count();

if ($numRecords > 0) {
    $managerUsers = $managerUsers->offset($page * $maxpageSize)->limit($maxpageSize)->get()->toArray();

    // CSS style for table
    // $tableClass = 'grid';
    // $rowHeaderClass = 'gridHeader';
    // $rowRegularClass = 'gridItem';
    // $rowAlternateClass = 'gridAltItem';
    $tableClass = 'table data nowrap';
    $columnHeaderClass = [
        'center',
        '',
        '',
        '',
        'right" nowrap="nowrap,right,center',
    ];
    $table = new MakeTable();
    $table->setTableClass($tableClass);
    $table->setColumnHeaderClass($columnHeaderClass);
    // evo()->getMakeTable()->setRowHeaderClass($rowHeaderClass);
    // evo()->getMakeTable()->setRowRegularClass($rowRegularClass);
    // evo()->getMakeTable()->setRowAlternateClass($rowAlternateClass);

    // Table header
    $listTableHeader = [
        'icon' => __('global.icon'),
        'name' => __('global.name'),
        'user_full_name' => __('global.user_full_name'),
        'email' => __('global.email'),
        'user_prevlogin' => __('global.user_prevlogin'),
        'user_logincount' => __('global.user_logincount'),
        'user_block' => __('global.user_block'),
    ];
    $tbWidth = ['1%', '', '', '', '1%', '1%', '1%'];
    $table->setColumnWidths($tbWidth);

    $listDocs = [];
    foreach ($managerUsers as $k => $el) {
        // дата блокировки
        $blocked_title = '';
        if ($el['blocked']) {
            if ($el['blockedafter']) {
                $blocked_title .= __('global.user_blockedafter') . ' ' .
                    evo()->toDateFormat($el['blockedafter']);
            }
            if ($el['blockedafter'] && $el['blockeduntil']) {
                $blocked_title .= ', ';
            }
            if ($el['blockeduntil']) {
                $blocked_title .= __('global.user_blockeduntil') . ' ' .
                    evo()->toDateFormat($el['blockeduntil']);
            }
        }

        $listDocs[] = [
            'icon' => '<a class="gridRowIcon" href="javascript:;" onclick="return showContentMenu(' . $el['id'] .
                ', event);" title="' . __('global.click_to_context') . '"><i class="' .
                ManagerTheme::getStyle(empty($el['name']) ? 'icon_no_user_role' : 'icon_web_user') . '"></i></a>',
            'name' => '<a href="index.php?a=88&id=' . $el['id'] . '" title="' .
                __('global.click_to_edit_title') . '">' . e($el['username']) . '</a>',
            'user_full_name' => e($el['fullname']),
            'email' => e($el['email']),
            'role' => e($el['name'] ?: __('global.no_user_role')),
            'user_prevlogin' => $el['thislogin'] ? evo()->toDateFormat($el['thislogin']) : '-',
            'user_logincount' => $el['logincount'],
            'user_block' => $el['blocked'] ? __('global.yes') .
                ' <i class="fa fa-question-circle help" data-toggle="tooltip" data-placement="top" title="' .
                $blocked_title . '"></i>' : '-',
        ];
    }

    $table->createPagingNavigation($numRecords, 'a=99&' . http_build_query($query));
    $output = $table->create($listDocs, $listTableHeader, 'index.php?a=99');
} else {
    // no documents
    $output = '<div class="container"><p>' . __('global.resources_in_container_no') . '</p></div>';
}
?>
<script>
  function searchResource () {
    document.resource.op.value = 'search'
    document.resource.submit()
  }

  function resetSearch () {
    document.resource.op.value = 'reset'
    document.resource.submit()
  }

  var selectedItem
  var contextm = <?= $cm->getClientScriptObject() ?>;

  function showContentMenu (id, e) {
    selectedItem = id
    contextm.style.left = (e.pageX || (e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft))) +
      'px'
    contextm.style.top = (e.pageY || (e.clientY + (document.documentElement.scrollTop || document.body.scrollTop))) +
      'px'
    contextm.style.visibility = 'visible'
    e.cancelBubble = true
    return false
  }

  function menuAction (a) {
    var id = selectedItem
    switch (a) {
      case 1: // edit
        window.location.href = 'index.php?a=88&id=' + id
        break
      case 2: // delete
        if (confirm(`<?= __('global.confirm_delete_user') ?>`) === true) {
          window.location.href = 'index.php?a=90&id=' + id
        }
        break
    }
  }

  document.addEventListener('click', function () {
    contextm.style.visibility = 'hidden'
  })

  document.addEventListener('DOMContentLoaded', function () {
    var h1help = document.querySelector('h1 > .help')
    h1help.onclick = function () {
      document.querySelector('.element-edit-message').classList.toggle('show')
    }

    // bootstrap tooltip
    //document.querySelector('[data-toggle="tooltip"]').tooltip()
  })
</script>

<form name="resource" method="post" action="?a=99">
    <?= csrf_field() ?>
    <input type="hidden" name="op" value=""/>

    <h1>
        <i class="<?= ManagerTheme::getStyle('icon_web_user') ?>"></i><?= __('global.web_user_management_title') ?> <i
                class="<?= ManagerTheme::getStyle('icon_question_circle') ?> help"></i>
    </h1>

    <div class="container element-edit-message">
        <div class="alert alert-info"><?= __('global.web_user_management_msg') ?></div>
    </div>

    <div class="tab-page">
        <div class="container container-body">
            <div class="row searchbar form-group">
                <div class="col-sm-6 input-group">
                    <div class="input-group-btn">
                        <a class="btn btn-success btn-sm" href="index.php?a=87"><i
                                    class="<?= ManagerTheme::getStyle('icon_add') ?>"></i> <?= __('global.new_web_user') ?></a>
                    </div>
                </div>
                <div class="col-sm-6 ">
                    <div class="input-group float-right w-auto">
                        <select class="form-control form-control-sm" name="role">
                            <option value=""><?= __('global.web_user_management_select_role') ?></option>
                            <?= $role_options ?>
                        </select>
                        <input class="form-control form-control-sm" name="search" type="text"
                               value="<?= $query['search'] ?>" placeholder="<?= __('global.search') ?>"/>
                        <div class="input-group-append">
                            <a class="btn btn-secondary btn-sm" href="javascript:;"
                               title="<?= __('global.search') ?>"
                               onclick="searchResource(); return false;"><i
                                        class="<?= ManagerTheme::getStyle('icon_search') ?>"></i></a>
                            <a class="btn btn-secondary btn-sm" href="javascript:;"
                               title="<?= __('global.reset') ?>"
                               onclick="resetSearch(); return false;"><i
                                        class="<?= ManagerTheme::getStyle('icon_refresh') ?>"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group clearfix">
                <?php
                if ($numRecords > 0) : ?>
                    <div class="float-xs-left">
                        <span class="publishedDoc"><?= $numRecords . ' ' .
                            __('global.resources_in_container') ?></span>
                    </div>
                <?php
                endif; ?>
            </div>
            <div class="row">
                <div class="table-responsive">
                    <?= $output; ?>
                </div>
            </div>
        </div>
    </div>
</form>
