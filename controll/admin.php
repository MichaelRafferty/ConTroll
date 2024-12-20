<?php
global $db_ini;
require_once "lib/base.php";
require_once "lib/sets.php";
//initialize google session
$need_login = google_init("page");

$page = "admin";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

if (array_key_exists('user_id', $_SESSION)) {
    $user_id = $_SESSION['user_id'];
} else {
    bounce_page('index.php');
    return;
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'],
                    //$cdn['tabbs5'],
                    'css/base.css',
                   ),
    /* js  */ array( //$cdn['luxon'],
                    $cdn['tabjs'],
                    'js/admin.js',
                   ),
              $need_login);
$con = get_conf("con");
$conid=$con['id'];
$debug = get_conf('debug');

if (array_key_exists('controll_admin', $debug))
    $debug_admin=$debug['controll_admin'];
else
    $debug_admin = 0;

?>
<div id='parameters' <?php if (!($debug_admin & 4)) echo 'hidden'; ?>>
    <div id="debug"><?php echo $debug_admin; ?></div>
    <div id="conid"><?php echo $conid; ?></div>
</div>
<div id='user-lookup' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Lookup Person to Add as User' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='addTitle'>Lookup Person to Add as User</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id='add-search' action='javascript:void(0)'>
                        <div class='row p-1'>
                            <div class='col-sm-3 p-0'>
                                <label for='add_name_search' id='addName'>Name:</label>
                            </div>
                            <div class='col-sm-9 p-0'>
                                <input class='form-control-sm' type='text' name='namesearch' id='add_name_search' size='64'
                                       placeholder='Name/Portion of Name, Person (Badge) ID'/>
                            </div>
                            <div class='row mt-3'>
                                <div class='col-sm-12 text-bg-secondary'>
                                    Search Results
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-12' id='add_search_results'>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='addSearch' onClick='add_find()'>Find Person</button>
            </div>
            <div id='result_message_user' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<div id='main'>
    <?php
    $userQ = "Select name, email, id, perid from user;";
    $userR = dbQuery($userQ);

    $authQ = "Select name, id from auth;";
    $authR = dbQuery($authQ);

    $auth_set = array(); $auth_num = array();
    while($auth = $authR->fetch_assoc()) {
        $auth_set[$auth['name']] = $auth['id'];
        $auth_num[$auth['id']] = $auth['name'];
    }

    $pairQ = "SELECT * from user_auth;";
    $pairR = dbQuery($pairQ);
    $user_auth = array();
    while($pair = $pairR->fetch_assoc()) {
        $user_auth[$pair['user_id']][$pair['auth_id']] = true;
    }


    $sets = get_admin_sets();
    ?>
    <ul class="nav nav-tabs mb-3" id="admin-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="users-tab" data-bs-toggle="pill" data-bs-target="#users-pane" type="button" role="tab" aria-controls="nav-users"
                    aria-selected="true" onclick="settab('users-pane');">Users
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='menu-tab' data-bs-toggle='pill' data-bs-target='#menu-pane' type='button' role='tab' aria-controls='nav-menu'
                    aria-selected='false' onclick="settab('menu-pane');">Main Menu
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='keys-tab' data-bs-toggle='pill' data-bs-target='#keys-pane' type='button' role='tab' aria-controls='nav-menu'
                    aria-selected='false' onclick="settab('keys-pane');">Oauth2 Client Keys
            </button>
        </li>
    </ul>
    <div class="tab-content ms-2" id="admin-content">
        <div class="tab-pane fade show active" id="users-pane" role="tabpanel" aria-labelledby="users-tab" tabindex="0">
            <table>
                <thead>
                    <tr>
                        <th>User</th><th>Email</th><th style="text-align: right; padding-left: 4px; padding-right: 4px;">Perid</th>
                        <?php

    $sets_num = array();
    foreach ($sets as $group => $perms) {
        $dispgroup = str_replace('_', '-', $group);
                        ?><th style="overflow-wrap:normal; width: 50px;padding-left: 2px; padding-right: 2px;">
                            <?php echo $dispgroup;?>
                        </th>
                        <?php
        $perm_num = array();
        foreach ($perms as $perm) {
            if (array_key_exists($perm, $auth_set)) {
                $perm_num[] = $auth_set[$perm];
            } else {
                $perm_num[] = false;
            }
        }
        $sets_num[$group] = $perm_num;
    }
                        ?>
                        <tr></tr>
                </thead>
                <tbody>
                    <?php
    while($user = $userR->fetch_assoc()) {
        $lookup_str = '';
        if ((!array_key_exists('perid', $user)) || $user['perid'] === null || $user['perid'] == '') {
            $updateFcn = 'updatePerid';
            $color = " background-color: red;";
            $updateLabel = "Fix Perid";
            $names = explode(' ', $user['name']);
            foreach ($names as $name) {
                $lookup_str .= ' ' . mb_substr($name, 0, 2);
            }
        } else {
            $updateFcn = 'updatePermissions';
            $updateLabel = "Update";
            $color = '';
        }
                    ?>
                    <tr>
                        <form id='<?php echo $user['id'];?>' action='javascript:void(0)'>
                            <input type='hidden' name='user_id' value='<?php echo $user['id'];?>' />
                            <input type='hidden' name='perid' value='<?php echo $user['perid'];?>' />
                            <?php
                            ?>
                            <td>
                                <?php echo $user['name'];?>
                            </td>
                            <td>
                                <?php echo $user['email']; ?>
                            </td>
                            <td style="text-align: right; padding-left: 4px; padding-right: 4px;<?php echo $color; ?>">
                                <?php echo $user['perid']; ?>
                            </td>
                            <?php
        foreach($sets_num as $n => $set) {
            $a = false;  // start as false, if there are no items in the set as a safeguard
            foreach ($set as $value) {
                //web_error_log("userid = ". $user['id'] . " and value = $value"); 
                if (array_key_exists($user['id'], $user_auth) && ($value != '') 
                    && array_key_exists($value, $user_auth[$user['id']])) {
                    $a = true;  // first granted perm will set it to true
                } else {
                    $a = false;  // first not granted perm will clear it to false and end the looping over the set
                    break;
                }
            }
                            ?>
                            <td>
                                <input form='<?php echo $user['id']; 
            ?>'
                                    type='checkbox' name='<?php echo $n;?>'
                                    <?php
            if($a) { echo "checked='checked'"; }
            if ($user_id == $user['id'] && $n == 'admin') { echo "onclick='return false;'"; } // prevent you from deleting your own admin setting
            ?> />
                            </td>
                            <?php
        }
                            ?>
                            <td>
                                <input form='<?php echo $user['id']; ?>'
                                    type='submit' onclick='<?php echo $updateFcn . "(" . $user['id'] . ',"' . trim($lookup_str) . '")'; ?>' value='<?php echo $updateLabel;?>' />
                                <?php if ($user['id'] != $user_id) { ?>
                                <input form='<?php echo $user['id']; ?>'
                                    type='button' onclick='clearPermissions("<?php echo $user['id']; ?>")' value='Delete' />
                                <?php } ?>
                            </td>
                        </form>
                    </tr>
                    <?php
    }
                    ?>
                </tbody>
            </table>
            <button id='add_new_account' type='button' class='btn btn-primary btn-sm' onclick="addFindPerson(); return false;">New Account</button>
        </div>
        <div class='tab-pane fade' id='menu-pane' role='tabpanel' aria-labelledby='menu-tab' tabindex='0'>
            <div class='container-fluid'>
                <div class='row'>
                    <div class='col-sm-auto'>
                        <h1 class="h4">Drag and drop menu items to re-order main menu</h1>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-auto" id="menuTableDiv"></div>
                </div>
                <div class='row mt-2'>
                    <div class='col-sm-auto'>
                        <button id='menu-undo' type='button' class='btn btn-secondary btn-sm' onclick='undoMenu(); return false;' disabled>Undo</button>
                        <button id='menu-redo' type='button' class='btn btn-secondary btn-sm' onclick='redoMenu(); return false;' disabled>Redo</button>
                        <button id='menu-save' type='button' class='btn btn-primary btn-sm' onclick='saveMenu(); return false;' disabled>Save Changes</button>
                    </div>
                </div>
        </div>
        <div class='tab-pane fade' id='keys-pane' role='tabpanel' aria-labelledby='keys-tab' tabindex='0'>
            <h1 class='h4'>ConTroll Oauth2 Clients Configuration</h1>
            <div class='row'>
                <div class='col-sm-12' id='clientsTableDiv'></div>
            </div>
        </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
</div>
<script>
    $(function() {
        $('#createDialog').dialog({
            title: "New Account",
            autoOpen: false,
            width: 400,
            height: 250,
            modal: true
        });
    });
</script>
<pre id='test'></pre>
<?php
page_foot($page);
?>
