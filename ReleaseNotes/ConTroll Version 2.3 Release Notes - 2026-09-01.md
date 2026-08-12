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

  * reg_secret.ini:  None
  
  * reg_conf.ini:
    * [controll] 
      * useGLcodes=0,1 (default 1) to enable the use of GL num/Label throught the system

* New Scripts: None

# Major changes by application: 
 * All applications have bug fixes incorporated and will not be listed for each application.
 * Support for Stripe as a payment processor in addition to Square

## ConTroll: (Administrative Back End to the system)
* Support for accessing badges and printouts set to printers starting with 0 as their queue name.
* Update of registration totals report to use the rptGrouping column (Report Grouping) instead of GL Label
* Added number of days option to the expiration report
* Sending test emails now processes test values for the macro substitution variables.

## Portal:
* 

## Atcon:
* Improvements in flow and button prompting when paying for an order, including how much is going to be paid, and send to terminal wording
* 

## Exhibitor (Vendor Portals)
* Attendance Reminder Email with custom text contents to remind artist/vendor/fan/exhibitor of the upcoming convention and what to do on arrival
* 

# Wrike Items Closed:
* 

