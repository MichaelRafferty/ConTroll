# ConTroll™  Registration for Conventions
![Control Troll Logo](onlinereg/lib/ConTroll.png)\
ConTroll™ and the ConTroll Troll Logo are Copyright 2015-2024, Michael Rafferty

ConTroll™ is designed as an all in one system to support registration for conventions.  It supports on-line, mail-in, and on-site registration.

ConTroll™ is freely available for use under the GNU Affero General Public License, Version 3 (https://www.gnu.org/licenses/agpl-3.0.en.html). Local changes are allowed, but all changes to ConTroll™ must be freely offered to the ConTroll™ developers for potential integration into the system.

The system is under active development by a team of developers and has functions currently in development to support:
- Support of the Membership Portal in the remainder of the applications

Planned future additions:
- Configurable Exhibitor Portals (not just artist and vendor)
- Moving extended text configuration into the database
- Reconfiguring the control menu structure and role based permissions
- Extended Reporting options

Registration Tools in this Repository:
- Composer: Add-on's to PHP tracked by Composer (composer.json and composer.lock)
- atcon: Onsite Registration Processing and Management
  - admin: Administer on-site reg system
  - artInventory: Audit and maintain art inventory in the artist spaces
  - artpos: Point of Sale (Cashier) for artwork managed by the inventory system
  - printform: Print arbitary badges
  - regpos: Point of Sale (Check-in and Cashier)
  - volRollover: Volunteer Rollover for sufficient hours worked

- controll: Registration Administration, control, and reports
  - Current:
    - Administration Users
    - Membership Graphs 
    - People - manage information about people in the database
      - Resolve Conflicts
      - Add New Person
      - Edit Existing Person
    - Mail-in/Manual Registration
    - Complimentary Registration (Free Badges) (awaiting rewrite)
    - Registraion Administration
      - Badge List/Transfer/Rollover
      - Membership Setup: Membership Items/Prices/Availability
      - Membership Configuration
      - Custom Text
      - Policies: For people to agree to
      - Interests: For people to express interest and get referred
      - Membership Rules: Which memberships require special rules to be available for purchase
      - Merge People: Combine two people into one surviving record
    - Attendence Graphs
    - Coupon Management: membership, cart and one use coupons supported
    - Exhibits/Exhibitor Mangement
      - Exhibits Configuration
        - Types (rules)
        - Regions (areas using those rules)
        - This Years Regions (configuration for this year)
        - Spaces (sections of an area)
        - Pricing (pricing options within a space)
      - Exhibitor Mangement (by Region Owner)
        - Exhibitor Information (Add/Edit)
        - Approval Requests (for regions that require approval to ask for space)
        - Space Requests (enter/approve/control space requests)
    - Art Control (Manage art inventory)
  - In controll, but still in old format (functions, but not updated)
    - Free Badges - create free badges and add people to receive a free badge
    - Reports
    - Club Management
- onlinereg
  - buy memberships in a single web page without login or history
  - checkReg - obsoleted by portal, but still provided for onlinereg
- portal
  - Login page/functions
  - Portal - main home page, see/pay for memberships
  - addUpdate - add/update information/memberships
  - accountSettings - alternate identities (emails) and people you manage
  - Membership History - current and prior years registrations
  - respond - back end respond to authorization requests
- vendor
  - Login page/functions
  - Apply / Request Space
  - Pay for space and memberships
  - Enter art inventory (if art show style portal)