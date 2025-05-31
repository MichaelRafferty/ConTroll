# ConTroll Version 1.3 Release Notes

## Version 1.3: Square Terminal, Release Date: 2025-06-01

# Major Configuration Changes in 1.3:

* **ConTroll now requires at least PHP 8.2** and uses the rewritten Square API
* Addition of PHPOffice to composer.json for reading/writing XSLX, XSL, ODT files

* New Database Patches   
  * 44: Payment Cleanup: New Auth Rols, move finance functions to Finance Tab 
  * 45: Square Terminals: New terminals table, support for tax tracking and use of Square to calculate taxes, removal of historic but no longer used field 
    memGroup from the memLabel view, support for order id and payment id for Square tracking
  * 46: Code Cleanup: making perinfo and newperson not allow nulls and be empty string default, add source to regActions
      
* New/Changed/Deleted Config File Entries:  
  * Change in default value for compLen to one year
  * debug: square = 0/1: logging of all square requests/responses
  * cc:location<subname> = alternative location id's for use by terminals, the subname is location name
  * log: term = path to the terminal log file used by square logging
  * portal: direct = allow direct login to portal accounts without a password for testing, requires test=1 as well
  * portal:businessmeetingURL = url to the virtual businessmeeting site
  * portal:businessmeetingBtn = text for the business meeting button
  * portal:siteselectionURL = url to site selection voting 
  * portal:siteselectionBtn = text for the site selection voting button
  * portal:virtualURL = url to the virtual portal
  * portal:virtualBtn = text for the virtual portal button
        
* New Scripts: None

# Major changes by application: 
 * All applications have bug fixes incorporated and will not be listed for each application.

## ConTroll: (Administrative Back End to the system)

* Registration
  * Offline Credit Card now bypasses using cc_payOrder to not duplicate the payment in the credit card vendor's transactions
* Membership
  * Complete rewrite of the screen using a more modern graphing package, including click to zoom.
* Attendence
  * Complete rewrite of the screen using a more modern graphing package, including click to zoom.
* Exhibitors
  * Auto payment of space allocations that are $0 due

## Portal:

* New virtual convention button on the "This account information" line
* Support for HugoVoting, Business Meeting and Site Selection sites via buttons
* Cleanup of the top of the "portal" page information

## Atcon:

* New support for Square Terminals for online credit cards including terminal management
* Rewritten Art Show Point of Sale "Art Show Cashier"
  * Includes global (all regions) or region filtering
  * Bar Code Reader improvements

## Exhibitor (Vendor Portals)

* Profile editing improvements

## Online Registration

* Credit card cleanup

## Global Changes

* Unused function cleanup (deletion)

# Jira Items Closed: (Jira not yet updated)