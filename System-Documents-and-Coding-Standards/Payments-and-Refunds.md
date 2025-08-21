# Payments and Refunds
ConTroll tracks all payments and refunds using several table and the credit card processor.
This document is based on the Square Credit Card Processor, although the process would be similar with any system.

## Tables
### Transaction
The transaction table is the base table to tie all items together in ConTroll.
From the payment point of view the following fields in transaction are relevant:

- price: The money amount of the items covered by this transaction - pre-tax
- tax: The sales tax money amount of the taxable items in this transaction
- withtax: price + tax
- paid: The money amount collected for this transaction via discounts, cash, check or credit cards
- couponDiscountCart: the total amount of discount on a cart wide basis, not per registration, can be discount element or a coupon element. Square allocates 
  this by prorationing the discount across all discountable items in the transaction.
- couponDiscountReg: the total amount of discounts applied on a per registration item
- change_due: for cash transactions the amount of change returned to the customer. (paid + change_due = cash tendered)
- type: Not strictly payment, but which section/process created this transaction:
   - Exhibitors Portal elements:
      - Artist
      - Exhibitors
      - Fan
      - Vendor
   - Point of Sale elements: (suffixed by / and operator perid)
     - artpos: Atcon Art Show Cashier
     - atcon: Atcon Reg Cashier (and Reg Check In for non payment transactions)
     - regctl-adm-???: Registration Admin
     - regctl-reg: Registration
     - regportal: Registration Portal
   - Back End (controll/) elements:
     - freebadge (suffixed by / and operator perid): Free Badges
   - Others: scripts used to import records and manual changes.  The type name is supposed to be descriptive of the action.
- orderId: credit card system order identifier for this transaction
- orderDate: timestamp the order was created
- paymentId: credit card system payment identifier if a payment was made
- checkoutId: credit card system device transaction identifier if a device was used to make this payment
- paymentStatus: text string used by the credit card system for the current state of this transaction. Examples:
  - ORDER: order created, no payment request yet submitted
  - PENDING: Device transaction pending
  - IN_PROGRESS: Device transaction in progress
  - CANCELED: Device transaction canceled, payment canceled
  - CANCEL_REQUESTED: Cancelation in progress
  - COMPLETED: Device transation and payment complete

### Payments
The payments table tracks money events against a transaction, both payments and credits. This includes payments, discounts, coupons, and refunds.
The following fields are relevant to this discussion:

- transid: tie to the relevant transaction
- type: which type of payment/credit is involved:
  - cash
  - check
  - card: off line (not via ConTroll) credit card processor/device
  - credit: credit card via the ConTroll credit card process
  - discount: cart wide discount (coupon cart discount or discount item in POS)
  - coupon: per reg coupon item
  - refund: refund of a prior payment (???, do I need a new table for this?)
    - (items to track, somehow: prior payment id, prior transaction id)
  - other: manual entries
- category: payment request item category
  - reg: registration items
  - artshow: art items
  - artist, vendor, exhibitor, fan: exhibitor space items (with optional embeded registrations)
- source: subsystem that created the payment
  - artist, vendor, exhibitor, fan: exhibitor portal
  - cashier: atcon
  - controll: back end
  - portal: registration portal
  - onlinereg: online registration (non portal system)
  - import: data import script
- pretax: abount before sales tax
- tax: sales tax
- amount: pretax+tax (amount of the payment)
- cc: last 4 digits of the credit card for type='credit'
- nonce: 
  - type: credit: identifier for the credit card provided by the processor
  - type: cash: CASH
  - type: check: CHECK
  - type: card: CARD
  - type: other: admin (and perhaps others)
- cc_txn_id: credit card processor transaction id
- cc_approval_code: credit card processor approval code
- txn_time: transaction type from credit card processor
- userPerid: logged in perid of person making the payment
- cashier: atcon cashier
- receipt_url: receipt URL from credit card processor
- status: payment status
  - APPLIED: discount applied
  - APPROVED: credit card approved, processing not finished
  - COMPLETED: payment completed
- receipt_id: credit card processor receipt id
- paymentId: credit card processor payment id

## Payment Processing
This is the overall steps involved in processing a payment.
There are two variants: Using a device controlled by ConTroll (Square Terminal) or purely payment API based.
### Common steps
Every payment transaction uses the following steps:
1. Validate the amounts to detect an error before it gets to the processor
2. Get a new master transaction if none exists
3. If Space:
   1. Update space information
   2. Build the memberships associated with the space
4. Build an Order Item in the credit card processor (even if it is not a credit card transaction)
   1. use cc_buildOrder to build the order for this credit card processor type
   2. Update the tax, amount, order id and status in the transaction record (we let the credit card processor compute the sales tax)
TODO: verify that all paths are doing #2 above

### Non Device (Terminal) Path
1. If not passed, etch the order
2. Verify the amounts against the payment amounts for tax and total amount due
3. Use cc_payOrder to process the payment with the processor. Passes:
   1. Request:
      1. source:
      2. nonce: from the web sdk for credit cards, hard coded for cash/check/card
      3. totalAmt: amount of the payment
      4. orderId: the order id fetched above or null if no order
      5. customerId: our reference number for the customer:
         1. specified in the order above if order is not null
         2. conid-person identifier if order is null
      6. referenceId: a unique id, as in transid-pay-<unixtime in seconds>
      7. transid: master transaction
      8. preTaxAmt
      9. taxAmt
      10. total
   2. buyer: associative array of email, phone and country of the buyer 
4. If cc_payOrder returns, the payment went through, it throws an exception if the payment fails.  The calling AJAX must handle the exception return values.
5. Insert the payment record from the payment as built by cc_payOrder

TODO: should it be converted to building the fields directly in the payment flow

6. If a coupon discount was used, insert the coupon payment record as APPLIED
7. If a payment plan was created, create the payor plan record
8. If a payment was made on a plan
   1. Update the payor plan balance due, status
   2. Create the payor plan payment record
   3. Update the items in the plan for the amount paid from this payment on the plan
9. If the payment is completed, update the transaction with the complete date
10. If a coupon was used, update the transaction with the coupon details
11. if not a plan payment
    1. update all the items paid amounts that are not in the plan
    2. update all the items paid amounts that are in the plan
12. If needed, recompute the plan info
13. Send the payment receipt email
14. Provide the return values for the AJAX caller

###Device (Terminal) Path:
1. If not passed, etch the order
2. Verify the amounts against the payment amounts for tax and total amount due
3. Determine if payment type is Terminal
   1. Set up the terminal procedures
   2. Get the status of the terminal
   3. If Override the terminal being busy
      1. Reset the terminal
      2. If the status showed a payment in process cancel the payment
      3. If the status showed an order in process, cancel the order
   4. If the terminal is busy and no override: create and return an in use error message to the AJAX caller and exit
4. Build the payor and buyer parameter the terminal and update the master transaction if needed
5. If payment type is not Terminal
   1. Build the arguments for cc_payOrder based on the payment type
   2. If type is not 'credit' or amount is 0 (do not send items of type credit or zero value payments to the processor for logging)
        1. use cc_payOrder as in the non terminal path above to send the transaction to the credit card processor
6. If payment type is terminal (Terminals are Asynchronous, so either send the payment request to the terminal, or poll it to see if it is completed)
   1. if a poll is requested: (Check if the payment is completed yet)
      1. get the payment status from the terminal
      2. Update the terminal status record
      3. If the status CANCELED, reset the terminal record to available
      4. If the status is not COMPLETED, return an appropriate message to the AJAX caller and exit.
      5. Get the paymentId from the checkout response from the terminal.
      6. Get the payment record from the credit card processor
      7. Update the terminal status to available
      8. Update the transaction record with the completion information from the terminal.
      9. Create the payment record
      10. Create the arguments cc_payOrder would create to process the payment 
   2. If no poll requested, it's a new payment, create the terminal request
      1. call term_payOrder with the orderId to have the terminal process the payment
      2. Update the terminal status to in use
      3. Update the transaction with the in process information
      4. Return to the AJAX caller with the information about the start of the payment process
7. (Poll, status = completed)
8. Update the transaction with the payment status and id
9. Create the payment record
10. Create the coupon payment record if a coupon was used
11. Create the discount payment record if a discount was used
12. Process the payment against the amount owed in the order items
13. Update the transaction with the coupon or discount information if needed
14. Update the transaction with the payment amount
15. If nothing more is owed, update the transaction as complete
16. create the response back to the AJAX caller and exit.
