# ConTroll Version 2.3 Release Notes

## Version 2.3: ???
### Target Release Date: 2026-09-01

# Major Configuration Changes in 2.3:

* New Database Patches
  * 59: 2.3:
    * Addition of new custom text items for exhibitor section reminder to attend (artist, vendor, fan, exhibitor)
    * Lenghten the description column in payments for updated usage
    * Added rptGrouping to memList to deal with the registration totals report, separating it from GL Label
    *
      
* New/Changed/Deleted Config File Entries: 
  * reg_admin.ini:
    * [atcon]
      * badges=absolute path to hold printouts from printer 0
        * this path needs pdf and txt subdirectories
      * opennnote=who can create/edit a person open note
        * Used to be only a manager
        * This option will allow a choice of any, active, manager or none similar to the discount payment method has the same choice.
        * The default value will be manager if this line is not entered.
  * reg_secret.ini:  None
  
  * reg_conf.ini:
    * [con]
      * cashRoundingUnit=units to round cash transactions, default 1
        * Will round to the nearest unit currency
        * Possible values are 1, 5, 10, 25, 50
        * The values are automatically divided by the curency multiplier (USD = 100) 
      * cashRoundingGLNum=   (default empty string)
        * All cash transaction rounding amounts will be documented as to be assigned to this GL account
        ;; N:cashRoundingGLNum
    * [controll] 
      * useGLcodes=0,1 (default 1) to enable the use of GL num/Label throught the system
      * opennote=manager,active,any,none
        * who can edit the perinfo open note field
          * manager - requires reg_admin privs
          *	active - also requires reg_admin privs
          *	any - anyone who uses mail in registration can create/edit an open perinfo note
          *	none - disable the creation/edit of an open note
      * showCartDescription=0,1
        * If the memList has cart descriptions, show them in the add membership modal if enabled
        * 0=suppress, 1=enable
      * showCartDate=0,1
        * Show the active date range for the membership in the membership button or row
        * 0=hide, 1=show
    * [atcon]
      * opennote=manager,active,any,none
        * who can edit the perinfo open note field
          * manager - requires reg_admin privs
          *	active - also requires reg_admin privs
          *	any - anyone who uses mail in registration can create/edit an open perinfo note
          *	none - disable the creation/edit of an open note
      * showCartDescription=0,1
        * If the memList has cart descriptions, show them in the add membership modal if enabled
        * 0=suppress, 1=enable 

* New Scripts: None

# Major changes by application: 
 * All applications have bug fixes incorporated and will not be listed for each application.
 * Support for Stripe as a payment processor in addition to Square
 * Preparation for upcoming support for currencies that require rounding cash transactions. 
  Support is for rounding to 1, 5, 10, 25, 50.  Whether that is cents or whole units is 
   automatically determined by the currency's currency multiplier. For example, USD = 100. 

## ConTroll: (Administrative Back End to the system)
* Support for accessing badges and printouts set to printers starting with 0 as their queue name.
* Update of registration totals report to use the rptGrouping column (Report Grouping) instead of GL Label
* Added number of days option to the expiration report
* Sending test emails now processes test values for the macro substitution variables.
* Support for editing the open note under configuration control
* Support the cart description and start/date ranges in membership add/edit under configuration control

## Portal:
* 

## Atcon:
* Improvements in flow and button prompting when paying for an order, including how much is going to be paid, and send to terminal wording
* Support for editing the open note under configuration control
* Support the cart description in add/edit under configuration control

## Exhibitor (Vendor Portals)
* Attendance Reminder Email with custom text contents to remind artist/vendor/fan/exhibitor of the upcoming convention and what to do on arrival
* 

# Wrike Items Closed:
* 

