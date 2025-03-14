# ConTroll Version 1.2 Release Notes

## Version 1.2: Reports and Finance Release, Release Date: 2025-03-20

# Major Configuration Changes in 1.2:

* **ConTroll now requires at least PHP 8.2** and uses the rewritten Square API
* Addition of PHPOffice to composer.json for reading/writing XSLX, XSL, ODT files

* New Database Patches   
  * 42: New lookup Role, cleanup of mergePerid stored procedure  
  * 43: Remove base role, replace with gen-rpts role, new glNum and glLabel fields for memList and exhibits, cleanup of payment category enum for new 
    exhibitor portals
      
* New/Changed/Deleted Config File Entries:  
  * Removed the Square apiversion line, it is no longer used in the new API
  * No new entries were added
        
* New Scripts: None

# Major changes by application:

## ConTroll: (Administrative Back End to the system)

* Moved most of the configuration out of the Admin menu to the registration Admin menu and the Exhibitors menu  
  * Admin now has:  
    * Users: ConTroll users  
    * Main Menu: Sorting the main menu items  
    * Atcon Users: Duplicate of the ‘Users’ tab within Atcon Administration  
    * Atcon Printers: Duplicate of the ‘Printers’ tab within Atcon Administration  
  * Moved to Registration admin:  
    * Registration List
      * Added Manager column  
      * Restrictred Refund, Paid editing to "Finance" Role
      * Suppressed Rollover if oneoff=1
      * Added the ability to add notes to any registration
    * Current Convention Setup  
      * glNum and glLabel columns added
    * Next Convention Setup  
      * glNum and glLabel columns added 
    * Membership Configuration  
      * Can still edit Active, Badge Label for locked rows 
    * Custom Text:  
      * Added direct Markup support to the app. No longer requires a browser add-on
    * Policies (No changes)  
    * Interests (No Changes)  
    * Membership Rules:  
      * Several bug fixes 
    * Merge People (No changes)  
* People  
  * Several bug fixes
* Finance
  * Payor Payment Plan Display added: Actions to follow in a future release
* Free Badges  
  * Put find/add at the top instead of the bottom
  * Cleaned up issue where second select list was sometimes displayed
* Reg Lookup
  * A new read only screen to search for registrations.  Only returns a limited number of registrations unlike registration list
* Coupon (no Changes)
* Exhibitors  
  * Bug fixes  
  * Added some Custom Text entries 
* Art Control (No Changes)
* Club (No Changes)
* Membership - Rewrite of Graph
* Reports
  * New Reports added to release: Registration History, No Show
  * Fixed Reports: Duplicate Memberships

## Portal:

* Bug fixes
* Recompute payment plans on paying more than the minimum amount
* Ability for mamangee's to pay for memberships bought for them by others, even if they come from a payment plan
* Warning cleanup in logs by adding more array_key_exists checks

## Atcon:

* Bug Fixes

## Exhibitor (Vendor Portals)

* Bug Fixes

## Online Registration

* Added ability to sell year ahead memberships

## Global Changes

* Universally allow /r for refused fields in profiles
* /r also suppresses USPS checks if they are enabled
* Portal and Exhibitor vendor pages are now their own 'source' for payments
* Unused function cleanup (deletion)
* Move registration notes to it's own class and used it everywhere (pos, reg-admin, etc.)
* For no-shows report make it take conid, con name, or label for the report

# Jira Items Closed:

~~~text
Issue key	Summary	Status
C1-1	Rewrite Membership page with newer D3 and proper use of status and mem types in the query	Done
C1-199	For Portal add config for At Con Reg to bring up you and people all managed together. Limited to manager of people. Need good instructions for At Con.	Done
C1-209	Change text in vendor portal regarding purchasing memberships	Done
C1-245	For Square: Break all individual Exhibitor purchases into items: membership, item purchased, exhibitor name, Quantity	Done
C1-88	Check that we changed the table purchase square field from items to notes and to update the columns used from pre-art integration to post-art integration	Won't Fix
C1-260	Create new isPrimary and use it to detect the various type of primary memberships	Done
C1-205	Install Montreal Instances.	Done
C1-215	Make sure the Patches need from B61 Boskone Only are in B62	Done
C1-25	Add ability to purchase more than one Oneday memberships (all different)	Done
C1-230	Green box after artist purchase is confusing.	Won't Fix
C1-242	Fix how Coupons work on Plans (see Bug from Seattle).	Won't Fix
C1-42	Atttendence is using con end date as a datetime, but its a date, thus it's ending it as midnight of the day, losing the last day of the con	Won't Fix
C1-29	Add active flag to rules	Won't Fix
C1-44	Create configable 'list of categories' that are like standard and virtual (for use in portal and everywhere else)	Won't Fix
C1-76	Add calls to validateEmailAddress to atcon and reg_control	Won't Fix
C1-27	In artist/vendor portal, format_currency is not being used everywhere, especially in the space price dropdown builds	Won't Fix
C1-165	Determining which regs a payment applies to, for atcon/reg payment sections, ability then to back out a coupon in main in reg/atcon, and find all payment records for the receipt module	Won't Fix
C1-21	Write list of standard membership Types, and Categories and their implicit meaning/logic, and add them to initial database creation	Done
C1-241	What should People do with new people created by Exhibitors? (issue is delete)	Done
C1-214	Problems trying to create an account using the login link process.	Done
C1-4	Next: it is the same as current in the code, just year based	Done
C1-5	membership config: add grouping to categories for use in portal, add fixed fields, add notes	Won't Fix
C1-255	In Registration list show discounts applied to a membership in a seperate column than just paid amount.	Won't Fix
C1-248	Artist & Dealer Profile should have a question about previous convention	Won't Fix
~~~



