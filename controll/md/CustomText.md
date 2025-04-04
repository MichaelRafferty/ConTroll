# Custom Text

Custom Text is the way an admin can configure specific text on the screen or in emails for end users of the ConTroll Registation system.

Custom Text is indexed by a four level system:
* Application (App) - the top level application this piece of custom text belongs:
  * Controll: The back end of the ConTroll system (future)
  * Exhibitor: The common exhibitor portal (artist, exhibitor, fan, vendor)
  * Atcon: The on-site membership/art show portal (future)
  * Onlinereg: The single purchase membership page (no login) (future)
  * Portal: The end user membership portal
* Page - which page of the section are they on.  This is the page name as shown in the address bar at the top of the web page, less the ".php" suffix.
* Section: Which section of the page does this item belong to.  Most pages only have a 'main' section.
* Item: The specific text item on the page.  The description tells you where that item appears on the page.

Custom text is configurable by the system administator via the configuration file to one of three settings:
* all: show all customtext, even the default messages. This setting lets you see where on the page all of the custom text blocks will appear.
* production: suppress the 'default' placeholder messages (an alias for this is nodefault)
* none: supporess all custom text

## Editing Custom Text
A 'Custom Text' menu tab appears on each section of the back end that supports custom text.
Each section only shows the applications avaialble for editing from that top level memu item.

* Admin can edit controll items
* Registraiton Admin can edit atcon, onlinereg and portal 
* Exhibitors can edit exhibitor (note there are specific items for each portal type in this application)

Custom Text is edited in the HTML tinyMCE editor. Any HTML tgs are acceptable within the limits
of the configuration of the editor.

However items of section 'email' will have all of the HTML tags converted back to plain 
text for the plain text style email if there is no HTML and Text alternatives in the item.


## Variable Substitution within Custom Text Messages

Custom Text supports two types of vsriable subsituion at present:
* `#section.element#` for items in the master configuration file
* `[[variableName]]` for special items for that particular section of the system

### Configuration File Variables

Within limited sections* of the configuration file one may embed a tag of the form 
`#section.element#` or just `#element#` to default to the `con` section.

The string after the equal sign of that element from the configuration file replaces the variable.
Variables that do not exist in the configuration are left unchanged in the ouput text.
This substitution occurs as the text is output in the web site, and not when it is being configured in the controll back end.

Please see your system administator for a list of configuration file variables. 
There are many of them and these often deal with names, email addresses, urls and other data that
will be changed from year to year.  The use of these variables provides for not having to re-edit
the custom text every year for these elements.

Some examples from the con section:
* label="Convention Name"  ; e.q. Philcon 2025
* id="number" ; convention identifier such as 62 or 2025 (changes each year)
* conname="Short Name"	; e.q. Philcon
* org="Non Profit Name" ; e.g. Philadelphia Science Fiction Society
* orgabv="NPN" ; e.g. Short name for non profit (abbreviation) PSFS
* volunteers="volunteers-email-address"
* policy="Website Policy URL"
* regpolicy="Website Registraiton Policy URL"
* privacypolicy="Website Privacy Policy URL"
* privacytext="See our privacy policy for how we use and share information" ; prompt text about the privacy policy
* policytext="Philcon Policies" ; pormpt text about hte convention policies"
* regemail="registration support email"
* regadminemail="registration admin/chair email"
* infoemail="Convention information email"
* refundemail='refund email'
* feedbackemail='feedback email'
* website="Convention website URL"
* regpage="Convention website registration information URL"
* schedulepage="Convention on-line schedule URL"
* hotelwebsite="Hotel's website URL"
* hotelname="Hotel name"
* hoteladdr="Hotel address"

*The sections *cc, client, debug, email, google, local, log, mysql*
are skipped for security reasons as they hold keys and other protected data) 

### Application Specific Variables

Several sections of the code use specific variables for substitution in their custom text
in addtiion to the configuration file variables.

#### Exhibitor Emails:
Items of section 'email' use the following specific variables:

``[[EXHIBITOR_NAME]]``: name from the exhibitor record (artist full name)<br/>
``[[CONTACT_NAME]]``: name from the exhibitor years record (contact full name)<br/>
``[[ARTIST_NUMBER]]``: number assigned to this artist<br/>
``[[REGION_NAME]]``: Name of the region (Art Show) where they bought space<br/>
``[[CON_NAME]]``: Name of the con from the config file<br/>
``[[ARTIST_PORTAL]]``: URL to artist portal from the config file<br/>
``[[OWNER_NAME]]``: Name of the region owners<br/>
``[[OWNER_EMAIL]]``: Email address for the owner of this region<br/>

***As other sections are added, they will be documented here.