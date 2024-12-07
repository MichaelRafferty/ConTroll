class artItemTypes {
    static ART = 'art';
    static NFS = 'nfs';
    static PRINT = 'print';

    isValid(type) {
        return [artItemTypes.ART, artItemTypes.NFS, artItemTypes.PRINT].includes (type)
    }

    getType(value) {
        if(!this.isValid(type)) { return false; }
        switch (value) {
            case 'art': return this.ART;
            case 'nfs': return this.NFS;
            case 'print': return this.PRINT;
        }
    }
}
var TypeList = new artItemTypes();

class artItemStatuses {
    static ENTERED = 'Entered';
    static NOT_IN_SHOW = 'Not In Show';
    static CHECKED_IN = 'Checked In';
    static REMOVED = 'Removed From Show';
    static BID = 'BID';
    static QUICKSALE = 'Quicksale/Sold';
    static TOAUCTION = 'To Auction';
    static SOLDBID = 'Sold Bid Sheet';
    static SOLDAUCTION = 'Sold at Auction'
    static CHECKED_OUT = 'Checked Out';
    static RELEASED = 'Purchased/Released';

    #statuses = [artItemStatuses.ENTERED, artItemStatuses.NOT_IN_SHOW, artItemStatuses.CHECKED_IN, artItemStatuses.REMOVED,
    artItemStatuses.BID, artItemStatuses.QUICKSALE, artItemStatuses.TOAUCTION, artItemStatuses.SOLDBID, artItemStatuses.SOLDAUCTION,
    artItemStatuses.CHECKED_OUT, artItemStatuses.RELEASED];

    isValid(role, status = '')  { //TODO make this check against a current status
        return this.#statuses.includes(role);
    }

    getStatus(value) {
        if (!this.isValid(value)) { return false; }
        switch (value) {
            case this.ENTERED: return this.ENTERED;
            case this.NOT_IN_SHOW: return this.NOT_IN_SHOW;
            case this.CHECKED_IN: return this.CHECKED_IN;
            case this.REMOVED: return this.REMOVED;
            case this.BID: return this.BID;
            case this.QUICKSALE: return this.QUICKSALE;
            case this.TOAUCTION: return this.TOAUCTION;
            case this.SOLDBID: return this.SOLDBID;
            case this.SOLDAUCTION: return this.SOLDAUCTION;
            case this.CHECKED_OUT: return this.CHECKED_OUT;
            case this.RELEASED: return this.RELEASED;
        }
    }

    // maybe add in functions to show valid transitions or check transition validity
    setValidOptions(status  = '') {
        //TODO set valid options based on current status
        var options = "";
        for(value of this.#statuses) {
           options += "<option>" + value + "</option>";
        }
        return options;
    }
}
var statusList = new artItemStatuses();

var ai_message_div; //file variable so it can be accessed anywhere
class artItem {
    id;
    artistNumber;
    itemNumber;
    title;
    material;
    type;
    status;
    location;
    #locationList;
    quantity;
    original_qty;
    min_price;
    sale_price;
    final_price;
    #bidder;
    bidderName;
    #exhibitorRegionYearId;
    exhibitorName;
    #exhibitRegionYearId
    regionYearName;

    #editPane;
    #exhibitorNameField;
    #exhibitShowNameField;
    #artistNumberField;
    #itemNumberField;
    #titleField;
    #materialField;
    #typeField;
    #statusField;
    #locationField;
    #quantityField;
    #original_qtyField;
    #min_bidField;
    #sale_priceField;
    #sale_priceNameField;
    #bidderField;
    #bidderNameField;
    #final_priceField;

//functions to fetch and update based on id
constructor() {
    var id = document.getElementById('artItemEditPane');
    if(id != null) {
        this.#editPane = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
    }
    this.#exhibitorNameField =document.getElementById('artItemExhibitor');
    this.#exhibitShowNameField=document.getElementById('artItemShow');
    this.#artistNumberField=document.getElementById('artItemArtistNumber');
    this.#itemNumberField=document.getElementById('artItemItemNumber');
    this.#typeField=document.getElementById('artItemType');

    /* editable fields */
    this.#titleField=document.getElementById('artItemTitle');
    this.#materialField=document.getElementById('artItemMaterial');
    this.#statusField=document.getElementById('artItemStatus');
    this.#locationField=document.getElementById('artItemLocation');
    this.#quantityField=document.getElementById('artItemQuantity');
    this.#original_qtyField=document.getElementById('artItemOrigQty');
    this.#min_bidField=document.getElementById('artItemMinPrice');
    this.#sale_priceNameField=document.getElementById('artItemSalePriceName');
    this.#sale_priceField=document.getElementById('artItemSalePrice');
    this.#bidderField=document.getElementById('artItemBidder');
    this.#bidderNameField=document.getElementById('artItemBidderName');
    this.#final_priceField=document.getElementById('artItemFinalPrice');

    ai_message_div = document.getElementById('ai_result_message');
}

resetEditPane() {
    this.#exhibitorNameField.innerHTML = this.exhibitorName;
    this.#exhibitShowNameField.innerHTML = this.regionYearName;
    this.#artistNumberField.innerHTML = this.artistNumber;
    this.#itemNumberField.innerHTML = this.itemNumber;
    this.#typeField.innerHTML = this.type;

    switch(this.type) {
        case TypeList.ART:
            document.getElementById('minPriceRow').display = 'block';
            this.#sale_priceNameField.innerHTML = "Quicksale Price";
            break;
        case TypeList.NFS:
            document.getElementById('minPriceRow').display = 'none';
            this.#sale_priceNameField.innerHTML = "Insurance Amount";
            break;
        case TypeList.PRINT:
            document.getElementById('minPriceRow').display = 'none';
            this.#sale_priceNameField.innerHTML = "Sale Price";
            break;
    }
    //editable fields
    this.#titleField.value(this.title);
    this.#materialField.value(this.material);
    this.#statusField.innerHTML = StatusList.setValidOptions('');
    this.#statusField.value(this.status);
    for(loc of this.#locationList) {
        this.locationField.innerHTML += "<option>" + loc + "</option>";
    }
    this.#locationField.value = this.location;
    this.#quantityField.value = this.quantity;
    this.#quantityField.setAttribute('max',this.original_qty);
    this.#min_bidField.value = this.min_price; // hidden unless type = ART
    this.#sale_priceField.value = this.sale_price;
    this.#bidderField.value = this.#bidder;
    this.#bidderNameField = this.bidderName;
    //open Edit Pane
}
setValuesFromData(artItemData, artistInfo, bidderInfo = null) {
    this.id = artItemData['id'];
    this.artistNumber = artistInfo['artistNumber'];
    this.itemNumber = artistInfo['itemNumber'];
    this.title=artItemData['title'];
    this.material=artItemData['material'];
    this.type=typeList.getType(artItemData['type']);
    this.status=statusList.getStatus(artItemData['status']);
    this.location=artItemData['location'];
    this.#locationList = artistInfo['locations'];
    this.quantity = artItemData['quantity'];
    this.original_qty = artItemData['orig_qty'];
    this.min_price = artItemData['min_price'];
    this.sale_price = artItemData['sale_price'];
    this.#bidder = artItemData['bidder'];
    if(bidderInfo != null) { this.bidderName = bidderInfo['name']; }
    this.#exhibitorRegionYearId = artistInfo['exhibitorRegionYearId'];
    this.exhibitorName = artistInfo['exhibitorName'];
    this.#exhibitRegionYearId = artistInfo['exhibitRegionYearId']
    this.regionYearName = artistInfo['exhibitRegionYearName']

    if(this.type === false) {
        show_message('invalid item type', 'warn', ai_message_div); //TODO append if possible
    }
    if(this.status === false) {
        show_message('invalid item status', 'warn', ai_message_div); //TODO append if possible
    }
}

openEditPane() {
    this.#editPane.show();
}

closeEditPane() {
    this.#editPane.hide();
}

fetchArtItem(item_id) {
    var _this = this;
    $.ajax({
        url: 'scripts/artItem_getItem',
        method: 'GET',
        data: {itemId: item_id},
        success: function (data, textstatus, jqxhr) {
            if((data['status'] == 'error') || isdefined(data['error'])) {
                show_message(data['error'],'error', ai_message_div)
            } else {
                _this.setValuesFromData(data['item'], data['artist'], data['bidder']);
                _this.resetEditPane();
                _this.openEditPane();
            }
        },
        error: showAjaxError
    })
}

updateArtItem () {
    //TODO make this work using the information in the editPaneModal;
    alert("update not implemented");
}

}
artItemModal = null;
function artItemModalOnLoad () {
    artItemModal = new artItem();
}