<?php

require_once "lib/base.php";

if (!isSessionVar('user')) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf('con');
$conid = $con['id'];
$method = 'manager';
$page = "Atcon Administration";

if (!check_atcon($method, $conid)) {
    header('Location: /index.php');
    exit(0);
}

$cdn = getTabulatorIncludes();
page_init($page, 'admin',
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'], 'css/style.css'),
    /* js  */ array($cdn['tabjs'],'js/admin.js','jslib/atconPrinters.js','jslib/atconUsers.js','jslib/atconTerminals.js')
    );

?>
<ul class='nav nav-tabs mb-3' id='admin-tab' role='tablist'>
    <li class='nav-item' role='presentation'>
        <button class='nav-link active' id='users-tab' data-bs-toggle='pill' data-bs-target='#users-pane' type='button'
                role='tab' aria-controls='nav-users' aria-selected='true' onclick="settab('users-pane');">Users
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='printers-tab' data-bs-toggle='pill' data-bs-target='#printers-pane' type='button'
                role='tab' aria-controls='nav-printers' aria-selected='false' onclick="settab('printers-pane');">Printers
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='terminals-tab' data-bs-toggle='pill' data-bs-target='#terminals-pane' type='button'
                role='tab' aria-controls='nav-terminals' aria-selected='false' onclick="settab('terminals-pane');">Square Terminals
        </button>
    </li>
</ul>
<div class='tab-content' id='admin-content'>
    <div class='tab-pane fade show active' id='users-pane' role='tabpanel' aria-labelledby='users-tab' tabindex='0'>
        <div class='container-fluid mt-4'>
            <div class="row">
                <div class="col-sm-auto table-bordered table-sm" id="userTab"></div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4">
                    <button type='button' class='btn btn-secondary btn-sm' id='users_add_user_btn' onclick='users.addUser();'>Add User</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='users_undo_btn' onclick='users.undo();' disabled>Undo</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='users_redo_btn' onclick='users.redo();' disabled>Redo</button>
                    <button type='button' class='btn btn-primary btn-sm' id='users_save_btn' onclick='users.save();' disabled>Save</button>
                </div>
            </div>
        </div>
        <div id='addUser' class='container-fluid mt-4' hidden>
            <div class='row'>
                <div class='col-sm-6'>
                    <div class='form-floating mb-3'>
                        <input type='text' name='name_search' id='name_search' class='form-control' oninput="users.search_name_changed();"
                               placeholder='First and Last Name Fragment' required/>
                        <label for='name_search'>User to Add: (Type parts of first and last name or enter the perid):</label>
                    </div>
                </div>
            </div>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <button type='button' class='btn btn-primary btn-sm' id='users_search_btn' onclick='users.search();'>Search Users</button>
                </div>
                <div class='col-sm-auto'>
                    <button type='button' class='btn btn-secondary btn-sm' id='users_search_btn' onclick='users.cancelSearch();'>Cancel Search</button>
                </div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-auto table-bordered table-sm' id='searchTab'></div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='printers-pane' role='tabpanel' aria-labelledby='printers-tab' tabindex='0'>
        <div class='container-fluid'>
            <div class='row'>
                <div class='col-sm-auto'><h2>Print Servers</h2></div>
            </div>
            <div class='row'>
                <div class='col-sm-auto table-bordered table-sm' id='serversTable'></div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-4'>
                    <button type='button' class='btn btn-secondary btn-sm' id='servers_add_btn' onclick='printers.addServer();'>Add Server</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='servers_undo_btn' onclick='printers.undo_server();' disabled>Undo</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='servers_redo_btn' onclick='printers.redo_server();' disabled>Redo</button>
                </div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-auto'><h2>Printers</h2></div>
            </div>
            <div class='row'>
                <div class='col-sm-auto table-bordered table-sm' id='printersTable'></div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-4'>
                    <button type='button' class='btn btn-secondary btn-sm' id='printers_add_btn' onclick='printers.addPrinter();'>Add Printer</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='printers_undo_btn' onclick='printers.undo_printer();' disabled>Undo</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='printers_redo_btn' onclick='printers.redo_printer();' disabled>Redo</button>
                    <button type='button' class='btn btn-primary btn-sm' id='printers_save_btn' onclick='printers.save();' disabled>Save</button>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='terminals-pane' role='tabpanel' aria-labelledby='terminals-tab' tabindex='0'>
        <div class='container-fluid'>
            <div class='row'>
                <div class='col-sm-auto'><h2>Square Terminals</h2></div>
            </div>
            <div class='row'>
                <div class='col-sm-auto table-bordered table-sm' id='terminalsTable'></div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-4'>
                    <button type='button' class='btn btn-secondary btn-sm' id='terminals_add_btn' onclick='terminals.addTerminal();'>Add Terminal</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--- status details modal popup -->
<div class='modal modal-xl' id='statusDetails' tabindex='-4' aria-labelledby='statusDetails' data-bs-backdrop='static' style='--bs-modal-width: 90%;'
     aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title' id='statusDetailsTitle'>
                    Terminal Details
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' id='statusDetailsBody'>
                <h1 class="size-h3">Square Terminal Settings</h1>
                <div class="row">
                    <div class='col-sm-2'>Terminal Name:</div>
                    <div class='col-sm-10' id="detailsName"></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Product Type:</div>
                    <div class='col-sm-10' id='detailsProductType'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Square ID:</div>
                    <div class='col-sm-10' id='detailsSquareId'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Location ID:</div>
                    <div class='col-sm-10' id='detailsLocationId'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Device ID:</div>
                    <div class='col-sm-10' id='detailsDeviceId'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Square Code:</div>
                    <div class='col-sm-10' id='detailsSquareCode'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Square Name:</div>
                    <div class='col-sm-10' id='detailsSquareName'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Square Model:</div>
                    <div class='col-sm-10' id='detailsSquareModel'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Version:</div>
                    <div class='col-sm-10' id='detailsVersion'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Terminal API Version:</div>
                    <div class='col-sm-10' id='detailsTerminalAPIVersion'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Pair By Date:</div>
                    <div class='col-sm-10' id='detailsPairBy'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Paired At Date:</div>
                    <div class='col-sm-10' id='detailsPairedAt'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Create Date:</div>
                    <div class='col-sm-10' id='detailsCreateDate'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Status:</div>
                    <div class='col-sm-10' id='detailsStatus'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Status Date:</div>
                    <div class='col-sm-10' id='detailsStatusChanged'></div>
                </div>
                <h1 class='size-h3 mt-4'>Square Terminal Condition</h1>
                <h2 class='size-h4'>Battery Power</h2>
                <div class='row'>
                    <div class='col-sm-2'>Battery Level:</div>
                    <div class='col-sm-10' id='detailsBatteryLevel'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>External Power:</div>
                    <div class='col-sm-10' id='detailsExternalPower'></div>
                </div>
                <h2 class='size-h4 mt-3'>WIFI</h2>
                <div class='row'>
                    <div class='col-sm-2'>Active:</div>
                    <div class='col-sm-10' id='detailsWifiActive'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>SSID:</div>
                    <div class='col-sm-10' id='detailsWifiSSID'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>V4 Address:</div>
                    <div class='col-sm-10' id='detailsWifiIPAddressV4'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>V6 Address:</div>
                    <div class='col-sm-10' id='detailsWifiIPAddressV6'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Signal Strength:</div>
                    <div class='col-sm-10' id='detailsSignalStrength'></div>
                </div>
                <h2 class='size-h4 mt-3'>Ethernet</h2>
                <div class='row'>
                    <div class='col-sm-2'>Active:</div>
                    <div class='col-sm-10' id='detailsEthernetActive'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>V4 Address:</div>
                    <div class='col-sm-10' id='detailsEthernetIPAddressV4'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>V6 Address:</div>
                    <div class='col-sm-10' id='detailsEthernetIPAddressV6'></div>
                </div>
                <h1 class='size-h3 mt-4'>ConTroll Terminal Status</h1>
                <div class='row'>
                    <div class='col-sm-2'>Current Order:</div>
                    <div class='col-sm-10' id='detailsCurrentOrder'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Current Payment:</div>
                    <div class='col-sm-10' id='detailsCurrentPayment'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Current Operator:</div>
                    <div class='col-sm-10' id='detailsCurrentOperator'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Status:</div>
                    <div class='col-sm-10' id='detailsControllStatus'></div>
                </div>
                <div class='row'>
                    <div class='col-sm-2'>Status Updated:</div>
                    <div class='col-sm-10' id='detailsControllStatusChanged'></div>
                </div>
            </div>
            <div class='modal-footer'>
                <button type='button' id='statusClose' class='btn btn-primary' onclick='statusDetailsModal.hide();'>Close Details</button>
            </div>
        </div>
    </div>
</div>
<!--- status details modal popup -->
<div class='modal modal-xl' id='addTerminal' tabindex='-4' aria-labelledby='addTerminal' data-bs-backdrop='static' style='--bs-modal-width: 80%;'
     aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title' id='addTerminalTitle'>
                    Add New Square Terminal
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' id='addTerminalBody'>
                <h1 class='size-h3'>Add New Square Terminal</h1>
                <div class="row">
                    <div class="col-sm-2">
                        <label for="newTerminalName">New Terminal Name:</label>
                    </div>
                    <div class="col-sm-auto">
                        <input type="text" class="form-control" id="newTerminalName" placeholder="Enter new terminal name"
                               size="30" maxlength="32" autofocus="true">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-2">
                        <label for="newTerminalLocation">Location:</label>
                    </div>
                    <div class="col-sm-auto">
                        <select id="newTerminalLocation">
                            <option value="" selected="selected">Select Location</option>
<?php
    $cc = get_conf('cc');
    foreach ($cc AS $name => $value) {
        if (str_starts_with($name, 'location')) {
            $shortname = substr($name, strlen('location'));
            if ($shortname == '')
                $shortname = 'default';
            echo "<option value='$value'>$shortname ($value)</option>\n";
        }
    }
?>
                        </select>
                    </div>
                </div>
                <div id="pairBlock" hidden>
                    <div class="row mt-4">
                        <div class='col-sm-2'>Terminal Name:</div>
                        <div class='col-sm-10' id='createName'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-2'>Square Code:</div>
                        <div class='col-sm-10' id='createSquareCode'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-2'>Product Type:</div>
                        <div class='col-sm-10' id='createProductType'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-2'>Square ID:</div>
                        <div class='col-sm-10' id='createSquareId'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-2'>Location ID:</div>
                        <div class='col-sm-10' id='createLocationId'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-2'>Pair By Date:</div>
                        <div class='col-sm-10' id='createPairBy'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-2'>Status:</div>
                        <div class='col-sm-10' id='createStatus'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-2'>Status Date:</div>
                        <div class='col-sm-10' id='createStatusChanged'></div>
                    </div>
                    <div class='row mt-4'>
                        <div class='col-sm-12'>
                            <p>
                                Now login in your terminal using the login by device code option.
                                The code to enter is the six character Square Code above.
                                You have to do this by the pair by date/time shown above.
                                Once the terminal has successfully logged in, as shown by the square box or your conventions logo on the screen,
                                the click the proceed button below to load the rest of the terminals configuration into the database.
                            </p>
                        </div>
                    </div>
                    <div class='row mt-4'>
                        <div class='col-sm-auto'>
                            <button type='button' class='btn btn-primary' id='createProceed' onclick="terminals.createProceed();">Proceed</button>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-12" id="add_result_message"></div>
                </div>
            </div>
            <div class='modal-footer'>
                <button type='button' id='AddTerminalCreate' class='btn btn-primary' onclick='terminals.createTerminal();'>Create Terminal</button>
                <button type='button' id='addTerminalClose' class='btn btn-secondary' onclick='addTerminalModal.hide();'>Close Create</button>
            </div>
        </div>
    </div>
</div>
<div id="result_message" class="mt-4 p-2"></div>
<pre id='test'></pre>
