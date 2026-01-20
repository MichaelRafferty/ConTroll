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

  * reg_secret.ini:
    * new comments about SQL_MODE and path (client path)
  
  * reg_conf.ini  
    * bundlememberships=  enable/disable flag for bundle memberships
    
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

* Exhibitor Portal Password Reset
* Art Show Sold Report
* Quick Sale Configuration Options
* Make buttons in Art Inventory in Atcon easier to click
* Added ability for PDF output pages to show error and status messages
* Rules editing bugs
* Renumber Perid (Ability to move Current GoH's into reserved range)
* Added Bundle Memberships
* Add online credit card ability to atcon and mail in reg using the "square WEB SDK"
* Add region support to Art Inventory
* New common token library for controll back end
* Portal home page redesign
* Make profile validation a common Javascript library
* Move Age into perinfo (code support)
* Add profile age to limit age rules in membership rules execution
* New Category Managed

# Items in Progress, but not in 2.0

* Redesign of the cart in the portal
