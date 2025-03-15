# ConTroll Version 1.1 Release Notes

## Version 1.1: Back End Buildout Release Release Date: 2025-01-27

# Major Configuration Changes in 1.1:

* New Database Patches 31-41  
  * 31: Adds notes to the registration configuration tables  
  * 32: Migration to trigger based history tables for reg, moving reg\_History to regActions  
  * 33: Adding reminders to payment plans  
  * 34: Start of a revamp on how coupons are accounted for and a new couponUsage view  
  * 35: ability to sort the main menu in ConTroll as the admin user desires  
  * 36; Initial tables for the Oauth2 Server which is now on hold indefinitely  
  * 37: Fix an issue with the reg, perinfo, and artItems triggers, this is an important patch to apply  
  * 38: Updates to the rules tables to make them conid specific, especially since they contain memId (individual registration item identifiers that are conid specific)  
  * 39: Additional Custom Text fields in the portal  
  * 40: Addition of Custom Text to the exhibitor portals (artist, vendor, and the future fan and hall portals)  
  * 41: Additional Custom Text for exhibitor, cleanup of custom text notes, and a fix to the perinfo update trigger.  
      
* New/Changed/Deleted Config File Entries:  
  * Moved the file references from the config file to Custom Text and deleted the entries:  
    * artistOnSiteInventoryReqHTML  
    * artistOnSiteInventoryReqText  
    * artistMailInInventoryReqHTML  
    * artistMailInInventoryReqText  
  * Moved the text values from the config file to Custom Text and deleted the entries:  
    * artistSignupAddltext  
    * taxidextra  
    * reg\_disclaimer  
    * pay\_disclaimer  
  * Added new entries  
    * required: first, addr, all (which fields are required in the profile)
    * oneoff: 0/1 (is this a one off convention or annual)
    * multioneday: Allows selling more than one different one-day memberships.  
    * controll\_stats: debug for the statistics routines, production instances should have this \= 0  
    * WorldCon related entries added:  
      * nomdate : last date allowed for paid membership to nominate  
      * nomnomURL: Full URL to the nomnom site  
      * nomnomKey; signing key for use by nomnom and ConTroll  
  * Official support of the \[local\] section for data reporting  
    * csvto: comma separated list of emails to receive csv’s of interests, et al.  
    * csvcc: comma separated list of emails to receive cc on the csv’s of interests, et. al.  
    * csvsavedir: path relative to the scripts directory to save and CSV files sent by the system  
        
* New Scripts: (see the \-h argument output for the options allowed on each)  
  * planreminders.php: payment plan payment due / past due emails  
  * sendinterests.php: send csv’s of the updates to the interest files

# Major changes by application:

## ConTroll: (Administrative Back End to the system)

* Moved most of the configuration out of the Admin menu to the registration Admin menu and the Exhibitors menu  
  * Admin now has:  
    * Users: ConTroll users  
    * Main Menu: Sorting the main menu items  
    * Atcon Users: Placeholder for duplicating the ‘Users’ tab within Atcon Administration  
  * Moved to Registration admin:  
    * (Badge List renamed Registration List)  
      * Revamped the Changes to existing item section to support:  
        * Editing the registration including:  
          * memId (reg Type)  
          * Price  
          * Paid  
          * Coupon  
          * Coupon Discount  
          * Status  
        * Revoke membership/restore revoked membership  
        * Transfer memberships  
        * Rollover memberships  
        * Refund Memberships  
      * Added Membership History to show any changes to the membership over time  
      * Added Notes to display any Actions (including notes) on this membership.  
    * Current Convention Setup  
      * Notes column added  
    * Next Convention Setup  
      * Notes column added  
    * Membership Configuration  
      * Support for locking required entries  
    * Custom Text:  
      * Editing custom text for online reg and portal applications  
    * Policies (No changes)  
    * Interests (No Changes)  
    * Membership Rules:  
      * Enhanced simulator to support which filters to use (online, atcon, none)  
      * Added tables to show memberships in the simulation, and effected by each rule  
    * Merge People (No changes)  
* People  
  * Improved policy support  
  * Unmatched New People:  
    * Added additional query section  
  * Registration (Mail in  
    * Improved policy support  
    * Better support for coupon payment information  
* Finance (new Menu Item)  
  * Payment Plan Configuration: new  
* Free Badges  
  * Conversion to new UI  
* Exhibitors  
  * Moved Configuration to this menu item, allowing those with exhibitors permissions to edit the configuration  
  * Added Custom Text editing for the exhibitors portals and emails  
* Art Control  
  * Added editing of the items

## Portal:

* Added Hugo System (Nom Nom) support  
* Cleaned up Policy and Interest sections

## Atcon:

* Rewrite using the new Mail In Reg common code with support for membership rules and new coupon accounting  
* Support for the portal database changes

## Exhibitor (Vendor Portals)

* Re-layout of the signup screen into multiple pages  
* Support for Custom Text by portal type replacing hard coded text or config file entries  
* Addition of vendor/artist specific fields to their portal displays.

# Jira Items Closed:

Some of these are catchups from prior release, but marked closed in this interval.

6: Membership Config changes  
8: Rewrite Free Badges in BS5  
14: Add new field, redo for age  
16: Add status processing  
17: Add status processing to exhibitor portals  
18: Add custom Text  
30: Legend configuration  
37: Custom Text notes of where on the page  
41: Policy check review in atcon  
53: Atcon coupon payment issues  
69: Atcon coupon payment issues  
70: People screen search fields  
73: Rewrite of people screen in BS5  
90: Rewrite of people screen in BS5  
91: Rules editing/simulator engine  
92: Home: (no changes)  
93: Reg-admin: delete no charge memberships  
94: Merge based on new database table fields  
95: Custom text editing  
96: Move exhibitors configuration  
97: Re-align column widths in exhibitors configuration  
98: Reg list: add button to show notes  
99: Configurable filter list position in reg list  
100: Bug fixes in membership portal  
101: exhibitor upgrade to new structure  
102: Variable price donations  
103: JSON issue with character sets  
105: Exhibitor space request better handle deny vs not requested  
106: Printing errors for upgrade badges  
107: redesign of artist profile screen for mailin to avoid accidental missing or incorrect data  
108: art items enhancements  
109: moved to user discussion  
110: finished off dependencies on user\_id vs perid in admin users  
111: Add perid to display of person in registration  
112: Bar code enhancements  
113: Bulk email timing settings for NFP Hosting  
114: Bar code enhancements  
115: remove paragraph tags from some exhibits descriptions  
116: cc\_square details on registration lines, add memId  
117: log artist inventory changes to log file (used history table)  
118: location assignment changes in exhibitors  
119: FPDF extensions  
120: Exhibitor display changes in ConTroll  
121: Javascript versioning  
122: Integrate letters into payment processing (?)  
123: FPDF enhancements  
124: Number Format corrections  
125: AGPL changes  
126: atcon/ArtInventory performance improvements  
127: Exhibitor Code Consolidation Completion  
128: find state fields that were still (2) and make them (16)  
129: release clean\_unpaid for onlinereg style installations  
130: Add “Preferred Name” \-\> moved to add Legal Name  
131: Realign Exhibitor Configuration  
132: printBadge changes for new memconfig tables  
133: Add email address to registration list display  
134: Install program updates  
135: Exhibitor: more corrections to denied vs not requested  
136: Add timestamp to log entries  
137: admin user edit restrictions  
138: admin perid migration from userid item  
139: printBadge changes for new memconfig tables  
140: Time based range clarification  
141: additions to receipt  
142: update for newer google\_init calls  
143: change to old style search (now obsolete and not used in current system)  
144: add/update support of regconfirmcc configuration variable  
145: fix to non membership checkout bug in exhibitor portal  
146: show receipt with memberships in exhibitors info and in exhibitors portal  
147: Mem config deal with key changes (cascades)  
148: Add receipt to registration list  
149: update search fields everywhere  
150: Add newperson access to registration list  
151: Add coupons in the new style back to online reg  
152: Atcon filter fixes  
153; Cleanup install to not propagate autoincrement values in database setup  
154: Cleanup mkfk.php for adding routines and triggers creation  
155: Use new methods for transfer in registration list  
156: Fixes to exhibitors configuration  
157: Add mail in reg to exhibitors  
158: Updates to mail in reg for new structure  
159: FInd in atcon focus enhancements  
160: Use pulldown in coupon  
179: Emails in exhibitors revisions  
195: Art show invoice bug  
197: Cancel dealers request bug  
200: Login issue fixes  
202: Menu reorder page  
204: Restructure of admin menu to other menus  
212: Restructure of admin menu items  
216: Fullname in match criteria  
217: Re-arrange fields in profile (move badge name/phone)  
218: Mail in reg crash bug  
220: Tier 1 Support issue  
221: remove zip code check reg  
222: Reimplement unpaid transactions in POS style reg functions (mail in reg/atcon)  
224: More denied emails for exhibitor fixes  
225: Exhibitor invoice fixes  
227: Add perid/standard name search to people  
229: Exhibitor approve other clarifications  
237: Tier 1 support Issue  
238: New Policy addition effect on existing users  
244: Match person button issue  
250: Improvement in payment work flow in the portal  
253: Bug fix in year ahead creation

