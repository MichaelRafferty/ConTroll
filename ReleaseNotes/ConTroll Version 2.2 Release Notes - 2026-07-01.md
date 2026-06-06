# ConTroll Version 2.2 Release Notes

## Version 2.2: ???
### Release Date: 2026-07-01

# Major Configuration Changes in 2.2:

* New Database Patches
  * 58: 2.2:
    * More email custom text fields predefined
    * Update of triggers to support Deceased and Former GoH flags
    * Former GoH Membership Category
    * Notes and End Date added to Interests
    * Removal of Philcon defaults in some text entries switching to variables in the custom test
    * Art Sales - Pickup by alternate person support
    * Taxability by item type being sold (Memberships, Space, Fees, Art, etc.)
    * Move of fullName and fullAddress to computed fields from query parameters (allows for support first...last or last, first... formats if desired)
      
* New/Changed/Deleted Config File Entries: 
  * reg_admin.ini:
    * [global]
      * passkeyRpLevel no longer supports 2, only 3 and higher or d

    * [con]
      * emailDomains=comma separated list of valid sending email domains for the system for config file validation (required)
      * Removal of support for: taxRate, taxLabel, taxuid, these have moved to the tax database table.

  * reg_secret.ini:  None
  
  * reg_conf.ini  
    * sending email variables are now marked to need validation for proper domain: regemail, regadminemail, artist, vendor
    * formerGoH= 
      * 0=disable formerGoH support
      * 1=enable
    * conRoles=
      * 0=disable conRoles support
      * 1=enable tracking of conRoles in the profile
    * showConRoles= 
      * 0=conRoles are hidden from the user
      * 1=assigned conRoles are shown to the user
      * 2=all conRoles are shown to the user, even if not assigned
    * artistOpen, vendorOpen, exhibitOpen, fanOpen
      * Individual exhibitor portal open flags
      * 0 = closed
      * 1 = open
      * Note: admin flag for the exhibitors portal is still required and controls all exhibitors portal access.

* New Scripts: None

# Major changes by application: 
 * All applications have bug fixes incorporated and will not be listed for each application.

## ConTroll: (Administrative Back End to the system)
* Support for formerGoH and Deceased person tracking
  * Limited ability to perform other functions on people marked deceased.
* Registration-Admin
  * Addition of Notes and End Date to Interests
  * Addition of defining Con Roles
* Registration
  * Support for the new tax structure
* People
  * Support for editing Deceased and Former GoH flags
    * reg-admin permission required to edit Deceased
    * reg-staff permission required to edit Former GoH
* Finance
  * Added to the sales tax configuration tab the ability to edit taxability by item type being sold (Memberships, ...)
* Exhibitors:
  * Ability to email control sheets to the artist
* Reports:
  * Addition of PHP reports with tabulator display
  * New regTotals report showing registations by label and month

## Portal:
* Support for notes and end date on interests
* Addition of convention role display
* Several wording changes for clarity

## Atcon:

* Point of Sale
  * Support for Deceased
  * Support for the new tax structure
* Art Show Cashier
  * Support for the new tax structure
  * Support for alternate pickup person
  * Improvements to inline inventory
* Art Alt Pickup
  * New tab for controlling alterate pickup people

## Exhibitor (Vendor Portals)
* Allow Artist Inventory Import in both before and during entry of inventory
* Support for new tax structure

# Wrike Items Closed:
* Add Field "Former GOH" and Deceased to PerInfo. OR ADD Table of special identifiers for each person.  Former GOH, etc.
* Add convention role table to configuration and profile
* Add email control sheet to artist as an action on exhibtor space management tab
* Barcode inventory not setting to auction
* Change custom text 'all' mode to show tag for all entries, and still suppress the placeholder text
* Fix bug: Can print virtual, should not be allowed
* Install, config edit, exhbits config, and other email from: sources need validation
* Interests: Create an End Date for Interests.
* Interests: add a "Notes" box (that displays a question in the box
* Invalidate relevant passkeys when email address changes (partially complete, remainder waiting for Email Verification of exhibitor portal)
* Make Age Type ALL required
* Make fullName and fullAddr DB computed fields
* Match People - move buttons to reduce mouse movements
* Need ability to allow alternate person to pick up art
* New PHP report showing Attendance for Worldcons
* Process terminal payment issues (artshow) (first pass, not fully resolved)
* Provide separate open flags for the portals
* Rewrite remaining emails in email.php as custom text
* Rewrite volunteer rollover table method due to potential bug
* Tax application: tax list which items it applies to and use of those fields in payment processing

