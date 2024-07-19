$(document).ready(function () {
    hideBlock("#transaction");
    $("#transactionForm").data('maxPay', 1);
    $("#transactionForm").data('maxPeople', 0);

    track("#editForm");

    $('#newPersonForm').bind("reset", function (e) {
        $('#transactionFormOwnerBadgeReprint').removeAttr('disabled');
        $('#findForCreate').removeAttr('disabled');
        $('#newPersonTransaction').removeAttr('disabled');
        $('#fetchTransactionSubmit').removeAttr('disabled');
        $('#newID').val('');
        $('#oldID').val('');
        $('#updatePerson').attr('disabled', 'disabled');
        $('#checkConflict').removeAttr('disabled');
        $('#oname').empty();
        $('#oemail').empty();
        $('#oaddr').empty();
        $('#ophone').empty();
        $('#obadge').empty();
        $('#newPersonForm :required').map(function (e) { $(this).removeClass('need'); });
    });
});

function findToCreate(form) {
    var getData = $(form).serialize();
    $.ajax({
        url: 'scripts/findPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXhr) {
            if (data['error'] != undefined) { console.log(data['error']); }
            displaySearchResults(data, createTransaction)
        }
    });
}

function findToAppend() {
    var full_name = $('#addFullName').val();
    $.ajax({
        url: 'scripts/findPerson.php',
        method: "GET",
        data: { "full_name": full_name },
        success: function (data, textStatus, jqXhr) {
            if (data['error'] != undefined) { console.log(data['error']); }
            displaySearchResults(data, appendPerson)
        }
    });
}

function appendNewPerson(data, textStatus, jqXHR) {
    script = "scripts/getPerson.php";
    $.ajax({
        url: script,
        data: { 'id': data['id'] },
        method: "GET",
        success: function (data, textStatus, jqXHR) {
            appendPerson(data['results']);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $('#test').empty().append(jqXHR);
        }

    });
}

function appendPerson(user) {
    var num = +$('#transactionForm').data('maxPeople') + 1;
    var name = 'Person' + num;
    var id = 'transactionForm' + name;
    var perBody = $(document.createElement('tbody')).attr('id', id);
    if (user['banned'] == "Y") { perBody.addClass('banned'); }
    else { perBody.removeClass('banned'); }

    var perName = $(document.createElement('td')).attr('colspan', 2).append(user['full_name']);
    var perSpacer = $(document.createElement('td'));
    var perSpacer2 = $(document.createElement('td')).attr('colspan', 2);
    var perEmail = $(document.createElement('td')).append(user['email_addr']);
    var perNotes = $(document.createElement('td'));
    var perAddr = $(document.createElement('td')).attr('colspan', 4);
    perAddr.append(user['address']).append($(document.createElement('br')));
    if (user['addr_2']) { perAddr.append(user['addr_2']).append($(document.createElement('br'))); }
    perAddr.append(user['locale']).append($(document.createElement('br')));

    var perButtons = $(document.createElement('td')).attr('colspan', 2);
    perButtons.append($(document.createElement("button"))
        .attr('id', id + "Edit")
        .click(function () { editPerson(id); return false; })
        .append("Edit Person"));
    perButtons.append($(document.createElement("br")));
    perButtons.append($(document.createElement("button"))
        .attr('id', id + 'Cancel')
        .click(function () { addBadgeAddon("cancel", $('#' + id + "BadgeId").val(), id, ""); })
        .append("Cancel Pickup"));

    var perLabels = $(document.createElement('tr')).html("<td class='formlabel' colspan=2>Badge Name</td><td class='formlabel center'>paid/price</td><td class='formlabel'>Badge Type</td><td class='formlabel'></td><td class='formlabel'>Cost</td>");
    var perBadgeName = $(document.createElement('td')).attr('colspan', 2).append(user['badge_name']);
    var badgeLine = $(document.createElement('tr')).attr('id', id + "Badge");
    badgeLine.append(perBadgeName);
    badgeLine.append($(document.createElement('input')).attr('id', id + "Id").attr('type', 'hidden').val(user['id']));
    badgeLine.append($(document.createElement('td')).attr('id', id + "BadgePaidPrice")
        .addClass('center')
        .append($(document.createElement('span')).attr('id', id + "BadgePaid")).append("/")
        .append($(document.createElement('span')).attr('id', id + "BadgePrice"))
    );
    badgeLine.append($(document.createElement('td')).attr('id', id + "BadgeTypeSelect"));
    badgeLine.append($(document.createElement('td'))
        .attr('id', id + "BadgeButtons")
        .append($(document.createElement('button')).attr('id', id + 'BadgeSubmit')
            .click(function () { updateBadge('transactionForm', name, 'scripts/createBadge.php'); })
            .append("Create")
        ));
    badgeLine.append($(document.createElement('input'))
        .attr('type', 'hidden').attr('id', id + 'BadgeId').val(user['badgeId']));
    badgeLine.append($(document.createElement('td'))
        .attr('id', id + "BadgeCost").addClass('rightText'));

    perBody.append($(document.createElement('tr'))
        .append(perName).append(perSpacer).append(perEmail).append(perNotes));
    perBody.append($(document.createElement('tr'))
        .append(perAddr).append(perButtons));
    perBody.append(perLabels);

    perBody.append(badgeLine);
    var actionLine = $(document.createElement('tr'));
    actionLine.append($(document.createElement('td'))
        .append($(document.createElement('ul'))
            .attr('id', id + "BadgeAction"))
        .attr('colspan', 6));
    perBody.append(actionLine);


    var actionButtonLine = $(document.createElement('tr'));
    var actionButtons = $(document.createElement('td'))
        .attr('colspan', 6).attr('id', id + "BadgeActionButtons");
    actionButtons.append($(document.createElement('button'))
        .attr('id', id + "BadgeNote")
        .addClass('badgeAction')
        .addClass('right')
        .click(function () { addBadgeNote("notes", $('#' + id + "BadgeId").val(), id + "Badge"); })
        .append("Add Note"));

    /*
        actionButtons.append($(document.createElement('button'))
            .attr('id', id+"BadgeReturn")
            .addClass('badgeAction')
            .addClass('right')
            .click(function () { addBadgeNote("reprint", $('#'+id+"BadgeId").val(), id+"Badge"); })
            .append("Reprint"));
        
        actionButtons.append($(document.createElement('button'))
            .attr('id', id+"BadgeReprint")
            .addClass('badgeAction')
            .addClass('right')
            .click(function () { addBadgeNote("return", $('#'+id+"BadgeId").val(), id+"Badge"); })
            .append("Return"));
     */
    actionButtonLine.append(actionButtons);
    perBody.append(actionButtonLine);

    $('#transactionFormAdd').before(perBody);
    $('#transactionForm').data('maxPeople', num);

    var script = "scripts/getBadge.php";
    $.ajax({
        url: script,
        type: "GET",
        data: { "perid": user['id'], "badgeId": user['badgeId'] },
        success: function (data, textStatus, jqXHR) {
            setBadgeLine('transactionForm', name, data['badgeTypes'], data['badgeInfo']);
        },
        error: function (JqXHR, textStatus, errorThrown) {
            $('#test').empty().append(JSON.stringify(JqXHR, null, 2));
        }
    });
}

function createTransaction(user) {
    $('#searchResultHolder').empty();
    var script = "scripts/reg_start.php";
    $.ajax({
        url: script,
        type: "POST",
        data: "perid=" + user['id'],
        success: setTransaction,
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

function createTransactionNewPerson(data, textStatus, jqXHR) {
    createTransaction(data);
    $('#newPerson').hide();
}

function checkForReg(form) {
    var postData = $(form).serialize();
    $.ajax({
        url: 'scripts/addPerson.php',
        method: "POST",
        data: postData,
        success: function (data, textStatus, jqXhr) {
            if (data['error'] != undefined) { console.log(data['error']); }

            getData = "id=" + data['id'];
            $.ajax({
                url: 'scripts/getNewPerson.php',
                method: "GET",
                data: getData,
                success: function (data, textStatus, jqXHR) {
                    if (data['error'] != undefined) { console.log(data['error']); }

                    loadNewPerson(data);
                    displaySearchResults(data, loadOldPerson);

                    var newPersonButton = $(document.createElement("button"));
                    newPersonButton.append("New Person");
                    newPersonButton.attr("type", "button");
                    $("#searchResultHolder").append(newPersonButton);

                    newPersonButton.click(function () {
                        if (data['count'] > 0) {
                            if (!confirm("Please confirm no search result matches\nPress OK to create new user")) {
                                return false;
                            }
                        }
                        var formData = "newID=" + data['new']['id'];
                        var script = "scripts/addPersonFromConflict.php";
                        $.ajax({
                            data: formData,
                            method: "POST",
                            url: script,
                            success: updatePersonCatch,
                            error: function (jqXHR, textStatus, errorThrown) {
                                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                                return false;
                            }
                        });
                        return false;
                    });

                }
            });
        }
    });
}

function updatePersonCatch(data, textStatus, jqXHR) {
    $('#newPersonForm').trigger('reset');
    $('#newPerson').hide();
    $('#searchResultHolder').empty();
    var fun = $('#newPerson').data("callback");
    fun(data, textStatus, jqXHR);
}

function setTransaction(data, textStatus, jqXHR) {
    //$('#test').empty().append(JSON.stringify(data, null, 2));
    for (var i = 2; i <= $('#transactionForm').data('maxPay'); i++) {
        $("#transactionFormPayment" + i).remove();
    }
    for (var i = 1; i <= $('#transactionForm').data('maxPeople'); i++) {
        $("#transactionFormPerson" + i).remove();
    }
    if ((data['badges'] == null) || (data['result'] == null)) {
        $('#test').empty().append(JSON.stringify(data));
    }
    $('#transactionFormIdNum').empty().append(data['result']['tID']);
    $('#transactionForm').data('id', data['result']['tID']);
    setTransaction_inner(data['result']);

    if (data['total'] == undefined || isNaN(data['total'])) { setPaid(0); }
    else { setPaid(data['total']); }
    appendPayments("transactionForm", data['payments']);

    appendBadges("transactionForm", data['badges']);
    if (data['result']['tComplete']) {
        $('#addFullName').attr('disabled', true);
        $('#addFullNameSubmit').attr('disabled', true).addClass('disable');
        $('#NewPersonShow').attr('disabled', true);
        $('#transactionForm :input[name^="transactionFormPayment"]').attr('disabled', true).addClass('disable');
        $('#addPayment').attr('disabled', true);
        $('#transactionFormSubmit').attr('disabled', false);
        $('.badgeAction').attr('disabled', 'disabled');
    } else {
        $('#addFullName').attr('disabled', false);
        $('#addFullNameSubmit').attr('disabled', false).removeClass('disable');
        $('#addNewPerson').attr('disabled', false);
        $('#addPayment').attr('disabled', false);
        $('#transactionFormSubmit').attr('disabled', false);
        $('.badgeAction').removeAttr('disabled');
    }

    if (data['result']['tPaid'] == undefined || isNaN(data['result']['tPaid'])) {
        setPaid(0);
    } else {
        setPaid(+data['result']['tPaid']);
    }
}

function setTransaction_inner(tData) {
    showBlock("#transaction");
    if (tData['banned'] == "Y") { $('#transactionFormId').addClass('banned'); }
    else { $('#transactionFormId').removeClass('banned'); }
    setCost(0);
    setPrice(0);
    setPaid(0);
    $('#findForCreate').attr('disabled', 'disabled');
    $('#newPersonTransaction').attr('disabled', 'disabled');
    $('#fetchTransactionSubmit').attr('disabled', 'disabled');
    $('#transactionForm').data('maxPay', 1);
    $('#transactionForm').data('maxPeople', 0);
    $('#transactionFormIdCreate').empty().append(tData['tCreate']);
    $('#transactionFormIdComplete').empty().append(tData['tComplete']);
    $('#transactionFormIdNotes').empty().append(tData['tNotes']);
    $('#transactionFormOwnerName').empty().append(tData['ownerName']);
    $('#transactionFormOwnerEmail').empty().append(tData['ownerEmail']);
    if (tData['ownerAddr2']) {
        $('#transactionFormOwnerAddr').empty().append(tData['ownerAddr'] + "<br/>" + tData['ownerAddr2'] + "<br/>" + tData['ownerLocale']);
    } else {
        $('#transactionFormOwnerAddr').empty().append(tData['ownerAddr'] + "<br/>" + tData['ownerLocale']);
    }
    $('#transactionFormOwnerBadge').data('age', tData['age']);
    $('#transactionFormOwnerBadgeName').empty().append(tData['ownerBadge']);
    $('#transactionFormOwnerBadgeAction').empty();
    $('#transactionFormOwnerBadgeAction').removeClass('note');
    $('#transactionFormOwnerId').val(tData['ownerId']);
    if (tData['badgeId']) {
        setBadge("transactionFormOwnerBadge", tData['badgeId'], tData['paid'], tData['price'],
            tData['type'], tData['cost'], tData['locked'], tData['label']);
        if (tData['label'].includes('cancel') || (tData['nextid'] > 0)) {
            $('#transactionFormOwnerBadgeRollover').attr('disabled', 'disabled');
            $('#transactionFormOwnerBadgeVolunteer').attr('disabled', 'disabled');
        } else {
            $('#transactionFormOwnerBadgeRollover').removeAttr('disabled');
            $('#transactionFormOwnerBadgeVolunteer').removeAttr('disabled');
        }
    } else {
        clearBadge("transactionFormOwnerBadge");
        $('#transactionFormOwnerBadgeRollover').attr('disabled', 'disabled');
        $('#transactionFormOwnerBadgeVolunteer').attr('disabled', 'disabled');
    }
}

function setPrice(total) {
    $('#transactionForm').data('price', +total);
    $('#transactionFormTotalPrice').empty().append(total);
    setTotal();
}
function setPaid(total) {
    $('#transactionForm').data('paid', +total);
    $('#transactionFormTotalPaid').empty().append(total);
    setTotal();
}

function setCost(cost) {
    $('#transactionForm').data('cost', +cost);
    $("#transactionFormCurrentCost").empty().append($("#transactionForm").data('cost'));
    $("#transactionFormTotal").empty().append(cost);
    setTotal();
}

function setTotal() {
    var obj = $('#transactionForm');
    var remainder = obj.data('price') - obj.data('paid');
    obj.data('total', remainder);
    $("#transactionFormTotal").empty().append(remainder);
    $('#checkPaymentSub').empty().append(remainder);
    $('#checkPaymentTax').empty().append("N/A");
    $('#checkPaymentTotal').empty().append(remainder);
    $('#cashPaymentSub').empty().append(remainder);
    $('#cashPaymentTax').empty().append("N/A");
    $('#cashPaymentTotal').empty().append(remainder);
    $('#creditPaymentSub').empty().append(remainder);
    $('#creditPaymentTax').empty().append("N/A");
    $('#creditPaymentTotal').empty().append(remainder);
    if (remainder <= 0) {
        $("#transactionFormSubmit").attr('value', 'Complete + Print')
            .attr('disabled', false);
        $('.payment').attr('disabled', 'disabled');
    } else {
        $("#transactionFormSubmit").attr('value', 'Requires Payment')
            .attr('disabled', 'disabled');
        $('.payment').removeAttr('disabled');
    }
}

function clearBadge(prefix) {
    $("#" + prefix + "Id").empty();
    $("#" + prefix + "Paid").empty();
    $("#" + prefix + "Price").empty();
    $("#" + prefix + "Cost").empty();
    $("#" + prefix + "Type").val('none').prop('disabled', false).removeClass('disable');
    $("#" + prefix + "Submit").prop('disabled', false).removeClass('disable');
}
function setBadge(prefix, id, paid, price, badgeType, cost, locked, label) {
    var currentCost = +$('#transactionForm').data('cost');
    var currentPrice = +$('#transactionForm').data('price');
    var currentPaid = +$('#transactionForm').data('paid');
    $("#" + prefix + "Id").val(id);
    $("#" + prefix + "Paid").empty().append(paid);
    $("#" + prefix + "Price").empty().append(price);
    $("#" + prefix + "Cost").empty().append("$" + cost);
    $("#" + prefix + "Type").val(badgeType);
    //.prop('disabled', true).addClass('disable');
    if ($("#" + prefix + "Type").val() == null) {
        $("#" + prefix + "Type").append($(document.createElement('option')).attr('value', badgeType).append(label));
        $("#" + prefix + "Type").val(badgeType);
        //.prop('disabled', true).addClass('disable');
    }
    $("#" + prefix + "Submit").prop('disabled', true).addClass('disable').hide();
    var upgradeButton = $(document.createElement('button'))
        .append("Upgrade")
        .attr('prefix', prefix.substring("transactionForm".length, prefix.length - 5))
        .attr('badgeId', id)
        .on('click', function () {
            updateBadge('transactionForm', $(this).attr('prefix'), 'scripts/updateBadge.php');
            addBadgeNote('upgrade', id, prefix, $('#' + prefix + 'Type').val());
            $(this).remove();

        })
        .insertAfter($('#' + prefix + 'Submit'));
    var newCost = currentCost + (+cost);
    var newPrice = currentPrice + (+price);
    var newPaid = currentPaid + (+paid);
    setCost(newCost);
    setPrice(newPrice);
    if (locked == "Y") { $("#" + prefix).addClass('locked'); }
    else { $("#" + prefix).removeClass('locked'); }

    var script = "scripts/attachBadge.php"
    var transid = $('#transactionForm').data('id');
    var data = "id=" + id + "&transid=" + transid;
    $.ajax({
        method: "POST",
        data: data,
        url: script,
        success: function (data, textstatus, jqXHR) {
            if ('error' in data && data['error'] != '') {
                showError(data['error']);
            }
            showActions(prefix, data['actions'], id, transid);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }

    });
}


function editPerson(prefix) {
    var id = $("#" + prefix + "Id").val();
    var script = "scripts/editPerson.php";

    $.ajax({
        url: script,
        method: "GET",
        data: "id=" + id + "&prefix=" + prefix,
        success: fillEditPersonDialog,
        error: function (jqXHR, textstatus, errortext) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

function fillEditPersonDialog(data, textStatus, jqXHR) {
    var formObj = "#editForm :input[name='";

    $("#editForm").attr('perid', data["id"]);
    $("#editForm").attr('prefix', data["prefix"]);
    $("#editPersonFormIdNum").empty().append(data["id"]);
    $("#editPersonFormIdCreate").empty().append(data["creation_date"]);
    $("#editPersonFormIdUpdate").empty().append(data["update_date"]);

    $(formObj + "id']").val(data["id"]);
    $(formObj + "prefix']").val(data["prefix"]);
    $(formObj + "fname']").val(data["first_name"]);
    $(formObj + "mname']").val(data["middle_name"]);
    $(formObj + "lname']").val(data["last_name"]);
    $(formObj + "suffix']").val(data["suffix"]);
    $(formObj + "badge']").val(data["badge_name"]);
    $(formObj + "address']").val(data["address"]);
    $(formObj + "addr2']").val(data["addr_2"]);
    $(formObj + "city']").val(data["city"]);
    $(formObj + "state']").val(data["state"]);
    $(formObj + "zip']").val(data["zip"]);
    $(formObj + "country']").val(data["country"]);
    $(formObj + "email']").val(data["email_addr"]);
    $(formObj + "phone']").val(data["phone"]);

    track("#editForm");
    $('#editDialog').dialog('open');
    $("#editForm :input[name='prefix']").trigger("change")
}


function appendPayments(formName, payments) {
    var payment;
    for (payment in payments) {
        addPayment(formName, payments[payment]);
    }

}
function addPayment(formName, payment) {
    var num = +$("#" + formName).data('maxPay') + 1;
    var baseId = formName + "Payment" + num;

    var paymentBody = $(document.createElement("tbody")).addClass('noborder');
    paymentBody.attr('id', baseId);
    var formline = $(document.createElement("tr"));

    var payType = $(document.createElement("select"));
    payType.attr('name', baseId + "Type");
    payType.html("<option value='credit'>Credit Card</option><option value='check'>Check</option><option value='cash'>Cash</option><option value='discount'>Discount Type</option>");
    if (null != payment) {
        payType.val(payment['type']);
        payType.attr('disabled', 'disabled');
    }
    formline.append($(document.createElement("td")).append(payType).addClass('formfield'));

    var payNote = $(document.createElement("input")).attr('type', 'text').attr('size', 50).attr('name', baseId + "Note").attr('disabled', 'disabled');
    if (null != payment) { payNote.val(payment['description']); payNote.attr('disabled', true); }
    formline.append($(document.createElement("td")).append(payNote).addClass('formfield').attr('colspan', 3));

    var payAmnt = $(document.createElement("input")).attr('type', 'text').attr('size', 8).attr('name', baseId + "Amount").attr('disabled', 'disabled');
    if (null != payment) { payAmnt.val(payment['amount']); payAmnt.attr('disabled', true); }
    formline.append($(document.createElement("td")).append(payAmnt).addClass('formfield'));

    var payBtn = $(document.createElement("button")).attr('id', baseId + "Submit");
    payBtn.click(function () { takePayment(formName, "Payment" + num, "scripts/de_takePayment.php") });
    if (null != payment) { payBtn.attr('disabled', true); payBtn.addClass('disable'); }
    payBtn.append("Pay");
    formline.append($(document.createElement("td")).append(payBtn).addClass('center'));

    paymentBody.append(formline);

    $("#" + formName + "Table").append(paymentBody);
    $("#" + formName).data('maxPay', num);
}


function updateBadge(formName, badgeLabel, script) {
    var prefix = formName + badgeLabel;
    var dataObj = $("#" + formName + " :input[name^= " + prefix + "]");
    var badgeTypeStr = $("#" + formName + " :input[name^= " + prefix + "BadgeType]").val();
    var badgeTypeArr = badgeTypeStr.split("-");
    var thisPrice = +$('#' + prefix + "BadgePrice").text();
    var thisCost = +($('#' + prefix + "BadgeCost").text().substring(1));
    var currentCost = +$('#transactionForm').data('cost');
    var currentPrice = +$('#transactionForm').data('price');
    var newCost = currentCost - (+thisCost);
    var newPrice = currentPrice - (+thisPrice);
    setCost(newCost);
    setPrice(newPrice);

    var postData = {
        "badgeId": $('#' + prefix + "BadgeId").val(),
        "id": $("#" + prefix + "Id").val(),
        "transaction": $("#transactionForm").data('id'),
        "memId": badgeTypeArr[0],
        "category": badgeTypeArr[1],
        "type": badgeTypeArr[2],
        "age": badgeTypeArr[3],
        "iden": badgeLabel
    };

    $.ajax({
        url: script,
        type: "POST",
        data: postData,
        success: function (data, textStatus, jqXHR) {
            var bData = data['badgeInfo'];
            if ('error' in data && data['error'] != '') {
                showError(data['error']);
                return false;
            }
            setBadge(formName + badgeLabel + "Badge", bData['id'], bData['paid'], bData['price'],
                bData['memId'] + '-' + bData['memCategory'] + '-' + bData['memType'] + '-' + bData['memAge'],
                bData['cost'], bData['label']);
            return false;
        },
        error: function (JqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });

    return false;
}


function appendBadges(formName, badges) {
    var badge;
    for (badge in badges) {
        appendPerson(badges[badge]);
    }
}

function setBadgeLine(form, name, memTypes, badgeInfo) {
    var selectName = form + name + "BadgeType";
    $("#" + selectName + "Select").append($(document.createElement('select'))
        .attr('name', selectName).attr('id', selectName));
    $("#" + selectName).append($(document.createElement('option')).attr('value', 'none').append('None'))
    for (var memType in memTypes) {
        $("#" + selectName).append($(document.createElement('option'))
            .attr('value', memTypes[memType]['type'])
            .append(memTypes[memType]['label'] + " (" + memTypes[memType]['price'] + ")"));
    }
    if (null != badgeInfo) {
        $('#' + form + name).data('age', badgeInfo['age']);
        setBadge(form + name + "Badge", badgeInfo['id'], badgeInfo['paid'], badgeInfo['price'],
            badgeInfo['type'], badgeInfo['cost'], badgeInfo['locked'], badgeInfo['label']);
    }

}

function takePayment(type) {
    var paymentData = {
        type: type,
        category: 'reg',
    };
    switch (type) {
        case "check":
            $('#checkTransactionId').empty().append($('#transactionForm').data('id'));
            $('#checkNo').val('');
            $('#checkAmt').val('');
            $('#checkDesc').val('');
            $('.payBtn').removeAttr('disabled');
            $('#checkPayment').dialog('open');
            break;
        case "cash":
            $('.payBtn').removeAttr('disabled');
            $('#cashTransactionId').empty().append($('#transactionForm').data('id'));
            $('#cashAmt').val('');
            $('#cashDesc').val('');
            $('#cashPayment').dialog('open');
            break;
        case "credit":
            $('.payBtn').removeAttr('disabled');
            $('#creditTransactionId').empty().append($('#transactionForm').data('id'));
            $('#creditAmt').val('');
            $('#creditTrack').val('');
            $('#creditDesc').val('');
            $('#creditNum').val('');
            $('#creditFirstName').val('');
            $('#creditLastName').val('');
            $('#creditExpMo').val('');
            $('#creditExpYr').val('');
            $('#creditPayment').dialog('open');
            break;
        case "discount":
            $('.payBtn').removeAttr('disabled');
            $('#discountTransactionId').empty().append($('#transactionForm').data('id'));
            $('#discountAmt').val('');
            $('#discountDesc').val('');
            $('#discountPayment').dialog('open');
            break;
        default:
            alert("Unknown Payment Type");
    }

    $('#' + type + 'PaymentSub').empty()
        .append('$' + $('#transactionForm').data('total'));
    $('#' + type + 'PaymentTotal').empty()
        .append('$' + $('#transactionForm').data('total'));
}

function makePayment(type) {
    $(".payBtn").attr('disabled', 'disabled');
    var description = "";
    var checkno = "";
    var approvalcode = "";
    if (type == 'check') { description = $('#checkNo').val(); checkno = description; }
    if (type == 'credit') { approvalcode = $("#" + type + "Desc").val(); }
    description += ": " + $("#" + type + "Desc").val();

    var postData = "trans_key=" + $('#transactionForm').data('id')
        + "&amount=" + $("#" + type + "Amt").val()
        + "&description=" + description
        + "&type=" + type;

    if (type == "credit") {
        postData += "&track=" + encodeURIComponent($('#creditTrack').val())
            + "&approvalcode=" + approvalcode;
    }
    if (type == "check") {
        postData += "&checkno=" + checkno;
    }
    var script = "scripts/regPayment.php";
    $.ajax({
        url: script,
        method: "POST",
        data: postData,
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        },
        success: function (data, textStatus, jqXHR) {
            //$('#test').empty().append(JSON.stringify(data, null, 2)); 
            if ('error' in data && data['error'] != '') { showError(data['error']); }
            addPayment("transactionForm", data['result']);
            var paid = +$("#transactionForm").data('paid');
            if (!isNaN(data['result']['amount'])) {
                paid += +data['result']['amount'];
            }
            setPaid(paid);

            if (data['change'] > 0) { alert("Change Due: " + data['change']); }
        }
    });

}

function completeTransaction(trans) {
    var transid = $('#' + trans).data('id');
    var transtotal = $('#' + trans).data('total');
    if (+transtotal > 0) {
        showAlert("Transaction <b>" + transid + "</b><br/>payment of <b>$" + transtotal + "</b><br/>Pay at Cashier");
    } else {
        var script = 'scripts/completeTransaction.php';
        $.ajax({
            url: script,
            data: { 'id': transid },
            type: "GET",
            success: function (data, textStatus, jqXHR) {
                if (data['success'] == 'true') {
                    var text = "<large>Transaction <b>" + transid
                        + "</b> Complete</large><br/>";
                    if (data['error'] != undefined) {
                        text += data['error'];
                    }
                    for (var i in data['badges']) {
                        text += "<br/>" + +data['badges'][i]['id'] + " "
                            + data['badges'][i]['badge_name'] + " "
                            + data['badges'][i]['label'];
                    }
                    showAlert(text)
                } else if (data['error']) {
                    showAlert("Transaction <b>" + transid + "</b> had error<br/><i>"
                        + data['error'] + "</i><br/>");
                } else if (data['price'] > data['paid']) {
                    showAlert("Transaction <b>" + transid + "</b><br/>Pay at Cashier<br/>" + "Remaining: " + (+data['price'] - (+data['paid'])));
                } else {
                    showAlert("Transaction <b>" + transid + "</b><br/>Weirdness");
                }
                $('#fetchTransactionId').val(transid);
                $('#fetchTransactionSubmit').click();
                return false;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }
}

function addBadgeNote(type, badgeId, prefix, note) {
    var text = '';
    var transid = $('#transactionForm').data('id');
    if (note != undefined) { text = note; }
    if (type == 'notes') { text = prompt("Please enter Note Text"); }

    formurl = 'scripts/badgeNote.php'
    formdata = { type: type, badgeId: badgeId, transid: transid, content: text };
    $.ajax({
        url: formurl,
        data: formdata,
        method: "POST",
        success: function (data, textstatus, jqXHR) {
            if ('error' in data && data['error'] != '') { showError(data['error']); }          
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + formurl + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

function showActions(prefix, acts, badgeId, transid) {
    elem = '#' + prefix + "Action";
    $(elem).removeClass("note");
    $(elem).empty();
    var printed = 0;

    if (acts.length > 0) for (act_num in acts) {
        var act = acts[act_num];
        var newAct = $(document.createElement('li')).append(
            act['action'] + "(" + act['tid'] + ") ... " + act['logdate'] +
            " ... " + act['notes']);

        if (act['action'] == 'notes') {
            $(elem).addClass("note");
        }
        if (act['action'] == 'print' && act['atcon_key'] != transid) { printed += 1; }

        $(elem).prepend(newAct);
    }

}

function getEdited(data, textStatus, jqXHR) {
    editPerson(data['post'].prefix);
    var script = "scripts/getTransaction.php";
    $.ajax({
        url: script,
        method: "GET",
        data: "id=" + $('#transactionForm').data('id'),
        success: setTransaction,
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

function processRolloverReturn(data, textstatus, jqXHR) {
    //console.log("success");
    //console.log(data);
    if ('error' in data && data['error'] != '') {
        showError(data['error']);
        return false;
    }
    if (data['success'] && data['success'] != '') {
        showError(data['success']);
        if (data['newlabel'] && data['newlabel'] != '') {
            $('#transactionFormOwnerBadgeType').val(data['newlabelid'] + '-rollover-full-all');
        }
        $('#transactionFormOwnerBadgeRollover').attr('disabled', 'disabled');
        $('#transactionFormOwnerBadgeVolunteer').attr('disabled', 'disabled');
    }
}

function processRollover(badgeid, type) {
    //console.log(badgeid);
    //console.log(type);

    var script = "scripts/rolloverBadge.php";
    $.ajax({
        url: script,
        method: "POST",
        data: { "id": badgeid, "type": type },
        success: processRolloverReturn,
        error: function (jqXHR, textStatus, errorThrown) {
            //console.log("error");
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}
