# ConTroll Version 2.1 Release Notes

## Version 2.1: Completion of Portal UX Rewrite
### Release Date: 2026-05-01

# Major Configuration Changes in 2.1:

* New Database Patches
  * 57: 2.1:
    * Items related to new portal cart including membership description
    * Revenue GL information for art sales
    * Exhibitor table history tracking (going forward from 2.1)
    * Membership badge label individual override support
    * Custom text for more emails
      * Post Convention Survey
      * Artist Inventory Entry Reminder
      
* New/Changed/Deleted Config File Entries: 
  * reg_admin.ini:
    * [con]
      * maxHistoryYears=  Overall number of years of history data to retain.  If missing, keep all.
      * maxPerinfoHistoryYears=  Override of maxHistoryYears for perinfoHistory table, if missing use maxHistoryYears.
      * maxRegHistoryYears=  Override of maxHistoryYears for regHistory table, if missing use maxHistoryYears.
      * maxExhibitorHistoryYears=  Override of maxHistoryYears for exhibitorHistory table, if missing use maxHistoryYears.
      * maxArtItemsHistoryYears=  Override of maxHistoryYears for artItemsHistory table, if missing use maxHistoryYears.


  * reg_secret.ini:  None
  
  * reg_conf.ini  
    * defaultCountry=  3 Character ISO code of the default country for new addresses, if missing, use first in csv file (USA)
    
* New Scripts: None

# Major changes by application: 
 * All applications have bug fixes incorporated and will not be listed for each application.

## ConTroll: (Administrative Back End to the system)
* Support for memList override badge label
* Support for File Manager in Admin, Registration Admin and Exhibitor tabs.  This allows for easy uploading of files to the server.
* Date filtering in tables using >, >=, <, <=, and n (now) with n followed by a date for what is the desired now date.
* Merge People now uses the same result screen as Match People allowing editing of the fields
* Point of sale:
  * Support for online credit card (typing number into Square)
* Free Badges: Can change membership type for a watched person until the badge has been printed.
* Finance: Can now cancel payor payment plans.
* Exhibitors:
  * support for access to prior years
  * support for art sales revenue gl information
  * Support for manually sending inventory reminder emails
  * New report for vendors or artists for a specific conid

## Portal:
* New cart with cart descriptions
* Simplification of the "Make Payment" User Experience allowing selection of what to pay for both your account and the accounts you manange as part of the 
  normal payment process.
* Closing some reported security issues.

## Atcon:

* Point of Sale
  * Support for online credit card (typing number into Square) for both membership and artsales

## Exhibitor (Vendor Portals)
* Added payee name to artist profile

# Wrike Items Closed:

* add < > type of processing to con setup date filter fields
* Add ability for exhibits admins to see prior year registrants and what they did like current year registrants
* Add an override column in memList to override the category badge label for print only.
* Add cancel plan to Finance / Payor Plans
* Add column to exhibitor information in the 'region' tabs of exhibitors information to show in which regions this exhibitor is active
* Add new exhibitor history to show changes in exh info tab
* add offline credit card to art show cashier
* Add PHP report to upload square log and compare it against ConTroll data for reconcilation
* Add reminder email to Exhibitors Controll space requests for those who have not added any inventory
* Add reports for dealers or artists for a conid
* Add revenue gl code to regions for this year
* Art Inventory Barcode Scan (new art inventory main level task)
* Art Inventory Buttons Color change on click (disable/enable)
* Backend asking artists for their tax id
* Change/Change & Pay should use approved as starting point
* combine payments of mine and others with forced row select
* Create config entry in reg_admin for max years of History tables and flush them at rollover
* Create new Artist field:  Name to make payment to (artistPayee)
* Enhance Match Member to allow new/old manager field
* For unprinted comp memberships in free badge, allow editing of which comp
* Image Upload
* Make account settings, provider a pulldown
* Manager should be able to pay for items added to cart by managees
* Move countryCode.csv load to a common file and provide config default
* New Status Donate
* Portal needs to allow them to save their profile with required policies unchecked
* Redesign the Cart
* Reported Security Issues in Portal
* show same edit screen in merge as in match people
* When logging in to exhibitor portal check that the specific portals required fields are entered
