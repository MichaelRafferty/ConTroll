<?php
global $db_ini;

require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "reg_admin";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css',
                    'css/table.css',
                    'css/reg_admin.css'
                    ),
    /* js  */ array('/javascript/d3.js',
                    'js/base.js',
                    'js/reg_admin.js',
                    'js/table.js'),
                    $need_login);

?>
<div id='main'>
    <span class='half' id='facets'>
    </span>
    <span class='half' id='table'>
        <div id='gridFilter'>
            <span id='gridSelectWrap' class='right'>
                <span id='gridSelect'></span>
                <button onclick='clearSelect("#grid")'>Clear</button>
                <button onclick='invSelect("#grid")'>Invert</button>
                <button onclick='addFilter("#grid")'>Filter</button>
            </span>
        </div>
        <div id='gridCtrl'>
            <span class='right'>
                Badge
                <input type='number' id='gridStart' min=0 step=1 value=0 />
                Of <span id='gridVis'></span> (<span id='gridMax'></span>)
                <button onClick='redraw("#grid")'>Go</button>
            </span>
            <span >
                <button onClick='firstPage("#grid")'>First</button>
                <button onClick='prevPage("#grid")'>Prev</button>
                <button onClick='nextPage("#grid")'>Next</button>
                <button onClick='lastPage("#grid")'>Last</button>
                Page Size
                <select id='gridSize'>
                    <option>10</option>
                    <option selected='selected'>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
            </span>
        </div>
        <table id='grid'>
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Badge Type</th>
                    <th>Payment</th>
                    <th>Dates</th>
                    <th>Buttons</th>
                </tr>
            </thead>
            <tbody id='gridBody'>
            </tbody>
        </table>
    </span>
    <span class='half' id='reports'>
        <a href="reports/allEmails.php">Email List</a> |
        <a href="reports/regReport.php">Reg Report</a> |
        <button onClick="sendEmail('marketing')">Send Marketing Email</button>
        <button onClick="sendEmail('reminder')">Send Attendance Reminder Email</button>
        <?php if ($db_ini['reg']['cancelled']) { ?>
        <button onClick="sendCancel()">Send Cancelation Instructions</button>
        <?php } ?>      
         <?php if ($db_ini['reg']['cancelled']) { ?>
        <br/>
        <a href="reports/cancel.php">Cancelation Report</a>
        <a href="reports/processRefunds.php">Process Refunds</a>
        <?php } ?>
    </span>
</div>
<pre id='test'>
</pre>
<?php


page_foot($page);
?>
