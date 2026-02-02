# ConTroll Version 2.0 Release Notes

## Version 2.0: Start of Portal UX Rewrite
### Release Date: TBD

# Major Configuration Changes in 2.0:

* New Database Patches
  * 56: Age/Art: Items related to the actual move of age into the profile, rename of some of the art items statuses, 
  Custom Text changes related to age moving into the profile, Modifications for the new Token Library in the backend
      
* New/Changed/Deleted Config File Entries: 
  * reg_admin.ini:
    * [global]
      * server=    URL for the default reg server (used by portal and onlinereg)
      * passkeyRpLevel=    Minimum is now 3, not 2
    * [reg]
      * registrationpage= no longer used
    * [portal]
      * ageRestriction=   comma separated list of Age Types than are precluded from logging into the Registration Portal
    * [controll]
      * controllsite=     URL to controll backend site 
      * redirect_base=     URL for oauth login redirect
      * New Auth Token timeouts
        * tokenExpireHrs=   number of hours before login token becomes invalid, default 8 (can be fractional)
        * authExpireHrs=   number of hours before the role permissions are re-fetched, default 0.25 
        * expireGrace=   how many hour before expire does the system auto-refresh the token
      * New log file: logins, tracks controll and atcon logins
        * priorRolloverYears=     Number of prior years to allow "Rollovers" in Registration List Edits (default 2)

  * reg_secret.ini:
    * new comments about SQL_MODE and path (client path)
  
  * reg_conf.ini  
    * bundlememberships=  enable/disable flag for bundle memberships
    * viewPriorLimit=   Minimum conid for viewing prior data
    * artEditYear=    Mimimum conid for allowing edit of art show data in Art Control
    
* New Scripts: None

# Major changes by application: 
 * All applications have bug fixes incorporated and will not be listed for each application.

## ConTroll: (Administrative Back End to the system)
* New Authentication Token support with better timeout recovery (refresh of the token does not loose your current actions)
  * Support of Passkey login in addition to google
* Usage of system wide Profile editor and validation
  * Moving of Current Age into the Profile
* Reg Admins can change a person id
* Support for creating and editing Bundle Memberships
* Point of sale:
  * Changes for bundles
  * Changes for Age in Profile
* Enforcement of Managed status for those with memberships of category managed
* Restriction of assigning a managed membership to a non managed person
* Reg List screen can show prior and future years and edit some of them
* Art Control can show prior years and edit some of them

## Portal:
* Rewrite of the entire home page User Experience
  * Display of all people under your account with the current memberships "above the fold"
  * Display of a single persons current profile and interests "below the fold"
* Simplification of the "Make Purchase" User Experience
* Moving Payment History to its own page (menu item)
* Restriction of access by configurable Age Types
* Enforcement of Managed status for those with memberships of category managed
* Restriction of assigning a managed membership to a non managed person

## Atcon:

* Point of Sale
  * Changes for bundles
  * Changes for Age in Profile
  * Enforcement of Managed status for those with memberships of category managed
  * Restriction of assigning a managed membership to a non managed person

## Exhibitor (Vendor Portals)

* Support of the common Profile Editor
* New Password Reset system

## Global Changes

* Common Profile editor with validations included address validation when used (USPS module)

# Wrike Items Closed:

* Change how exhibitor portal password resets work
* Create Art Show Sold Report
* Make quick sale options
* Pad in and Out buttons to make them easier to click
* PDF output pages cannot show status messages, shows blank screen
* rules editing bugs
* Ability to move Current GoHs into reserved range (renumber Perid)
* Add "Bundle: " memberships
* Add to atcon online credit card ability using the "square WEB SDK" for the credit card information
* Add to vendor portal checking the policy items for memberships added that way as an option
* add year select with limited functions to reglist
* Art Inventory find doesn't handle regions, the tab is there but it isn't passed in
* Create new common token library and use it to replace what is in Controll's google_init
* Exhibitors pay should also trigger the payment emails
* Implement Portal Redesign in PHP
* In Exhibitor New Region Check Short Name is not in use
* Make Portal Validation a common JS Library
* Move Age to Perinfo
* New Category Managed
* now that age is in perinfo, rules needs to look at people ages, not just membership ages
* Payment Page redesign
* add abbility for art control to see prior con

# Items in Progress, but not in 2.0

* Redesign of the cart in the portal
