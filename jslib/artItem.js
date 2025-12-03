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
    #bidderValid = true;
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
    #minPriceRow;
    #salePriceRow;
    #quantityRow;
    #bidderRow;
    #finalPriceRow;
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
    this.#minPriceRow=document.getElementById('minPriceRow');
    this.#salePriceRow=document.getElementById('salePriceRow');
    this.#quantityRow=document.getElementById('quantityRow');
    this.#bidderRow=document.getElementById('bidderRow');
    this.#finalPriceRow=document.getElementById('finalPriceRow');
    this.#bidderNameField=document.getElementById('artItemBidderName');//TODO make this field auto-update when bidder updates

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
}
setPriceNames(type){
    switch(type) {
        case artItemTypes.ART:
            this.#minPriceRow.style.display = 'block';
            this.#salePriceRow.style.display = 'block';
            this.#sale_priceNameField.innerHTML = "Quicksale Price";
            this.#finalPriceRow.style.display = 'block';
            this.#quantityRow.style.display = 'none';
            this.#bidderRow.style.display = 'block';
            break;
        case artItemTypes.NFS:
            this.#minPriceRow.style.display = 'none';
            this.#salePriceRow.style.display = 'block';
            this.#sale_priceNameField.innerHTML = "Insurance Amount";
            this.#finalPriceRow.style.display = 'none';
            this.#quantityRow.style.display = 'none';
            this.#bidderRow.style.display = 'none';
            break;
        case artItemTypes.PRINT:
            this.#minPriceRow.style.display = 'none';
            this.#salePriceRow.style.display = 'block';
            this.#sale_priceNameField.innerHTML = "Sale Price";
            this.#finalPriceRow.style.display = 'none';
            this.#quantityRow.style.display = 'block';
            this.#bidderRow.style.display = 'none';
            break;
    }
}
resetEditPane() {
    clear_message('ai_result_message');
    this.#exhibitorNameField.innerHTML = this.exhibitorName;
    this.#exhibitShowNameField.innerHTML = this.regionYearName;
    this.#artistNumberField.innerHTML = this.artistNumber;
    this.#itemNumberField.innerHTML = this.itemNumber;
    this.#typeField.innerHTML = this.type;
    this.#bidderValid = true;

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

    /* art only items */
    if (this.type == artItemTypes.ART) {
        this.#min_bidField.value = this.min_price;
        this.#bidderField.value = this.#bidder;
        this.#bidderNameField.innerHTML = this.bidderName;
        this.#final_priceField.value = this.final_price;
    } else {
        this.#min_bidField.value = null;
        this.#bidderField.value = null;
        this.#bidderNameField.innerHTML = '';
        this.#final_priceField.value = null;
    }

    if (this.#typeField == artItemTypes.PRINT) {
        this.#quantityField.value = this.quantity;
        this.#quantityField.setAttribute('max',this.original_qty);
        this.#original_qtyField.value = this.original_qty;
    } else {
        if (this.status == artItemStatuses.QUICKSALE || this.status == artItemStatuses.SOLDAUCTION ||
            this.status == artItemStatuses.SOLDAUCTION || this.status == artItemStatuses.RELEASED)
            this.#quantityField.value = 0;
        else
            this.#quantityField.value = 1;
        this.#original_qtyField.value = 1;
    }
    this.#sale_priceField.value = this.sale_price;
    this.#notesField.value = this.itemNotes;

    this.#isChanged=false;
    this.#changedFields= {};
}

setIsChanged(value,fieldName) {
    // validate the fields
    var _this = this;
    clear_message('ai_result_message');
    let fieldValue = document.getElementById(value).value;
    switch (value) {
        case 'artItemMinPrice':
            if (Number(fieldValue) < 1) {
                show_message("Minimum must be $1.00 or more", 'warn', 'ai_result_message');
                return;
            }
            let salePriceValue = Number(this.#sale_priceField.value);
            if (this.type == artItemTypes.ART && salePriceValue > 0 && Number(fieldValue) >= salePriceValue) {
                show_message("Minimum must be less than sale price", 'warn', 'ai_result_message');
                return;
            }
            break;

        case 'artItemSalePrice':
            if (this.type == artItemTypes.ART && Number(fieldValue) != 0 && Number(fieldValue) <= Number(this.#min_bidField.value)) {
                show_message("Sale price must not be less than minimum", 'warn', 'ai_result_message');
                return;
            }
            break;

        case 'artItemFinalPrice':
            let checkPrice = this.#statusField.value == artItemStatuses.QUICKSALE ? Number(this.#sale_priceField.value) : Number(this.#min_bidField.value);
            let checkPriceField = this.#statusField.value == artItemStatuses.QUICKSALE ? 'sale price' : 'minimum';
            if (Number(fieldValue) < checkPrice) {
                show_message("Final price must not be less than " + checkPriceField, 'warn', 'ai_result_message');
                return;
            }
            break;

        case 'artItemBidder':
            if (fieldValue == undefined || fieldValue == null || fieldValue == '' ) {
                _this.#bidderNameField.innerHTML = '';
                _this.bidderValid = true;
                break;
            }
            if (Number(fieldValue) < 1) {
                show_message("Invalid bidder number", 'warn', 'ai_result_message');
                return;
            }
            // use ajax to validate bidder number
            let script = "scripts/artcontrol_validateBidder.php"
            this.#bidderValid = false;
            let postdata = {
                bidder: Number(fieldValue),
                action: 'ValidateBidder',
            }
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data.error != undefined) {
                        show_message(data.error, 'error', 'ai_result_message');
                        return;
                    }
                    if (data.warning != undefined) {
                        show_message(data.warning, 'warn', 'ai_result_message');
                        return;
                    }
                    // valid return
                    _this.#isChanged=true;
                    _this.#changedFields.value = fieldName;
                    _this.#bidderNameField.innerHTML = data.name;
                    _this.#bidderValid = true;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in artControl: " + textStatus, jqXHR);
                    return false;
                }
            });
            break;
    }
    this.#isChanged=true;
    this.#changedFields[value]=fieldName;
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
    if (exhibitor['locations'])
        this.#locationList = exhibitor['locations'].split(',');
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
        show_message('invalid item type', 'warn', 'ai_result_message'); //TODO append if possible
        }
    if(this.status === false) {
        show_message('invalid item status', 'warn', 'ai_result_message'); //TODO append if possible
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
    if (artItemData['locations'] && artItemData['locations'] != '')
        this.#locationList = artItemData['locations'].split(',');
    else
        this.#locationList = [];
    this.quantity = artItemData['quantity'];
    this.original_qty = artItemData['original_qty'];
    this.min_price = artItemData['min_price'];
    this.sale_price = artItemData['sale_price'];
    this.final_price = artItemData['final_price'];
    this.#bidder = artItemData['bidder'];
    this.bidderName = artItemData['bidderName'];
    this.#exhibitorRegionYearId = artItemData['exhibitorRegionYearId'];
    this.exhibitorName = artItemData['exhibitorName'];
    this.#exhibitRegionYearId = artItemData['exhibitRegionYearId'];
    this.regionYearName = artItemData['exhibitRegionName'];
    this.itemNotes = artItemData['notes'];

    if(this.type === false) {
        show_message('invalid item type', 'warn', 'ai_result_message'); //TODO append if possible
    }
    if(this.status === false) {
        show_message('invalid item status', 'warn', 'ai_result_message'); //TODO append if possible
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
    clear_message('ai_result_message');
    if (!this.#bidderValid) {
        show_message('Cannot save back to the table, the bidder field is still invalid', 'warn', 'ai_result_message');
        return;
    }
    // now perform a pre-save validation, the bidder we already know is valid or empty (still valid, if empty)
    // we only need to do this for art, as there are no interdependencies for the other types
    if (this.type == artItemTypes.ART) {
        let minPriceValue = Number(this.#min_bidField.value);
        if (minPriceValue < 1) {
            show_message("Cannot update: Minimum must be $1.00 or more", 'warn', 'ai_result_message');
            return;
        }

        let salePriceValue = Number(this.#sale_priceField.value);
        if (salePriceValue > 0 && minPriceValue >= salePriceValue) {
            show_message("Cannot update: Minimum must be less than sale price", 'warn', 'ai_result_message');
            return;
        }

        let finalPriceValue = Number(this.#final_priceField.value);
        let checkPriceValue = this.#statusField.value == artItemStatuses.QUICKSALE ? salePriceValue : minPriceValue;
        let checkPriceField = this.#statusField.value == artItemStatuses.QUICKSALE ? 'sale price' : 'minimum';
        if (finalPriceValue > 0 && finalPriceValue < checkPriceValue) {
            show_message("Cannot update: Final price must not be less than " + checkPriceField, 'warn', 'ai_result_message');
            return;
        }
    }
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
