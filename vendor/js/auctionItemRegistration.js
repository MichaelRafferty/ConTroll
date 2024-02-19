/* Auction Item Registration related functions
 */
class AuctionItemRegistration {
    
// items related to artists, or other exhibitors registering items
    #item_registration = null;
    #item_registration_btn = null;


// init
    constructor() {
        var id = document.getElementById('item_registration');
        if (id != null) {
            this.#item_registration = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#item_registration_btn = document.getElementById('item_registration_btn');
        }
    }


    open() {
        this.#item_registration.show(); 
    }

    close() {
        this.#item_registration.hide(); 
    }
}

auctionItemRegistration = null;
// init
function auctionItemRegistrationOnLoad() {
    auctionItemRegistration = new AuctionItemRegistration();
}

