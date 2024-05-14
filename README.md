# ConTroll™  Registration for Conventions
![Control Troll Logo](onlinereg/lib/ConTroll.png)\
ConTroll™ and the ConTroll Troll Logo are Copyright 2015-2024, Michael Rafferty

ConTroll™ is designed as an all in one system to support registration for conventions.  It supports on-line, mail-in, and on-site registration.

ConTroll™ is freely available for use under the GNU Affero General Public License, Version 3 (https://www.gnu.org/licenses/agpl-3.0.en.html). Local changes are allowed, but all changes to ConTroll™ must be freely offered to the ConTroll™ developers for potential integration into the system.

The system is under active development by a team of developers and has functions currently in development to support:
- Membership Portal to review and add to a person's memberships

Planned future additions:
- Configurable Exhibitor Portals (not just artist and vendor)
- Moving extended text configuration into the database
- Reconfiguring the control menu structure and role based permissions
- Extended Reporting options

Registration Tools in this Repository:
- Composer: Add-on's to PHP tracked by Composer (composer.json and composer.lock)
- atcon: Onsite Registration Processing and Management
  - admin: Administer on-site reg system
  - regpos: Point of Sale (Check-in and Cashier)
  - printform: Print arbitary badges
  - volRollover: Volunteer Rollover for sufficient hours worked
  - artInventory: Audit and maintain art inventory in the artist spacesd
  - artpos: Point of Sale (Cashier) for artwork managed by the inventory system
- onlinereg/reg_control: Registration Administration, control, and reports
  - Current:
    - Administration (Users, Convention Setup, Vendor Setup, Merge People Records)
    - Membership Graphs
    - Mail-in/Manual Registration
    - Badge List/Transfer/Rollover
    - Attendence Graphs
    - Coupon Management: membership, cart and one use coupons supported
    - Exhibits Mangement
    - Exhibitor Management: Exhibitor requests/approval/registration
    - Art Control
  - In reg_control, but still in old format (functions, but not updated)
    - People - manage information about the people in the database
    - Free Badges - create free badges and add people to receive a free badge
    - Reports
    - Club Management
- Older Repositories not yet ported (no current plans to port):
  - OnlineArt
