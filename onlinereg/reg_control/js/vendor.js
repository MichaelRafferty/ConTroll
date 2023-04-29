function resetPw(vendor) {
    $.ajax({
        url: 'scripts/setPassword.php',
        method: "GET",
        data: 'vendor=' + vendor,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            alert(data['password']);
        }
    });
}

function resetPWForm() {
var vendorId = $('#vendorId').val();
resetPw(vendorId);
}

function authorize(vendor) { 
    $.ajax({
        url: 'scripts/getVendorReq.php',
        method: "GET",
        data: 'vendor='+vendor,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            $('#vendorId').val(data['id']);
            $('#vendorName').val(data['name']);
            $('#vendorWebsite').val(data['website']);
            $('#vendorDesc').val(data['description']);
            $('#alleyRequest').val(data['alleyRequest']);
            $('#alleyAuth').val(data['alleyAuth']);
            $('#alleyPurch').val(data['alleyPurch']);
            $('#dealerRequest').val(data['dealerRequest']);
            $('#dealerAuth').val(data['dealerAuth']);
            $('#dealerPurch').val(data['dealerPurch']);
            $('#d10Request').val(data['d10Request']);
            $('#d10Auth').val(data['d10Auth']);
            $('#d10Purch').val(data['d10Purch']);
            console.log(data);
        }
    });
}

function updateVendor() { 
    var formData = $('#vendorUpdate').serialize();
    $.ajax({
        url: 'scripts/setVendorReq.php',
        method: "POST",
        data: formData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            $('#vendorId').val(data['id']);
            $('#vendorName').val(data['name']);
            $('#vendorWebsite').val(data['website']);
            $('#vendorDesc').val(data['description']);
            $('#alleyRequest').val(data['alleyRequest']);
            $('#alleyAuth').val(data['alleyAuth']);
            $('#alleyPurch').val(data['alleyPurch']);
            $('#dealerRequest').val(data['dealerRequest']);
            $('#dealerAuth').val(data['dealerAuth']);
            $('#dealerPurch').val(data['dealerPurch']);
            $('#d10Request').val(data['d10Request']);
            $('#d10Auth').val(data['d10Auth']);
            $('#d10Purch').val(data['d10Purch']);
            console.log(data);
        }
    });
}

function findPerson(form) {
    var getData = $(form).serialize();
    $.ajax({
        url: 'scripts/findPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            displaySearchResults(data, getVendor)
        }
    });
}

function getVendor(perid) {
    $.ajax({
        url: 'scripts/getVendor.php',
        method: "GET",
        data: "perid="+perid.id,
        success: function(data, textStatus, jqXHR) {
            console.log(data);
            if('vendor' in data){ authorize(data['vendor']); }
            else { alert("No Vendor Exists"); }
        }
    });
}
