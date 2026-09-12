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
  * reg_secret.ini:
    * 
  * reg_conf.ini:
    * 

* New Scripts: None

# Major changes by application: 
 *  

## ConTroll: (Administrative Back End to the system)
* 

## Portal:
* 

## Atcon:
* Support for rounding in square and stripe
   * Square via the OrderRoundingAdjustment field of the order
   * Stripe via a metadata value on the payment record
   * Rounding amount is maintained in the transaction table
   * Payment is the actual amount paid

## Exhibitor (Vendor Portals)
* 

# Wrike Items Closed:
* 
