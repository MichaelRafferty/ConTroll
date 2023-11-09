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

page_init($page,
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css',
                    //'https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator_bootstrap5.min.css',
                    'css/base.css',
                   ),
    /* js  */ array( //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js',
                    'js/base.js',
                    'js/admin.js',
                    'js/admin_consetup.js',
                    'js/admin_memconfig.js',
                    'js/admin_merge.js'
                   ),
              $need_login);
$con = get_conf("con");
$conid=$con['id'];
?>
<div id='merge-lookup' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Look up Merge Person' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='mergeTitle'>Lookup Person for Merge</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id='merge-search' action='javascript:void(0)'>
                        <div class='row p-1'>
                            <div class='col-sm-3 p-0'>
                                <label for='search_name' id="mergeName">Merge Name:</label>
                            </div>
                            <div class='col-sm-9 p-0'>
                                <input class='form-control-sm' type='text' name='namesearch' id='merge_name_search' size='64'
                                       placeholder='Name/Portion of Name, Person (Badge) ID'/>
                            </div>
                            <div class='row mt-3'>
                                <div class='col-sm-12 text-bg-secondary'>
                                    Search Results
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-12' id='merge_search_results'>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='mergeSearch' onClick='merge_find()'>Find Person</button>
            </div>
            <div id='result_message' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<div id='main'>

    <?php
    $userQ = "Select name, email, id from user;";
    $userR = dbQuery($userQ);

    $authQ = "Select name, id from auth;";
    $authR = dbQuery($authQ);

    $auth_set = array(); $auth_num = array();
    while($auth = fetch_safe_assoc($authR)) {
        $auth_set[$auth['name']] = $auth['id'];
        $auth_num[$auth['id']] = $auth['name'];
    }

    $pairQ = "SELECT * from user_auth;";
    $pairR = dbQuery($pairQ);
    $user_auth = array();
    while($pair = fetch_safe_assoc($pairR)) {
        $user_auth[$pair['user_id']][$pair['auth_id']] = true;
    }


    $sets = get_admin_sets();
    ?>
    <ul class="nav nav-tabs mb-3" id="admin-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="users-tab" data-bs-toggle="pill" data-bs-target="#users-pane" type="button" role="tab" aria-controls="nav-users" aria-selected="true" onclick="settab('users-pane');">Users</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="consetup-tab" data-bs-toggle="pill" data-bs-target="#consetup-pane" type="button" role="tab" aria-controls="nav-consetup" aria-selected="false" onclick="settab('consetup-pane');">Current Convention Setup</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="nextconsetup-tab" data-bs-toggle="pill" data-bs-target="#nextconsetup-pane" type="button" role="tab" aria-controls="nav-nextconsetup" aria-selected="false" onclick="settab('nextconsetup-pane');">Next Convention Setup</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="memconfig-tab" data-bs-toggle="pill" data-bs-target="#memconfig-pane" type="button" role="tab" aria-controls="nav-nextconsetup" aria-selected="false" onclick="settab('memconfig-pane');">Membership Configuration Tables</button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='merge-tab' data-bs-toggle='pill' data-bs-target='#merge-pane' type='button' role='tab' aria-controls='nav-merge' aria-selected='false' onclick="settab('merge-pane');">Merge People
            </button>
        </li>
    </ul>
    <div class="tab-content" id="admin-content">
        <div class="tab-pane fade show active" id="users-pane" role="tabpanel" aria-labelledby="users-tab" tabindex="0">
            <table>
                <thead>
                    <tr>
                        <th>User</th><th>Email</th>
                        <?php

    $sets_num = array();
    foreach ($sets as $group => $perms) {
                        ?><th>
                            <?php echo $group;?>
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
    while($user = fetch_safe_assoc($userR)) {
                    ?>
                    <tr>
                        <form id='<?php echo $user['id'];?>' action='javascript:void(0)'>
                            <input type='hidden' name='user_id' value='<?php echo $user['id'];?>' />
                            <?php
                            ?>
                            <td>
                                <?php echo $user['name'];?>
                            </td><td>
                                <?php echo $user['email']; ?>
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
            ?> />
                            </td>
                            <?php
        }
                            ?>
                            <td>
                                <input form='<?php echo $user['id']; 
                                             ?>'
                                    type='submit' onclick='updatePermissions("<?php echo $user['id']; ?>")' value='Update' />
                                <input form='<?php echo $user['id'];
            ?>'
                                    type='button' onclick='clearPermissions("<?php echo $user['id']; ?>")' value='Delete' />
                            </td>
                        </form>
                    </tr>
                    <?php
    }
                    ?>
                </tbody>
            </table>
            <button onclick="$('#createDialog').dialog('open');">New Account</button>
        </div>
        <div id='createDialog'>
            <form id='createUserForm' action='javascript:void(0)'>
                Name: <input name='name' type='text' /><br />
                Email: <input name='email' type='email' /><br />
                <?php
    foreach($sets_num as $n => $set) {
                ?>
                <label class='blocks'>
                    <?php echo $n;?>
                    <input type='checkbox' name='<?php echo $n;?>' />
                </label>
                <?php
    }
                ?>
                <br />
                <input type='submit' value='Create' onclick='createAccount()' />
            </form>
        </div>
    </div>
    <div class="tab-pane fade" id="consetup-pane" role="tabpanel" aria-labelledby="consetup-tab" tabindex="0"></div>
    <div class="tab-pane fade" id="nextconsetup-pane" role="tabpanel" aria-labelledby="nextconsetup-tab" tabindex="0"></div>
    <div class="tab-pane fade" id="memconfig-pane" role="tabpanel" aria-labelledby="memconfig-tab" tabindex="0"></div>
    <div class='tab-pane fade' id='merge-pane' role='tabpanel' aria-labelledby='merge-tab' tabindex='0'>
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
