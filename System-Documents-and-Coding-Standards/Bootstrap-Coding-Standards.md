# Bootstrap Coding Standards

## Introduction

- ConTroll is developed using Boostrap 5.3 or later. 
- All HTML output destined for the web browser should be in Bootstrap style.
   - Currenly the CDN `https://cdn.jsdelivr.net` is used to load:
     - bs5css => `dist/css/bootstrap.min.css`
     - bs5js => `/dist/js/bootstrap.bundle.min.js`
   - The current version and validation strings are in lib/jsVersions.php
   - The load of these files is usually part of `base.php` in each application's lib directory
- All Email HTML output should use HTML tables as a javascript engine is not normalily part of email readers

(This file is a work in progress, it's just a start for now)

## Color Usage
The standard Bootstrap color scheme is used by default:

- Body: --bs-body-color, --bs-body-bg
  - Black text on white background
  - Used for all 'default' body areas
- Primary: --bs-primary, btn-primary, --bs-primary-bg-subtle (disabled)
  - Used for main action buttons, modal headers and other primary areas
  - White text on a blue background
- Secondary: --bs-secondary-color, btn-secondary, --bs-secondary-bg (disabled)
  - Used for alternate actions/buttons
  - Dark gray background with white text
- Tertiary (New in v5.3): not yet used, but similar to secondary, but of lesser importance
- Border: --bs-border-color
  - Used for HR and dividers
- Success: --bs-success, btn-success
  - Used for successful execution status messages and other successful information areas
  - Green background with white text
- Danger: --bs-danger
  - Used for error messages and error conditions
  - Red with white text
- Warning: --bs-warning, btn-warning
  - Used for warning messages or buttons that require caution before executing
- Info: --bs-info, btn-info
  - Used for buttons that call up information
  - Used for information messages
  - Cyan with black text

There are exception to this including in the legend where some specific colors are called out.  In additon Dark and Light are also rarely used.

## Page Layout
### Grid Structure
All ConTroll HTML pages are output using the Grid Layout. 
- This is because the newer CSS Grid is still under active development by Bootstrap and considered 
'Experimental'.
- At some future point a decision will be made whether to support browser CSS Grids in addition to Bootstrap grids or to phase out Bootstrap 
grid in favor of the CSS Grid structure.
- All HTML body output should be within some grid controled area

### Breakpoints
Breakpoints are the method Bootstrap uses to specify how to be responsive to page sizes.

ConTroll has standardized on the `sm` breakpoint.
- this provides a balance between responsive column flows and minimum column widths
- Standardizing on one reduces the amount of code we have to develop to take into account variable size screens

### Containers
Whenever possible ConTroll uses `container-fluid` so allow for the section to grow/shrink responsively and yet set no maximum page width.
- If approriate to your page design, a specific responsive container breakpoint can be used to specify a maximum width
- Containers can be nested inside other breakpoints or grid column elements


### Grid (rows/columns)
A Bootstrap grid is a set `<div></div` blocks broken into rows and columns.

#### Rows
Bootstrap requires all column sections be contained in a row div block.
- Use the `m` classes to provide spacing
  - ms, me, mt, mb may used to control specific directions
  - mt-4 before major blocks _(can be adapted for readability)_
  - mt-2 between minor blocks _(can be adapted for readability)_
  - Minimum of mt-2 before button rows to separate the buttons from the text / table blocks.

#### Columns
Bootstrap divides a row into 12 equal width columns
- A column div may consiste of 1-12 contiguous columm sections, `col-sm-1` .. `col-sm-12`
- `col-sm` without a number extends to the end of the row _(through section 12)_
- `col-sm-auto` is a variable width column

ConTroll uses both the fixed width and the auto columns and both are equally allowed
- Items that should line up require the fixed width
- Items that are label followed by value can either:
  - Do it one per row
    - use the label as `col-sm-auto`
    - use the value or input item as the remainder `col-sm`
  - Subdivide a multiple column width item as 
     - Hold the block in a `<div class="col-sm-N">`
     - Contain the label and value in a `<div class="container-fluid">` and `<div class="row">` blocks

### Navigation

#### Navbar
Controll uses the Bootstrap Navbar for the top of page menu, if the application has one.
- The navbar should be included in the page title block and have the same background color as the page title
  - For user facing applications this should default to "primary" (blue)
    - The standard style is:
    
            <nav class="navbar navbar-dark bg-primary navbar-expand-lg mb-2">
    
- For /controll the page header and Navbar are separate and differing colors of purple based on the old Balticon standard.
  
- The first item in the nav bar is the responsive menu toggle:

       <div>
           <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                     style="border-radius: 5px;" aria-label="Toggle navigation">
                 <span class="navbar-toggler-icon"></span>
             </button>
         </div>
- The remainder of the menu is in a collapse div:
      
        <div class="collapse navbar-collapse" id="navbarNav">
      
- The first menu button has rounded edges except for the bottom left corner:
    
      <button class="btn btn-outline-light navitem me-3 <?php echo $active; ?>" type="button" <?php echo $ariainfo; ?>
          style='border-bottom-right-radius: 20px;' onclick='onclick code'>
          Menu Item Name
      </button>

- Middle menu items have rounded corners except for the upper left and lower right:

      <button class="btn btn-outline-light navitem me-3 <?php echo $active; ?>" type='button' <?php echo $ariainfo; ?>
       style='border-top-left-radius: 20px; border-bottom-right-radius: 20px;' onclick="onclickcode;
           Menu Item Name
       </button>
- The last menu item has rounded edges except for the top left corner:

      <button class="btn btn-outline-light navitem <?php echo $active; ?>" type='button' <?php echo $ariainfo; ?>
          style='border-top-left-radius: 20px;' onclick="onclick action;">
              Logout (or last menu item name)
      </button>

- Active highlight of the current item is by setting the active flag and it sets the color of the button to white with dark type.

#### Tab Based Navigation
To switch between screens within a single page, ConTroll uses the "Navs and Tabs" section of Bootstrap.

- The first page is the default active page in ConTroll

      <ul class="nav nav-tabs mb-3" id="PAGENAME-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="SCREENNAME-tab" data-bs-toggle="pill" data-bs-target="#SCREENNAME-pane" type="button" role="tab"
                    aria-controls="nav-users" aria-selected="true" onclick="settab('SCREENNAME-pane');">Screen Name
            </button>
        </li>

   - PAGENAME is the php name of the application page
   - SCREENNAME is the sub screen name
     - -tab for the menu item
     - -pane for the actual surrounding div block
     - Screen Name - the display name for this screen's tab
     - settab - a standardized javascript routine name for code needed to close the other tabs and open this tab
       - The actual switching of active tabs is handled by ARIA in the browser's javascript
  - DIV block of the page contents

          <div class="tab-content ms-2" id="PAGENAME-content">
             <div class="tab-pane fade show active" id="SCREENNAME-pane" role="tabpanel" aria-labelledby="SCREENNAME-tab" tabindex="0">
                 <div class='container-fluid'>
                      content
                 </div>
              </div>
          </div>
  - Outer div covers the entire page area that is changed
    - Items outside this div do not change on nav clicks
    - The message block should be outside this div
  - Inner div covers the contents of the page
    - It must include a container for its content
    - Content within that container should follow normal grid style

### Modals
In Bootstrap, Modal's are a javascript plugin which ConTroll loads by default.  Modals are well defined in the Bootstrap documentation, but there are a 
couple of style standards in ConTroll.

#### Creation
Modals need to be at the top level of the stack of elements.  They only show up when called out by the javascript.

The modal HTML is usually output by the PHP script as

     <div id='idName' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Name String' aria-hidden='true' style='--bs-modal-width: 80%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title'>
                        <strong id='idNameTitle'>Title String</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid' id='idNameBody'>
                    ..... contents of modal body .....
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='actionBtn' onClick='actionBtns();'>Action</button>
                     ...
                </div>
                <div id='result_message_idName' class='mt-4 p-2'></div>
            </div>
        </div>
    </div>


- In this case the id assigned to the modal is _idName_.
- The width of the modal is at least _-lg_ if not _-xl_
- The style parameter sets the width of the modal which should be from 60-98% of the overall window width.
- Each section of the modal has it's own id= string
- The colors of the sections of the modal are part of the style definition
  - Title is primary blue with white text
  - Body is white with black text by default
  - Footer is light cyan

In Controll declare the element of the modal with the following structure, either at window.onload or a when instantating a class:

       var id = document.getElementById('idName');
        if (id) {
            this.#idNameModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#idNameTitle = document.getElementById('idNameTitle');
            this.#idNameBody = document.getElementById('idNameBody');
            this.#idNameActionBtn = document.getElementById('actionBtn');
            ...
        }
