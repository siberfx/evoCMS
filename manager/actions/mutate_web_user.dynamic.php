<?php

use EvolutionCMS\Facades\ManagerTheme;
use EvolutionCMS\Models\MemberGroup;
use EvolutionCMS\Models\MembergroupName;
use EvolutionCMS\Models\SiteTmplvar;
use EvolutionCMS\Models\User;
use EvolutionCMS\Models\UserAttribute;
use EvolutionCMS\Models\UserRole;
use EvolutionCMS\Models\UserSetting;
use Illuminate\Support\Facades\DB;

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

switch (evo()->getManagerApi()->action) {
    case 88:
        if (!evo()->hasPermission('edit_user')) {
            evo()->webAlertAndQuit(__('global.error_no_privileges'));
        }
        break;
    case 87:
        if (!evo()->hasPermission('new_user')) {
            evo()->webAlertAndQuit(__('global.error_no_privileges'));
        }
        break;
    default:
        evo()->webAlertAndQuit(__('global.error_no_privileges'));
}

$user = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

// @TODO check lock

$userdata = [
    'fullname' => '',
    'middle_name' => '',
    'first_name' => '',
    'last_name' => '',
    'verified' => 0,
    'role' => 0,
    'blocked' => 0,
    'blockeduntil' => 0,
    'blockedafter' => 0,
    'failedlogins' => 0,
    'email' => '',
    'phone' => '',
    'mobilephone' => '',
    'dob' => 0,
    'gender' => 3,
    'country' => '',
    'street' => '',
    'city' => '',
    'state' => '',
    'zip' => '',
    'fax' => '',
    'photo' => '',
    'comment' => '',
];

$usersettings = [
    'allowed_days' => '',
    'login_home' => '',
    'allowed_ip' => '',
    'manager_login_startup' => '',
    'which_browser' => 'default',
];

$usernamedata = [
    'username' => '',
];

if (evo()->getManagerApi()->action == '88') {
    // get user attributes
    $userdatatmp = UserAttribute::query()->where('internalKey', $user)->first();
    if (!$userdatatmp) {
        evo()->webAlertAndQuit('No user returned!');
    }
    $userdatatmp = $userdatatmp->makeVisible('role')->toArray();
    $userdata = array_merge($userdata, $userdatatmp);
    unset($userdatatmp);

    // get user settings
    $usersettings = UserSetting::query()->where('user', $user)->pluck('setting_value', 'setting_name')->toArray();
    extract($usersettings);
    // get username
    $usernamedata = User::query()->find($user)->toArray();
    if (!$usernamedata) {
        evo()->webAlertAndQuit('No user returned while getting username!');
    }
    $_SESSION['itemname'] = $usernamedata['username'];
} else {
    $_SESSION['itemname'] = __('global.new_web_user');
}
// avoid doubling htmlspecialchars (already encoded in DB)
foreach ($userdata as $key => $val) {
    $userdata[$key] = html_entity_decode($val ?? '', ENT_NOQUOTES, evo()->getConfig('modx_charset'));
}

$usernamedata['username'] = html_entity_decode(
    get_by_key($usernamedata, 'username', ''),
    ENT_NOQUOTES,
    evo()->getConfig('modx_charset')
);

// restore saved form
$formRestored = false;
if (evo()->getManagerApi()->hasFormValues()) {
    evo()->getManagerApi()->loadFormValues();
    unset($_POST['a']);
    // restore post values
    $userdata = array_merge($userdata, $_POST);
    $userdata['dob'] = evo()->toTimeStamp($userdata['dob']);
    $usernamedata['username'] = $userdata['newusername'];
    $usernamedata['oldusername'] = $_POST['oldusername'] ?? '';
    $usersettings = array_merge($usersettings, $userdata);
    if (isset($_POST['allowed_days'])) {
        $usersettings['allowed_days'] = is_array($_POST['allowed_days']) ? implode(',', $_POST['allowed_days']) : '';
    } else {
        $usersettings['allowed_days'] = '';
    }
    extract($usersettings);
}

if (isset($_REQUEST['newrole'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $query['a'] = evo()->getManagerApi()->action;
        if ($user) {
            $query['id'] = $user;
        }
        $query['newrole'] = $_REQUEST['newrole'];
        evo()->getManagerApi()->saveFormValues(87);
        redirect('index.php?' . http_build_query($query))->send();
    } else {
        $userdata['role'] = $_REQUEST['newrole'];
    }
}

// include the country list language file
$_country_lang = [];
if ($manager_language != 'english' &&
    file_exists(MODX_MANAGER_PATH . 'includes/lang/country/' . $manager_language . '_country.inc.php')
) {
    include_once MODX_MANAGER_PATH . 'includes/lang/country/' . $manager_language . '_country.inc.php';
} else {
    include_once MODX_MANAGER_PATH . 'includes/lang/country/en_country.inc.php';
}
asort($_country_lang);

$displayStyle = ($_SESSION['browser'] === 'modern') ? 'table-row' : 'block';
?>
<style>
    .image_for_field[data-image] { display: block; content: ""; width: 120px; height: 120px; margin: .1rem .1rem 0 0; border: 1px #ccc solid; background: #fff 50% 50% no-repeat; background-size: contain; cursor: pointer }
    .image_for_field[data-image=""] { display: none }

</style>

<script>
  function changestate (el) {
    documentDirty = true
    if (parseInt(el.value) === 1) {
      el.value = 0
    } else {
      el.value = 1
    }
  }

  function changePasswordState (el) {
    if (parseInt(el.value) === 1) {
      document.getElementById('passwordBlock').style.display = 'block'
    } else {
      document.getElementById('passwordBlock').style.display = 'none'
    }
  }

  function changeblockstate (el, checkelement) {
    if (parseInt(el.value) === 1) {
      if (confirm(`<?= __('global.confirm_unblock'); ?>`) === true) {
        document.userform.blocked.value = 0
        document.userform.blockeduntil.value = ''
        document.userform.blockedafter.value = ''
        document.userform.failedlogincount.value = 0
        blocked.innerHTML = <?=json_encode('<b>' . __('global.unblock_message') . '</b>', JSON_UNESCAPED_SLASHES)?>;
        blocked.className = 'TD'
        el.value = 0
      } else {
        checkelement.checked = true
      }
    } else {
      if (confirm(`<?= __('global.confirm_block'); ?>`) === true) {
        document.userform.blocked.value = 1
        blocked.innerHTML = <?=json_encode('<b>' . __('global.block_message') . '</b>', JSON_UNESCAPED_SLASHES)?>;
        blocked.className = 'warning'
        el.value = 1
      } else {
        checkelement.checked = false
      }
    }
  }

  function resetFailed () {
    document.userform.failedlogincount.value = 0
    document.getElementById('failed').innerHTML = '0'
  }

  // change name
  function changeName () {
    if (confirm(`<?= __('global.confirm_name_change'); ?>`) === true) {
      var e1 = document.getElementById('showname')
      var e2 = document.getElementById('editname')
      e1.style.display = 'none'
      e2.style.display = '<?= $displayStyle; ?>'
    }
  }

  // showHide - used by custom settings
  function showHide (what, onoff) {
    var all = document.getElementsByTagName('*')
    var l = all.length
    var buttonRe = what
    var id, el, stylevar

    if (onoff === 1) {
      stylevar = '<?= $displayStyle; ?>'
    } else {
      stylevar = 'none'
    }

    for (var i = 0; i < l; i++) {
      el = all[i]
      id = el.id
      if (!id) continue
      if (buttonRe.test(id)) {
        el.style.display = stylevar
      }
    }
  }

  var curRole = -1
  var curRoleIndex = 0

  function storeCurRole () {
    var dropRole = document.getElementById('role')
    if (dropRole) {
      for (var i = 0; i < dropRole.length; i++) {
        if (dropRole[i].selected) {
          curRole = dropRole[i].value
          curRoleIndex = i
        }
      }
    }
  }

  var newRole

  function roleWarning () {
    var dropRole = document.getElementById('role')
    if (dropRole) {
      for (var i = 0; i < dropRole.length; i++) {
        if (dropRole[i].selected) {
          newRole = dropRole[i].value
          break
        }
      }
    }
    if (curRole === newRole) {
      return
    }

    if (documentDirty === true) {
      if (confirm(`<?= __('global.tmplvar_change_template_msg') ?>`)) {
        documentDirty = false
        document.userform.a.value = <?= $user ? 88 : 87 ?>;
        document.userform.newrole.value = newRole
        document.userform.submit()
      } else {
        dropRole[curRoleIndex].selected = true
      }
    } else {
      document.userform.a.value = <?= $user ? 88 : 87 ?>;

      document.userform.newrole.value = newRole
      document.userform.submit()
    }
  }

  var actions = {
    save: function () {
      documentDirty = false
      document.userform.save.click()
    },
    delete: function () {
      if (confirm(`<?= __('global.confirm_delete_user') ?>`) === true) {
        window.location.href = 'index.php?id=' + document.userform.id.value + '&a=90'
      }
    },
    cancel: function () {
      documentDirty = false
      window.location.href = 'index.php?a=99'
    }
  }

  function evoRenderTvImageCheck (a) {
    var b = document.getElementById('image_for_' + a.target.id),
      c = new Image
    a.target.value ? (c.src = (a.target.value.search(/^https?:\/\//i) < 0
      ? "<?php echo evo()->getConfig('site_url')?>"
      : '') + a.target.value, c.onerror = function () {
      b.style.backgroundImage = '', b.setAttribute('data-image', '')
    }, c.onload = function () {
      b.style.backgroundImage = 'url(\'' + this.src + '\')', b.setAttribute('data-image', this.src)
    }) : (b.style.backgroundImage = '', b.setAttribute('data-image', ''))
  }
</script>

<form name="userform" method="post" action="index.php">
    <?= csrf_field() ?>
    <?php
    // invoke OnWUsrFormPrerender event
    $evtOut = evo()->invokeEvent('OnUserFormPrerender', ['id' => $user]);
    if (is_array($evtOut)) {
        echo implode('', $evtOut);
    }
    ?>
    <input type="hidden" name="a" value="89">
    <input type="hidden" name="mode" value="<?= evo()->getManagerApi()->action ?>"/>
    <input type="hidden" name="id" value="<?= $user ?>"/>
    <input type="hidden" name="newrole" value=""/>
    <input type="hidden" name="blockedmode" value="<?= ($userdata['blocked'] == 1 ||
        ($userdata['blockeduntil'] > time() && $userdata['blockeduntil'] != 0) ||
        ($userdata['blockedafter'] < time() && $userdata['blockedafter'] != 0) ||
        $userdata['failedlogins'] > evo()->getConfig('failed_login_attempts')) ? '1' : '0' ?>"/>

    <h1>
        <i class="<?= ManagerTheme::getStyle('icon_web_user') ?>"></i><?= ($usernamedata['username']
            ? $usernamedata['username'] .
            (isset($usernamedata['id']) ? '<small>(' . $usernamedata['id'] . ')</small>' : '')
            : __('global.web_user_title')) ?>
    </h1>

    <?= ManagerTheme::getStyle('actionbuttons.dynamic.user') ?>

    <!-- Tab Start -->
    <div class="sectionBody">

        <div class="tab-pane" id="webUserPane">
            <script>
              tpUser = new WebFXTabPane(document.getElementById('webUserPane'), <?= evo()->getConfig(
                  'remember_last_tab'
              ) == 1 ? 'true' : 'false'; ?>)
            </script>
            <div class="tab-page" id="tabGeneral">
                <h2 class="tab"><?= __('global.settings_general') ?></h2>
                <script>tpUser.addTabPage(document.getElementById('tabGeneral'))</script>
                <table border="0" cellspacing="0" cellpadding="3">
                    <?php
                    if ($userdata['blocked'] == 1 ||
                        ($userdata['blockeduntil'] > time() && $userdata['blockeduntil'] != 0) ||
                        ($userdata['blockedafter'] < time() && $userdata['blockedafter'] != 0) ||
                        $userdata['failedlogins'] > 3
                    ) { ?>
                        <tr>
                            <td colspan="3"><span id="blocked" class="warning">
                                <b><?= __('global.user_is_blocked'); ?></b>
                            </span>
                                <br/></td>
                        </tr>
                        <?php
                    } ?>
                    <tr>
                        <td><?= __('global.user_role'); ?>:
                        </td>
                        <td>
                            <?php
                            $roles = UserRole::query()->select('name', 'id');
                            if (!evo()->hasPermission('save_role')) {
                                $roles = $roles->where('id', '!=', 1);
                            }
                            ?>
                            <select name="role" id="role" class="inputBox" onChange="roleWarning();"
                                    style="width:300px">
                                <option value="0"<?= $userdata['role'] == 0 ? ' selected'
                                    : '' ?>><?= __('global.no_user_role') ?></option>
                                <?php
                                foreach ($roles->get()->toArray() as $row) {
                                    if (evo()->getManagerApi()->action == '11') {
                                        $selectedtext = $row['id'] == '1' ? ' selected' : '';
                                    } else {
                                        $selectedtext = $row['id'] == $userdata['role'] ? ' selected' : '';
                                    }
                                    ?>
                                    <option value="<?= $row['id']; ?>"<?= $selectedtext; ?>><?= e(
                                            $row['name']
                                        ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <?php
                    if (!empty($userdata['id'])) { ?>
                        <tr id="showname" style="display: <?= (evo()->getManagerApi()->action == '88' &&
                            (!isset($usernamedata['oldusername']) ||
                                $usernamedata['oldusername'] == $usernamedata['username'])) ? $displayStyle
                            : 'none'; ?> ">
                            <td><?= __('global.username'); ?>:
                            </td>
                            <td>
                                <i class="<?= ManagerTheme::getStyle('icon_web_user') ?>"></i>&nbsp;<b><?= e(
                                        !empty($usernamedata['oldusername']) ? $usernamedata['oldusername']
                                            : $usernamedata['username']
                                    ); ?></b> - <span class="comment">
                                    <a href="javascript:;"
                                       onClick="changeName();return false;"><?= __('global.change_name') ?></a>
                                </span>
                                <input type="hidden" name="oldusername"
                                       value="<?= e(
                                           !empty($usernamedata['oldusername']) ? $usernamedata['oldusername']
                                               : $usernamedata['username']
                                       ); ?>"/>
                            </td>
                        </tr>
                        <?php
                    } ?>
                    <tr id="editname" style="display:<?= evo()->getManagerApi()->action == '87' ||
                    (isset($usernamedata['oldusername']) && $usernamedata['oldusername'] != $usernamedata['username'])
                        ? $displayStyle : 'none'; ?>">
                        <td><?= __('global.username'); ?>:
                        </td>
                        <td>
                            <input type="text" name="newusername" class="inputBox"
                                   value="<?= e(
                                       $_POST['newusername'] ?? $usernamedata['username']
                                   ); ?>" onChange="documentDirty=true;" maxlength="100"/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?= evo()->getManagerApi()->action == '87' ? __('global.password') . ':'
                                : __('global.change_password_new') . ':'; ?>
                        </td>
                        <td>
                            <input name="newpasswordcheck" type="checkbox"
                                   onClick="changestate(document.userform.newpassword);changePasswordState(document.userform.newpassword);"<?= evo(
                            )->getManagerApi()->action == '87' ? ' checked disabled' : ''; ?>>
                            <input type="hidden" name="newpassword"
                                   value="<?= evo()->getManagerApi()->action == '87' ? 1 : 0; ?>"
                                   onChange="documentDirty=true;"/>
                            <br/>
                            <span style="display:<?= evo()->getManagerApi()->action == '87' ? 'block' : 'none'; ?>"
                                  id="passwordBlock">
                            <fieldset style="width:300px">
                                <legend><?= __('global.password_gen_method'); ?></legend>
                                <input type=radio name="passwordgenmethod" value="g"<?= get_by_key(
                                    $_POST,
                                    'passwordgenmethod'
                                ) === 'spec' ? '' : ' checked'; ?> />
                                <?= __('global.password_gen_gen'); ?>
                                <br/>
                                <input type=radio name="passwordgenmethod" value="spec"<?= get_by_key(
                                    $_POST,
                                    'passwordgenmethod'
                                ) === 'spec' ? ' checked' : ''; ?>>
                                <?= __('global.password_gen_specify'); ?>
                                <br/>
                                <div>
                                    <label for="specifiedpassword" style="width:120px"><?= __(
                                            'global.change_password_new'
                                        ) ?>:</label>
                                    <input type="password" name="specifiedpassword" onChange="documentdirty=true;"
                                           onKeyPress="document.userform.passwordgenmethod[1].checked=true;" size="20"/>
                                    <br/>
                                    <label for="confirmpassword" style="width:120px"><?= __(
                                            'global.change_password_confirm'
                                        ) ?>:</label>
                                    <input type="password" name="confirmpassword" onChange="documentdirty=true;"
                                           onKeyPress="document.userform.passwordgenmethod[1].checked=true;" size="20"/>
                                    <br/>
                                    <small>
                                        <span class="warning" style="font-weight:normal"><?= __(
                                                'global.password_gen_length'
                                            ) ?></span>
                                    </small>
                                </div>
                            </fieldset>
                            <br/>
                            <fieldset style="width:300px">
                                <legend><?= __('global.password_method'); ?></legend>
                                <input type=radio name="passwordnotifymethod" value="e"<?= get_by_key(
                                    $_POST,
                                    'passwordnotifymethod'
                                ) === 'e' ? ' checked' : ''; ?> />
                                <?= __('global.password_method_email'); ?>
                                <br/>
                                <input type=radio name="passwordnotifymethod" value="s"<?= get_by_key(
                                    $_POST,
                                    'passwordnotifymethod'
                                ) === 'e' ? '' : ' checked'; ?> />
                                <?= __('global.password_method_screen'); ?>
                            </fieldset>
                            </span></td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_full_name'); ?>:
                        </td>
                        <td>
                            <input type="text" name="fullname" class="inputBox"
                                   value="<?= e(
                                       $_POST['fullname'] ?? $userdata['fullname']
                                   ); ?>" onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_first_name'); ?>:
                        </td>
                        <td>
                            <input type="text" name="first_name" class="inputBox"
                                   value="<?= e($userdata['first_name']); ?>"
                                   onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_middle_name'); ?>:
                        </td>
                        <td>
                            <input type="text" name="middle_name" class="inputBox"
                                   value="<?= e($userdata['middle_name']); ?>"
                                   onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_last_name'); ?>:
                        </td>
                        <td>
                            <input type="text" name="last_name" class="inputBox"
                                   value="<?= e($userdata['last_name']); ?>"
                                   onChange="documentDirty=true;"/>
                        </td>
                    </tr>

                    <tr>
                        <td><?= __('global.user_email'); ?>:
                        </td>
                        <td>
                            <input type="text" name="email" class="inputBox" value="<?= $_POST['email'] ??
                                $userdata['email']; ?>" onChange="documentDirty=true;"/>
                            <input type="hidden" name="oldemail" value="<?= e(
                                !empty($userdata['oldemail']) ? $userdata['oldemail'] : $userdata['email']
                            ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_phone'); ?>:
                        </td>
                        <td>
                            <input type="text" name="phone" class="inputBox" value="<?= $_POST['phone'] ??
                                $userdata['phone']; ?>" onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_mobile'); ?>:
                        </td>
                        <td>
                            <input type="text" name="mobilephone" class="inputBox" value="<?= $_POST['mobilephone']
                                ?? $userdata['mobilephone']; ?>" onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_fax'); ?>:
                        </td>
                        <td>
                            <input type="text" name="fax" class="inputBox" value="<?= $_POST['fax'] ??
                                $userdata['fax']; ?>" onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_street'); ?>:
                        </td>
                        <td>
                            <input type="text" name="street" class="inputBox"
                                   value="<?= e($userdata['street']); ?>"
                                   onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_city'); ?>:
                        </td>
                        <td>
                            <input type="text" name="city" class="inputBox"
                                   value="<?= e($userdata['city']); ?>"
                                   onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_state'); ?>:
                        </td>
                        <td>
                            <input type="text" name="state" class="inputBox" value="<?= $_POST['state'] ??
                                $userdata['state']; ?>" onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_zip'); ?>:
                        </td>
                        <td>
                            <input type="text" name="zip" class="inputBox" value="<?= $_POST['zip'] ??
                                $userdata['zip']; ?>" onChange="documentDirty=true;"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_country'); ?>:
                        </td>
                        <td>
                            <select name="country" onChange="documentDirty=true;">
                                <?php
                                $chosenCountry = $_POST['country'] ?? $userdata['country']; ?>
                                <option value=""<?= isset($chosenCountry) ? '' : ' selected' ?> >&nbsp;
                                </option>
                                <?php
                                foreach ($_country_lang as $key => $country) {
                                    echo "<option value=\"$key\"" .
                                        (isset($chosenCountry) && $chosenCountry == $key ? ' selected' : '') .
                                        ">$country</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_dob'); ?>:
                        </td>
                        <td>
                            <input type="text" id="dob" name="dob" class="DatePicker"
                                   value="<?= $_POST['dob'] ?? ($userdata['dob'] ? evo()->toDateFormat(
                                       $userdata['dob']
                                   ) : ''); ?>" onBlur='documentDirty=true;' readonly/>
                            <i onClick="document.userform.dob.value=''; return true;"
                               class="clearDate <?= ManagerTheme::getStyle('icon_calendar_close') ?>"
                               data-tooltip="<?= __('global.remove_date'); ?>"></i>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_gender'); ?>:
                        </td>
                        <td>
                            <select name="gender" onChange="documentDirty=true;">
                                <option value=""></option>
                                <option value="1" <?= (get_by_key($_POST, 'gender') === '1' ||
                                    $userdata['gender'] == '1')
                                    ? 'selected' : ''; ?>>
                                    <?= __('global.user_male') ?>
                                </option>
                                <option value="2" <?= (get_by_key($_POST, 'gender') === '2' ||
                                    $userdata['gender'] == '2')
                                    ? 'selected' : ''; ?>>
                                    <?= __('global.user_female') ?>
                                </option>
                                <option value="3" <?= (get_by_key($_POST, 'gender') === '3' ||
                                    $userdata['gender'] == '3')
                                    ? 'selected' : '' ?>>
                                    <?= __('global.user_other') ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.comment'); ?>:
                        </td>
                        <td>
                            <textarea type="text" name="comment" class="inputBox" rows="5"
                                      onChange="documentDirty=true;"><?= e(
                                    $_POST['comment'] ?? $userdata['comment']
                                ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('global.user_verification'); ?>:</td>
                        <td>
                            <input type="checkbox" name="verified" value="1"<?= $userdata['verified'] == 1 ||
                            evo()->getManagerApi()->action == 87 ? ' checked '
                                : '' ?><?= evo()->getManagerApi()->action == 87 ? ' disabled' : '' ?>>
                        </td>
                    </tr>
                    <?php
                    if (evo()->getManagerApi()->action == '88') { ?>
                        <tr>
                            <td><?= __('global.user_logincount'); ?>:</td>
                            <td><?= $userdata['logincount'] ?></td>
                        </tr>
                        <tr>
                            <td><?= __('global.user_prevlogin'); ?>:</td>
                            <td>
                                <?= evo()->toDateFormat(
                                    $userdata['thislogin'] + evo()->getConfig('server_offset_time')
                                ) ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('global.user_failedlogincount'); ?>:</td>
                            <input type="hidden" name="failedlogincount" onChange="documentDirty=true;"
                                   value="<?= $userdata['failedlogincount']; ?>">
                            <td>
                                <span id='failed'><?= $userdata['failedlogincount'] ?></span>&nbsp;&nbsp;&nbsp;[<a
                                        href="javascript:resetFailed()"><?= __('global.reset_failedlogins') ?></a>]
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('global.user_block'); ?>:</td>
                            <td>
                                <input name="blockedcheck" type="checkbox"
                                       onClick="changeblockstate(document.userform.blockedmode, document.userform.blockedcheck);"<?= ($userdata['blocked'] ==
                                    1 ||
                                    ($userdata['blockeduntil'] > time() && $userdata['blockeduntil'] != 0) ||
                                    ($userdata['blockedafter'] < time() && $userdata['blockedafter'] != 0))
                                    ? ' checked' : ''; ?> />
                                <input type="hidden" name="blocked" value="<?= ($userdata['blocked'] == 1 ||
                                    ($userdata['blockeduntil'] > time() && $userdata['blockeduntil'] != 0)) ? 1
                                    : 0; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('global.user_blockeduntil'); ?>:</td>
                            <td>
                                <input type="text" id="blockeduntil" name="blockeduntil" class="DatePicker"
                                       value="<?= $_POST['blockeduntil'] ??
                                           ($userdata['blockeduntil'] ? evo()->toDateFormat($userdata['blockeduntil'])
                                               : ''); ?>" onBlur='documentDirty=true;' readonly/>
                                <i onClick="document.userform.blockeduntil.value=''; return true;"
                                   class="clearDate <?= ManagerTheme::getStyle('icon_calendar_close') ?>"
                                   data-tooltip="<?= __('global.remove_date'); ?>"></i>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('global.user_blockedafter'); ?>:</td>
                            <td>
                                <input type="text" id="blockedafter" name="blockedafter" class="DatePicker"
                                       value="<?= $_POST['blockedafter'] ??
                                           ($userdata['blockedafter'] ? evo()->toDateFormat($userdata['blockedafter'])
                                               : ''); ?>" onBlur='documentDirty=true;' readonly/>
                                <i onClick="document.userform.blockedafter.value=''; return true;"
                                   class="clearDate <?= ManagerTheme::getStyle('icon_calendar_close') ?>"
                                   data-tooltip="<?= __('global.remove_date'); ?>"></i>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <?php
                $tvs = SiteTmplvar::query()
                    ->select(
                        'site_tmplvars.*',
                        'user_values.value',
                        'user_role_vars.rank as tvrank',
                        'user_role_vars.rank',
                        'site_tmplvars.id',
                        'site_tmplvars.rank'
                    )
                    ->join('user_role_vars', 'user_role_vars.tmplvarid', '=', 'site_tmplvars.id')
                    ->leftJoin('user_values', function ($query) use ($user) {
                        $query->on('user_values.userid', '=', DB::raw($user));
                        $query->on('user_values.tmplvarid', '=', 'site_tmplvars.id');
                    });
                $group_tvs = evo()->getConfig('group_tvs');
                $templateVariables = '';
                $templateVariablesOutput = '';
                if ($group_tvs) {
                    $tvs = $tvs->select(
                        'site_tmplvars.*',
                        'user_values.value',
                        'categories.id as category_id',
                        'categories.category as category_name',
                        'categories.rank as category_rank',
                        'user_role_vars.rank',
                        'site_tmplvars.id',
                        'site_tmplvars.rank'
                    );
                    $tvs = $tvs->leftJoin('categories', 'categories.id', '=', 'site_tmplvars.category');
                    //$sort = 'category_rank,category_id,' . $sort;
                    $tvs = $tvs->orderBy('category_rank', 'ASC');
                    $tvs = $tvs->orderBy('category_id', 'ASC');
                }
                $tvs = $tvs->orderBy('user_role_vars.rank', 'ASC');
                $tvs = $tvs->orderBy('site_tmplvars.rank', 'ASC');
                $tvs = $tvs->orderBy('site_tmplvars.id', 'ASC');
                $tvs = $tvs->where('user_role_vars.roleid', $userdata['role']);
                $tvs = $tvs->get();
                if (count($tvs) > 0) {
                    $tvsArray = $tvs->toArray();

                    $templateVariablesOutput = '';
                    $templateVariablesGeneral = '';

                    $i = $ii = 0;
                    $tab = '';
                    foreach ($tvsArray as $row) {
                        $row['category'] = $row['category_name'] ?? '';
                        if (!isset($row['category_id'])) {
                            $row['category_id'] = 0;
                            $row['category'] = __('global.no_category');
                            $row['category_rank'] = 0;
                        }
                        if ($row['value'] == '') {
                            $row['value'] = $row['default_text'];
                        }

                        if ($group_tvs && $row['category_id'] != 0) {
                            $ii = 0;
                            if ($tab !== $row['category_id']) {
                                if ($group_tvs == 1 || $group_tvs == 3) {
                                    if ($i === 0) {
                                        $templateVariablesOutput .= '
                            <div class="tab-section" id="tabTV_' . $row['category_id'] . '">
                                <div class="tab-header">' . e($row['category']) . '</div>
                                <div class="tab-body tmplvars">
                                    <table>' . "\n";
                                    } else {
                                        $templateVariablesOutput .= '
                                    </table>
                                </div>
                            </div>

                            <div class="tab-section" id="tabTV_' . $row['category_id'] . '">
                                <div class="tab-header">' . e($row['category']) . '</div>
                                <div class="tab-body tmplvars">
                                    <table>';
                                    }
                                } else {
                                    if ($group_tvs == 2 || $group_tvs == 4) {
                                        if ($i === 0) {
                                            $templateVariablesOutput .= '
                            <div id="tabTV_' . $row['category_id'] . '" class="tab-page tmplvars">
                                <h2 class="tab">' . e($row['category']) . '</h2>
                                <script>tpTemplateVariables.addTabPage(document.getElementById(\'tabTV_' .
                                                $row['category_id'] . '\'));</script>

                                <div class="tab-body tmplvars">
                                    <table>';
                                        } else {
                                            $templateVariablesOutput .= '
                                    </table>
                                </div>
                            </div>

                            <div id="tabTV_' . $row['category_id'] . '" class="tab-page tmplvars">
                                <h2 class="tab">' . e($row['category']) . '</h2>
                                <script>tpTemplateVariables.addTabPage(document.getElementById(\'tabTV_' .
                                                $row['category_id'] . '\'));</script>

                                <div class="tab-body tmplvars">
                                    <table>';
                                        }
                                    } else {
                                        if ($group_tvs == 5) {
                                            if ($i === 0) {
                                                $templateVariablesOutput .= '
                                <div id="tabTV_' . $row['category_id'] . '" class="tab-page tmplvars">
                                    <h2 class="tab">' . e($row['category']) . '</h2>
                                    <script>tpSettings.addTabPage(document.getElementById(\'tabTV_' .
                                                    $row['category_id'] . '\'));</script>
                                    <table>';
                                            } else {
                                                $templateVariablesOutput .= '
                                    </table>
                                </div>

                                <div id="tabTV_' . $row['category_id'] . '" class="tab-page tmplvars">
                                    <h2 class="tab">' . e($row['category']) . '</h2>
                                    <script>tpSettings.addTabPage(document.getElementById(\'tabTV_' .
                                                    $row['category_id'] . '\'));</script>

                                    <table>';
                                            }
                                        }
                                    }
                                }
                                $split = 0;
                            } else {
                                $split = 1;
                            }
                        }

                        // Go through and display all Template Variables
                        if ($row['type'] == 'richtext' || $row['type'] == 'htmlarea') {
                            // determine TV-options
                            $tvOptions = evo()->parseProperties($row['elements']);
                            if (!empty($tvOptions)) {
                                // Allow different Editor with TV-option {"editor":"CKEditor4"} or &editor=Editor;text;CKEditor4
                                $editor = $tvOptions['editor'] ?? evo()->getConfig('which_editor');
                            };
                            // Add richtext editor to the list
                            $richtexteditorIds[$editor][] = 'tv' . $row['id'];
                            $richtexteditorOptions[$editor]['tv' . $row['id']] = $tvOptions;
                        }

                        $templateVariablesTmp = '';

                        // splitter
                        if ($group_tvs) {
                            if ((!empty($split) && $i) || $ii) {
                                $templateVariablesTmp .= '
                                            <tr><td colspan="2"><div class="split"></div></td></tr>' . "\n";
                            }
                        } else {
                            if ($i) {
                                $templateVariablesTmp .= '
                                        <tr><td colspan="2"><div class="split"></div></td></tr>' . "\n";
                            }
                        }

                        // post back value
                        if (array_key_exists('tv' . $row['id'], $_POST)) {
                            if (is_array($_POST['tv' . $row['id']])) {
                                $tvPBV = implode('||', $_POST['tv' . $row['id']]);
                            } else {
                                $tvPBV = $_POST['tv' . $row['id']];
                            }
                        } else {
                            $tvPBV = $row['value'];
                        }

                        $tvDescription =
                            (!empty($row['description'])) ? '<br /><span class="comment">' . e($row['description']) .
                                '</span>' : '';
                        $tvInherited =
                            (substr($tvPBV, 0, 8) == '@INHERIT') ? '<br /><span class="comment inherited">(' .
                                __('global.tmplvars_inherited') . ')</span>' : '';
                        $tvName = '<br/><small class="protectedNode">[*' . e($row['name']) . '*]</small>';

                        $templateVariablesTmp .= '
                                        <tr>
                                            <td><span class="warning">' . e($row['caption']) . $tvName . '</span>' .
                            $tvDescription . $tvInherited . '</td>
                                            <td><div style="position:relative;">' .
                            renderFormElement(
                                $row['type'],
                                $row['id'],
                                $row['default_text'],
                                $row['elements'],
                                $tvPBV,
                                '',
                                $row,
                                $tvsArray ?? [],
                                $userdata,
                                evo()->parseProperties($row['properties'], $row['name'], 'tv')
                            ) .
                            '</div></td>
                                        </tr>';

                        if ($group_tvs && $row['category_id'] == 0) {
                            $templateVariablesGeneral .= $templateVariablesTmp;
                            $ii++;
                        } else {
                            $templateVariablesOutput .= $templateVariablesTmp;
                            $tab = $row['category_id'];
                            $i++;
                        }
                    }

                    if ($templateVariablesGeneral) {
                        echo '<table id="tabTV_0" class="tmplvars"><tbody>' . $templateVariablesGeneral .
                            '</tbody></table>';
                    }

                    $templateVariables .= '
                        <!-- Template Variables -->' . "\n";
                    if (!$group_tvs) {
                        $templateVariables .= '
                                    <div class="sectionHeader" id="tv_header">' .
                            __('global.settings_templvars') . '</div>
                                        <div class="sectionBody tmplvars">
                                            <table>';
                    } else {
                        if ($group_tvs == 2) {
                            $templateVariables .= '
                    <div class="tab-section">
                        <div class="tab-header" id="tv_header">' . __('global.settings_templvars') . '</div>
                        <div class="tab-pane" id="paneTemplateVariables">
                            <script>
                                tpTemplateVariables = new WebFXTabPane(document.getElementById(\'paneTemplateVariables\'), ' .
                                (evo()->getConfig('remember_last_tab') ? 'true' : 'false') . ')
                            </script>';
                        } else {
                            if ($group_tvs == 3) {
                                $templateVariables .= '
                        <div id="templateVariables" class="tab-page tmplvars">
                            <h2 class="tab">' . __('global.settings_templvars') . '</h2>
                            <script>tpSettings.addTabPage(document.getElementById(\'templateVariables\'));</script>';
                            } else {
                                if ($group_tvs == 4) {
                                    $templateVariables .= '
                    <div id="templateVariables" class="tab-page tmplvars">
                        <h2 class="tab">' . __('global.settings_templvars') . '</h2>
                        <script>tpSettings.addTabPage(document.getElementById(\'templateVariables\'));</script>
                        <div class="tab-pane" id="paneTemplateVariables">
                            <script>
                                tpTemplateVariables = new WebFXTabPane(document.getElementById(\'paneTemplateVariables\'), ' .
                                        (evo()->getConfig('remember_last_tab') ? 'true' : 'false') . ')
                            </script>';
                                }
                            }
                        }
                    }
                    if ($templateVariablesOutput) {
                        $templateVariables .= $templateVariablesOutput;
                        $templateVariables .= '
                                    </table>
                                </div>' . "\n";
                        if ($group_tvs == 1) {
                            $templateVariables .= '
                            </div>' . "\n";
                        } else {
                            if ($group_tvs == 2 || $group_tvs == 4) {
                                $templateVariables .= '
                            </div>
                        </div>
                    </div>' . "\n";
                            } else {
                                if ($group_tvs == 3) {
                                    $templateVariables .= '
                            </div>
                        </div>' . "\n";
                                }
                            }
                        }
                    }
                    $templateVariables .= '
                        <!-- end Template Variables -->' . "\n";
                }

                // Template Variables
                if ($group_tvs < 3 && $templateVariablesOutput) {
                    echo $templateVariables;
                }
                ?>
            </div>
            <?php
            //Template Variables
            if ($group_tvs > 2 && $templateVariablesOutput) {
                echo $templateVariables;
            }
            ?>

            <!-- Settings -->
            <div class="tab-page" id="tabSettings">
                <h2 class="tab"><?= __('global.settings_users') ?></h2>
                <script>tpUser.addTabPage(document.getElementById('tabSettings'))</script>
                <table border="0" cellspacing="0" cellpadding="3">
                    <tr>
                        <td><?= __('global.language_title') ?></td>
                        <td>
                            <select name="manager_language" class="inputBox" onChange="documentDirty=true">
                                <option value=""></option>
                                <?php
                                $activelang =
                                    !empty($usersettings['manager_language']) ? $usersettings['manager_language'] : '';
                                $dir = dir(EVO_CORE_PATH . 'lang');
                                while ($file = $dir->read()) {
                                    if (is_dir(EVO_CORE_PATH . 'lang/' . $file) && ($file != '.' && $file != '..')) {
                                        $selectedtext = $file == $activelang ? 'selected' : '';
                                        ?>
                                        <option value="<?= $file; ?>" <?= $selectedtext; ?>><?= ucwords(
                                                str_replace('_', ' ', $file)
                                            ); ?></option>
                                        <?php
                                    }
                                }

                                $dir->close();
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.language_message') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.mgr_login_start') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='50'
                                   name="manager_login_startup" value="<?= $_POST['manager_login_startup']
                                ?? ($usersettings['manager_login_startup'] ?? '') ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.mgr_login_start_message') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.login_homepage') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='50' name="login_home"
                                   value="<?= $_POST['login_home'] ?? $usersettings['login_home'] ?? '' ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.allow_mgr_access_message') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.login_allowed_ip') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type="text" maxlength='255' style="width: 300px;"
                                   name="allowed_ip" value="<?= $usersettings['allowed_ip']
                                ?? ''; ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.login_allowed_ip_message') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.login_allowed_days') ?></td>
                        <td>
                            <label>
                                <?php
                                if (!isset($usersettings['allowed_days'])) {
                                    $usersettings['allowed_days'] = '';
                                }
                                ?>
                                <input onChange="documentDirty=true;" type="checkbox" name="allowed_days[]"
                                       value="1" <?= strpos($usersettings['allowed_days'], '1') !== false
                                    ? 'checked' : ''; ?> />
                                <?= __('global.sunday'); ?>
                            </label>
                            <br/>
                            <label>
                                <input onChange="documentDirty=true;" type="checkbox" name="allowed_days[]"
                                       value="2" <?= strpos($usersettings['allowed_days'], '2') !== false
                                    ? 'checked' : ''; ?> />
                                <?= __('global.monday'); ?></label>
                            <br/>
                            <label>
                                <input onChange="documentDirty=true;" type="checkbox" name="allowed_days[]"
                                       value="3" <?= strpos($usersettings['allowed_days'], '3') !== false
                                    ? 'checked' : ''; ?> />
                                <?= __('global.tuesday'); ?></label>
                            <br/>
                            <label>
                                <input onChange="documentDirty=true;" type="checkbox" name="allowed_days[]"
                                       value="4" <?= strpos($usersettings['allowed_days'], '4') !== false
                                    ? 'checked' : ''; ?> />
                                <?= __('global.wednesday'); ?></label>
                            <br/>
                            <label>
                                <input onChange="documentDirty=true;" type="checkbox" name="allowed_days[]"
                                       value="5" <?= strpos($usersettings['allowed_days'], '5') !== false
                                    ? 'checked' : ''; ?> />
                                <?= __('global.thursday'); ?></label>
                            <br/>
                            <label>
                                <input onChange="documentDirty=true;" type="checkbox" name="allowed_days[]"
                                       value="6" <?= strpos($usersettings['allowed_days'], '6') !== false
                                    ? 'checked' : ''; ?> />
                                <?= __('global.friday'); ?></label>
                            <br/>
                            <label>
                                <input onChange="documentDirty=true;" type="checkbox" name="allowed_days[]"
                                       value="7" <?= strpos($usersettings['allowed_days'], '7') !== false
                                    ? 'checked' : ''; ?> />
                                <?= __('global.saturday'); ?></label>
                            <br/></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.login_allowed_days_message') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.manager_theme') ?></td>
                        <td>
                            <select name="manager_theme" class="inputBox"
                                    onChange="documentDirty=true;document.userform.theme_refresher.value = Date.parse(new Date());">
                                <option value=""></option>
                                <?php
                                $dir = dir('media/style/');
                                while ($file = $dir->read()) {
                                    if ($file != "." && $file != '..' && is_dir('media/style/' . $file) &&
                                        substr($file, 0, 1) != '.'
                                    ) {
                                        $themename = $file;
                                        if ($themename === 'common') {
                                            continue;
                                        }
                                        $attr = 'value="' . $themename . '" ';
                                        if (isset($usersettings['manager_theme']) &&
                                            $themename == $usersettings['manager_theme']
                                        ) {
                                            $attr .= 'selected ';
                                        }
                                        echo "\t\t<option " . rtrim($attr) . '>' .
                                            ucwords(str_replace('_', ' ', $themename)) . "</option>\n";
                                    }
                                }
                                $dir->close();
                                ?>
                            </select>
                            <input type="hidden" name="theme_refresher" value="">
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.manager_theme_message') ?></td>
                    </tr>

                    <tr>
                        <td nowrap class="warning"><?= __('global.manager_theme_mode') ?><br>
                            <small>[(manager_theme_mode)]</small>
                        </td>
                        <td>
                            <label><input type="radio" name="manager_theme_mode" value="" <?= evo()->getConfig(
                                    'manager_theme_mode'
                                ) === 0 ? 'checked' : '' ?> />
                                <?= __('global.option_default') ?></label>
                            <br/>

                            <label><input type="radio" name="manager_theme_mode" value="1" <?= evo()->getConfig(
                                    'manager_theme_mode'
                                ) === 1 ? 'checked' : '' ?> />
                                <?= __('global.manager_theme_mode1') ?></label>
                            <br/>
                            <label><input type="radio" name="manager_theme_mode" value="2" <?= evo()->getConfig(
                                    'manager_theme_mode'
                                ) === 2 ? 'checked' : '' ?> />
                                <?= __('global.manager_theme_mode2') ?></label>
                            <br/>
                            <label><input type="radio" name="manager_theme_mode" value="3" <?= evo()->getConfig(
                                    'manager_theme_mode'
                                ) === 3 ? 'checked' : '' ?> />
                                <?= __('global.manager_theme_mode3') ?></label>
                            <br/>
                            <label><input type="radio" name="manager_theme_mode" value="4" <?= (evo()->getConfig(
                                        'manager_theme_mode'
                                    ) === 4) ? 'checked' : '' ?> />
                                <?= __('global.manager_theme_mode4') ?></label>
                        </td>
                    </tr>

                    <tr>
                        <td><?= __('global.which_browser_title') ?></td>
                        <td>
                            <select name="which_browser" class="inputBox" onChange="documentDirty=true;">
                                <?php
                                $selected = $usersettings['which_browser'] ?? '';
                                echo '<option value="default"' . $selected . '>' .
                                    __('global.option_default') . "</option>\n";
                                foreach (glob('media/browser/*', GLOB_ONLYDIR) as $dir) {
                                    $dir = str_replace('\\', '/', $dir);
                                    $browser_name = substr($dir, strrpos($dir, '/') + 1);
                                    $selected = $usersettings['which_browser'] ?? '';
                                    echo '<option value="' . $browser_name . '"' . $selected . '>' .
                                        "$browser_name</option>\n";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.which_browser_msg') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.filemanager_path_title') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='255' style="width: 300px;"
                                   name="filemanager_path" value="<?= e(
                                $usersettings['filemanager_path'] ?? ''
                            ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.filemanager_path_message') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.uploadable_images_title') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='255' name="upload_images"
                                   value="<?= $usersettings['upload_images']
                                       ?? ''; ?>">
                            &nbsp;&nbsp;
                            <input onChange="documentDirty=true;" type="checkbox" name="default_upload_images"
                                   value="1" <?= isset($usersettings['upload_images']) &&
                            $usersettings['upload_images'] != '' ? ''
                                : 'checked'; ?> />
                            <?= __('global.user_use_config'); ?>
                            <br/>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.uploadable_images_message') .
                            __('global.user_upload_message') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.uploadable_media_title') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='255' name="upload_media"
                                   value="<?= $usersettings['upload_media']
                                       ?? ''; ?>">
                            &nbsp;&nbsp;
                            <input onChange="documentDirty=true;" type="checkbox" name="default_upload_media"
                                   value="1" <?= isset($usersettings['upload_media']) &&
                            $usersettings['upload_media'] != '' ? ''
                                : 'checked'; ?> />
                            <?= __('global.user_use_config'); ?>
                            <br/>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.uploadable_media_message') .
                            __('global.user_upload_message') ?></td>
                    </tr>
                    <tr>
                        <td><?= __('global.uploadable_files_title') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='255' name="upload_files"
                                   value="<?= $usersettings['upload_files']
                                       ?? ''; ?>">
                            &nbsp;&nbsp;
                            <input onChange="documentDirty=true;" type="checkbox" name="default_upload_files"
                                   value="1" <?= isset($usersettings['upload_files']) &&
                            $usersettings['upload_files'] != '' ? ''
                                : 'checked'; ?> />
                            <?= __('global.user_use_config'); ?>
                            <br/>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.uploadable_files_message') .
                            __('global.user_upload_message') ?></td>
                    </tr>
                    <tr class='row2'>
                        <td><?= __('global.upload_maxsize_title') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='255' style="width: 300px;"
                                   name="upload_maxsize" value="<?= $usersettings['upload_maxsize']
                                ?? ''; ?>">
                        </td>
                    </tr>
                    <tr class='row2'>
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.upload_maxsize_message') ?></td>
                    </tr>
                    <tr id="editorRow0"
                        style="display: <?= evo()->getConfig('use_editor') === true ? $displayStyle : 'none'; ?>">
                        <td><?= __('global.which_editor_title') ?></td>
                        <td>
                            <select name="which_editor" onChange="documentDirty=true;">
                                <option value=""></option>
                                <?php
                                $edt = $usersettings['which_editor'] ?? '';
                                // invoke OnRichTextEditorRegister event
                                $evtOut = evo()->invokeEvent('OnRichTextEditorRegister');
                                echo '<option value="none"' . ($edt == 'none' ? ' selected' : '') . '>' .
                                    __('global.none') . "</option>\n";
                                if (is_array($evtOut)) {
                                    for ($i = 0; $i < count($evtOut); $i++) {
                                        $editor = $evtOut[$i];
                                        echo "<option value='$editor'" .
                                            ($edt == $editor ? ' selected' : '') . ">$editor</option>\n";
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr id="editorRow1"
                        style="display: <?= evo()->getConfig('use_editor') === true ? $displayStyle : 'none'; ?>">
                        <td>&nbsp;</td>
                        <td class='comment'>
                            <?= __('global.which_editor_message') ?>
                        </td>
                    </tr>
                    <tr id='editorRow14' class="row3"
                        style="display: <?= evo()->getConfig('use_editor') === true ? $displayStyle : 'none'; ?>">
                        <td><?= __('global.editor_css_path_title') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='255' name="editor_css_path"
                                   value="<?= $usersettings['editor_css_path'] ?? ''; ?>"/>
                        </td>
                    </tr>
                    <tr id="editorRow15" class="row3"
                        style="display: <?= evo()->getConfig('use_editor') === true ? $displayStyle : 'none'; ?>">
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.editor_css_path_message') ?></td>
                    </tr>
                    <tr id="rbRow1" class="row3"
                        style="display: <?= evo()->getConfig('use_browser') === true ? $displayStyle : 'none'; ?>">
                        <td><?= __('global.rb_base_dir_title') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='255' style="width: 300px;"
                                   name="rb_base_dir" value="<?= $usersettings['rb_base_dir'] ?? '' ?>"/>
                        </td>
                    </tr>
                    <tr id="rbRow2" class="row3"
                        style="display: <?= evo()->getConfig('use_browser') === true ? $displayStyle : 'none'; ?>">
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.rb_base_dir_message') ?></td>
                    </tr>
                    <tr id="rbRow4" class="row3"
                        style="display: <?= evo()->getConfig('use_browser') === true ? $displayStyle : 'none'; ?>">
                        <td><?= __('global.rb_base_url_title') ?></td>
                        <td>
                            <input onChange="documentDirty=true;" type='text' maxlength='255' style="width: 300px;"
                                   name="rb_base_url" value="<?= $usersettings['rb_base_url'] ?? '' ?>"/>
                        </td>
                    </tr>
                    <tr id='rbRow5' class='row3'
                        style="display: <?= evo()->getConfig('use_browser') === true ? $displayStyle : 'none'; ?>">
                        <td>&nbsp;</td>
                        <td class='comment'><?= __('global.rb_base_url_message') ?></td>
                    </tr>
                </table>
                <?php
                // invoke OnInterfaceSettingsRender event
                $evtOut = evo()->invokeEvent('OnInterfaceSettingsRender');
                if (is_array($evtOut)) {
                    echo implode('', $evtOut);
                }
                ?>
            </div>
            <!-- Photo -->
            <div class="tab-page" id="tabPhoto">
                <h2 class="tab"><?= __('global.settings_photo') ?></h2>
                <script>tpUser.addTabPage(document.getElementById('tabPhoto'))</script>
                <?php
                $out = '';
                if (isset($_POST['photo']) && is_scalar($_POST['photo'])) {
                    if (preg_match('#^https?://#i', $_POST['photo']) == false) {
                        $out = MODX_SITE_URL;
                    }
                    $out .= $_POST['photo'];
                } else {
                    if (!empty($userdata['photo'])) {
                        if (preg_match('#^https?://#i', $userdata['photo']) == false) {
                            $out = MODX_SITE_URL;
                        }
                        $out .= $userdata['photo'];
                    } else {
                        $out = ManagerTheme::getStyle('tx');
                    }
                }
                ?>
                <div class='comment'><?= __('global.user_photo_message') ?></div>
                <input type="text" id="photo" name="photo" value="<?= e(
                    $_POST['photo'] ?? $userdata['photo']
                ); ?>" onchange="documentDirty=true;"/>
                <input type="button"
                       value="<?= __('global.insert') ?>"
                       onclick="BrowseServer('photo')"/>
                <div class="col-12" style="padding-left: 0;">
                    <div id="image_for_photo" class="image_for_field"
                         data-image="<?= e($out); ?>"
                         onclick="BrowseServer('photo')"
                         style="background-image: url('<?= e($out) ?>');"></div>
                    <script>document.getElementById('photo').
                        addEventListener('change', evoRenderTvImageCheck, false)</script>
                </div>
            </div>
            <?php
            $groupsarray = [];

            if (evo()->getManagerApi()->action == '88') { // only do this bit if the user is being edited
                $groupsarray = MemberGroup::query()->where('member', $user)->pluck('user_group')->toArray();
            }
            // retain selected user groups between post
            if (isset($_POST['user_groups']) && is_array($_POST['user_groups'])) {
                foreach ($_POST['user_groups'] as $n => $v) {
                    $groupsarray[] = $v;
                }
            }
            ?>
            <div class="tab-page" id="tabPermissions">
                <h2 class="tab"><?= __('global.web_access_permissions') ?></h2>
                <script>tpUser.addTabPage(document.getElementById('tabPermissions'))</script>
                <p><a href="javascript:;"
                      onclick="document.getElementsByName('user_groups[]').forEach(e => e.checked ^= 1) ; return false;"><?= __(
                            'global.access_permissions_user_toggle'
                        ) ?></a></p>
                <p><?= __('global.access_permissions_user_message') ?></p>
                <?php
                $webgroupnames = MembergroupName::query()->orderBy('name')->get();
                foreach ($webgroupnames->toArray() as $row) {
                    echo '<label><input type="checkbox" name="user_groups[]" value="' . $row['id'] . '"' .
                        (in_array($row['id'], $groupsarray) ? ' checked' : '') . ' />' . e($row['name']) .
                        '</label><br />';
                }
                ?>
            </div>
            <?php
            // invoke OnWUsrFormRender event
            $evtOut = evo()->invokeEvent('OnUserFormRender', [
                'id' => $user,
            ]);
            if (is_array($evtOut)) {
                echo implode('', $evtOut);
            }
            ?>
        </div>
    </div>
    <input type="submit" name="save" style="display:none">
</form>
<script>
  storeCurRole()
</script>
