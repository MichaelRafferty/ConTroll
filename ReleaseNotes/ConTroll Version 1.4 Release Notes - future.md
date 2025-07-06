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
  * Many fields have been cleaned up, please see the sample files for all of the parameters now supported.  While R1.4 will support a single configuration 
    file, you should migrate from the one file to the three files during 1.4.  That support is considered Depreciated in 1.4 and will be removed in 1.5.
        
* New Scripts: None

# Major changes by application: 
 * All applications have bug fixes incorporated and will not be listed for each application.

## ConTroll: (Administrative Back End to the system)
* Prepared for support for login with passkey and mapping passkey id to user id/perid (not yet completed)
* All Configuration Screens: 
  * Added support for "Download Excel" to export the configuration tables to Excel files.
  * Added additonal download support to additional configuration tables
* Reports
  * Support for PHP style reports in the menu system. This is slightly different than the old PHP reports but can be used for reports that are more complex 
    than the report processor can handle.
* Exhibitors
  * Clean up of reporting of spaces and fees to the Square Order

## Portal:
* Support for creating and logging in with passkeys.

## Atcon:

* Enhancement of Art Show Cashier to support in-line inventory update
  * Entered to Checked State transition
  * Quicksale vs Sold via Bid Sheet price and bidder updates
  * Auction price and bidder updates

## Art Control
* Cleanup of editing the art items
* Enabled adding new art items

## Exhibitor (Vendor Portals)

* Clean up of square reporting.  Standardization of receipt emails.
* Login with passkey if passkey created for the same email in the reg portal.

## Global Changes

* Addition of the conf.ini file read and lookup routines

# Jira Items Closed: (Jira not yet updated)