# ConTroll Version 2.4 Release Notes

## Version 2.4:
### Target Release Date: 2026-12-28

# Major Configuration Changes in 2.4:

* New Database Patches
  * 60: 2.4:
    * Added rounding to transaction for cash rounding
      
* New/Changed/Deleted Config File Entries: 
  * reg_admin.ini:
    * cashRounding: in base currency units, what amount to round to.  For USD, currency unit is 100, so rounding would be 5 for $0.05.
    * javascript: in [log], new log file entry for errors captured from the client side javascript
    * in worldcon section
      * worldcon: must be 1 for enabling auto cancellation of WSFSNOM
      * wsfsCategory: memCategory of the WSFS memberships (default wsfs)
      * wsfsnomCategory: memCategory of the WSFS Nomination membership (default wsfsnom)
  * reg_secret.ini:
    * lumiSalt: salt to use for computing LUMI password for busines meeting login
  * reg_conf.ini:
    * agePurchaseRestriction: ages not allowed to pay for memberships in the portal
    * ageRestriocton: ages not allowed to login to the portal
    * pastdueDaysNext: When to include next payment in past due payments on plans
    * artistLogo, artistLogoAlt, artistLogoText: overrides of the logos for the Artist Portal
    * vendorLogo, vendorLogoAlt, vendorLogoText: overrides of the logos for the Vendor Portal
    * exhibitorLogo, exhibitorLogoAlt, exhibitorLogoText: overrides of the logos for the Exhibitor Portal
    * fanLogo, fanLogoAlt, fanLogoText: overrides of the logos for the Fan Portal

* New Scripts: None

# Major changes by application:
* All Applicatiomns
   * Support for adding an Emergency Contact field to the profile

## ConTroll: (Administrative Back End to the system)
* Additional options on registration list
   * P takes you to people to edit that person
   * M takes you to match to match that newperson
* GL code entry moved to Finance Tab
   * Use of GL Codes is now a select pulldown in other tabs
* Some reports have been moved to the General Reports tab to allow more users access to them
* One off conventions can see members as well as registrations in Reg Lookup

## Portal:
* Ages limits for access to and paying for memberships are now supported in the portal.

## Atcon:
* Support for rounding in square and stripe
   * Square via the OrderRoundingAdjustment field of the order
   * Stripe via a metadata value on the payment record
   * Rounding amount is maintained in the transaction table
   * Payment is the actual amount paid
* Support for auto polling the terminal for completion

## Exhibitor (Artist/Vendor/Exhibitor/Fan Portals)
* Addition of overrides of the logos on a per portal basis

# Wrike Items Closed:
* 
