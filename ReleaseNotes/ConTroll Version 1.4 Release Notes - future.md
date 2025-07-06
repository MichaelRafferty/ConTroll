# ConTroll Version 1.4 Release Notes

## Version 1.4: Art Show/Configuration/Passkeys, Release Date: TDB

# Major Configuration Changes in 1.4:

* New Database Patches   
  * 47: Marketing: Addition of more configurable text for marketing emails
  * 48: Artshow: adds further indicies and GL Numbers for art show items.  Adds site selection tokens for worldcon's
  * 49: Art Inventory: Adds tracked notes to artItems for use by in-line inventory
  * 50: Passkeys: Adds tables and fields to hold and use passkeys
      
* New/Changed/Deleted Config File Entries:  
  * Splitting of the config file into three sections, see System-Documents-and-Coding-Standards/Configuration-File-Format-and-Usage for details.
  * Rewrite of the configuration files with a \[global\] section for system wide defaults and \[section\] overrides.
  * Addition of options and keys to support Passkeys including
    * Support for top level domain passkey RP domain or full application pass key RP domaion
        
* New Scripts: None

# Major changes by application: 
 * All applications have bug fixes incorporated and will not be listed for each application.

## ConTroll: (Administrative Back End to the system)
* Support for login with passkey and mapping passkey id to user id/perid
* All Configuration Screens: 
  * Added support for "Download Excel" to export the configuration tables to excel files.
  * Added additonal download support to additional configuration tables
* Registration
* Exhibitors
  * Clean up of reporting of spaces and fees to the Square Order

## Atcon:

* Enhance ment of Art Show Cashier to support in-line inventory update
  * Entered to Checked State transition
  * Quicksale vs Sold via Bid Sheet price and bidder updates
  * Auction price and bidder updates

## Exhibitor (Vendor Portals)

* Clean up of square reporting.  Standardization of receipt emails.

## Global Changes

* Addition of the conf.ini file read and lookup routines

# Jira Items Closed: (Jira not yet updated)