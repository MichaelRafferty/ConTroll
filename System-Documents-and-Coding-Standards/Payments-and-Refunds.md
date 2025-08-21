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
