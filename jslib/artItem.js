class artItemTypes {
    static ART = 'art';
    static NFS = 'nfs';
    static PRINT = 'print';

    isValid(type) {
        return [artItemTypes.ART, artItemTypes.NFS, artItemTypes.PRINT].includes (type)
    }

    getType(value) {
        if(!this.isValid(value)) { return false; }
        switch (value) {
            case 'art': return artItemTypes.ART;
            case 'nfs': return artItemTypes.NFS;
            case 'print': return artItemTypes.PRINT;
        }
    }
}
var typeList = new artItemTypes();

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
            case artItemStatuses.ENTERED: return artItemStatuses.ENTERED;
            case artItemStatuses.NOT_IN_SHOW: return artItemStatuses.NOT_IN_SHOW;
            case artItemStatuses.CHECKED_IN: return artItemStatuses.CHECKED_IN;
            case artItemStatuses.REMOVED: return artItemStatuses.REMOVED;
            case artItemStatuses.BID: return artItemStatuses.BID;
            case artItemStatuses.QUICKSALE: return artItemStatuses.QUICKSALE;
            case artItemStatuses.TOAUCTION: return artItemStatuses.TOAUCTION;
            case artItemStatuses.SOLDBID: return artItemStatuses.SOLDBID;
            case artItemStatuses.SOLDAUCTION: return artItemStatuses.SOLDAUCTION;
            case artItemStatuses.CHECKED_OUT: return artItemStatuses.CHECKED_OUT;
            case artItemStatuses.RELEASED: return artItemStatuses.RELEASED;
        }
    }
    getDefault() {
        return this.getStatus('Entered');
    }

    // maybe add in functions to show valid transitions or check transition validity
    setValidOptions(status  = '') {
        //TODO set valid options based on current status
        var options = "";
        for(const value of this.#statuses) {
            if(value == status) {
                options += "<option selected=selected>" + value + "</option>";
            } else {
                options += "<option>" + value + "</option>";
            }
        }
        return options;
    }
    getStatuses() { return this.#statuses;}
}
var statusList = new artItemStatuses();

var ai_message_div; //file variable so it can be accessed anywhere
class artItem {
    #index;
    #isChanged=false;
    #changedFields;

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
    itemNotes;

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
    #itemTable;
    #notesField;

//functions to fetch and update based on id
constructor(itemTable) {
    var id = document.getElementById('artItemEditPane');
    var _this=this;

    this.itemTable = itemTable;
    this.#changedFields={};

    if(id != null) {
        this.#editPane = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
    }
    this.#exhibitorNameField =document.getElementById('artItemExhibitor');
    this.#exhibitShowNameField=document.getElementById('artItemShow');
    this.#artistNumberField=document.getElementById('artItemArtistNumber');
    this.#itemNumberField=document.getElementById('artItemItemNumber');
    this.#typeField=document.getElementById('artItemType');
    this.#sale_priceNameField=document.getElementById('artItemSalePriceName');
    this.#bidderNameField=document.getElementById('artItemBidderName');//TODO make this field auto-update when bidder updatea

    /* editable fields */
    this.#titleField=document.getElementById('artItemTitle');
    this.#titleField.addEventListener('change',function() { _this.setIsChanged('artItemTitle','title') });
    this.#materialField=document.getElementById('artItemMaterial');
    this.#materialField.addEventListener('change',function() { _this.setIsChanged('artItemMaterial','material') });
    this.#statusField=document.getElementById('artItemStatus');
    this.#statusField.addEventListener('change',function() { _this.setIsChanged('artItemStatus','status') });
    this.#locationField=document.getElementById('artItemLocation');
    this.#locationField.addEventListener('change',function() { _this.setIsChanged('artItemLocation', 'location') });
    this.#quantityField=document.getElementById('artItemQuantity');
    this.#quantityField.addEventListener('change',function() { _this.setIsChanged('artItemQuantity','quantity') });
    this.#original_qtyField=document.getElementById('artItemOrigQty');
    this.#original_qtyField.addEventListener('change',function() { _this.setIsChanged('artItemOrigQty', 'original_qty') });
    this.#min_bidField=document.getElementById('artItemMinPrice');
    this.#min_bidField.addEventListener('change',function() { _this.setIsChanged('artItemMinPrice', 'min_price') });
    this.#sale_priceField=document.getElementById('artItemSalePrice');
    this.#sale_priceField.addEventListener('change',function() { _this.setIsChanged('artItemSalePrice', 'sale_price') });
    this.#bidderField=document.getElementById('artItemBidder');
    this.#bidderField.addEventListener('change',function() {_this.setIsChanged('artItemBidder','bidder');});
    this.#final_priceField=document.getElementById('artItemFinalPrice');
    this.#final_priceField.addEventListener('change',function() { _this.setIsChanged('artItemFinalPrice', 'final_price') });
    this.#notesField=document.getElementById('artItemNotes');
    this.#notesField.addEventListener('change',function() { _this.setIsChanged('artItemNotes', 'notes') });

    ai_message_div = document.getElementById('ai_result_message');
}
setPriceNames(type){
    switch(type) {
        case artItemTypes.ART:
            document.getElementById('minPriceRow').style.display = 'block';
            this.#sale_priceNameField.innerHTML = "Quicksale Price";
            break;
        case artItemTypes.NFS:
            document.getElementById('minPriceRow').style.display = 'none';
            this.#sale_priceNameField.innerHTML = "Insurance Amount";
            break;
        case artItemTypes.PRINT:
            document.getElementById('minPriceRow').style.display = 'none';
            this.#sale_priceNameField.innerHTML = "Sale Price";
            break;
    }
}
resetEditPane() {
    this.#exhibitorNameField.innerHTML = this.exhibitorName;
    this.#exhibitShowNameField.innerHTML = this.regionYearName;
    this.#artistNumberField.innerHTML = this.artistNumber;
    this.#itemNumberField.innerHTML = this.itemNumber;
    this.#typeField.innerHTML = this.type;

    this.setPriceNames(this.type)
    //editable fields
    this.#titleField.value = this.title;
    this.#materialField.value = this.material;
    this.#statusField.innerHTML = "";
    this.#statusField.innerHTML = statusList.setValidOptions(this.status);
    this.#statusField.value = this.status;
    this.#locationField.innerHTML = "<option></option>";
    for(const loc of this.#locationList) {
        this.#locationField.innerHTML += "<option>" + loc + "</option>";
    }
    this.#locationField.value = this.location;
    this.#quantityField.value = this.quantity;
    this.#quantityField.setAttribute('max',this.original_qty);
    this.#original_qtyField.value = this.original_qty;
    this.#min_bidField.value = this.min_price; // hidden unless type = ART
    this.#sale_priceField.value = this.sale_price;
    this.#bidderField.value = this.#bidder;
    this.#bidderNameField = this.bidderName;
    this.#final_priceField = this.final_price;
    this.#notesField.value = this.itemNotes;

    this.#isChanged=false;
    this.#changedFields= {};
}

setIsChanged(value,field) {
    this.#isChanged=true;
    this.#changedFields[value]=field;
}

setItemTable(itemTable) {this.#itemTable = itemTable;}

setValuesForNew(exhibitor, number, type) {
    this.#index = -99;
    this.id = -99;
    this.artistNumber = exhibitor.exhibitorNumber;
    this.itemNumber = number;
    this.title='';
    this.material=''
    this.type=typeList.getType(type);
    this.status=statusList.getDefault();
    this.location='';
    if (artItemData['locations'])
        this.#locationList = artItemData['locations'].split(',');
    else
        this.#locationList = [];
    this.quantity = 1;
    this.original_qty = 1;
    this.min_price = null;
    this.sale_price = null;
    this.#bidder = null;
    this.bidderName = null;
    this.final_price=null;
    this.#exhibitorRegionYearId = exhibitor.exhibitorRegionYearId;
    this.exhibitorName = exhibitor.exhibitorName;
    this.#exhibitRegionYearId = region;
    this.regionYearName = region;
    this.itemNotes = null;

    if(this.type === false) {
        show_message('invalid item type', 'warn', ai_message_div); //TODO append if possible
        }
    if(this.status === false) {
        show_message('invalid item status', 'warn', ai_message_div); //TODO append if possible
        }
    }

setValuesFromData(artItemData) {
    this.id = artItemData['id'];
    this.artistNumber = artItemData['exhibitorNumber'];
    this.itemNumber = artItemData['item_key'];
    this.title=artItemData['title'];
    this.material=artItemData['material'];
    this.type=typeList.getType(artItemData['type']);
    this.status=statusList.getStatus(artItemData['status']);
    this.location=artItemData['location'];
    if (artItemData['locations'])
        this.#locationList = artItemData['locations'].split(',');
    else
        this.#locationList = [];
    this.quantity = artItemData['quantity'];
    this.original_qty = artItemData['orig_qty'];
    this.min_price = artItemData['min_price'];
    this.sale_price = artItemData['sale_price'];
    this.#bidder = artItemData['bidder'];
    this.bidderName = artItemData['bidderName'];
    this.#exhibitorRegionYearId = artItemData['exhibitorRegionYearId'];
    this.exhibitorName = artItemData['exhibitorName'];
    this.#exhibitRegionYearId = artItemData['exhibitRegionYearId'];
    this.regionYearName = artItemData['exhibitRegionName'];
    this.itemNotes = artItemData['notes'];

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


fetchArtItem(index) {
    var data = this.#itemTable.getRow(index).getData();
    this.#index = index;
    this.setValuesFromData(data);
    this.resetEditPane();
    this.openEditPane();
}

updateArtItem () {
    if(this.#index < 0) {
        this.#itemTable.addData([{
            id: this.id,
            exhibitorRegionYearId: this.#exhibitorRegionYearId,
            locations: this.#locationList,
            exhibitorName: this.exhibitorName,
            exhibitorNumber: this.artistNumber,
            type: this.type,
            item_key: this.itemNumber,
            title: this.title,
            material: this.material,
            min_price: this.min_price,
            sale_price: this.sale_price,
            original_qty: this.original_qty,
            quantity: this.quantity,
            status: this.status,
            location: this.location,
            bidder: this.#bidder,
            final_price: this.final_price,
            notes: this.itemNotes
            }], true);
    }
    for (const changed in this.#changedFields) {
        var value = document.getElementById(changed).value;
        this.#itemTable.getRow(this.#index).getCell(this.#changedFields[changed]).setValue(value);

        if (this.#changedFields[changed] == 'bidder') {
            this.#itemTable.getRow(this.#index).getCell('bidderText').setValue(value);
        }
    }

    this.closeEditPane();
}

}
artItemModal = null;
function artItemModalOnLoad (itemTable) {
    artItemModal = new artItem(itemTable);
    return artItemModal;
}