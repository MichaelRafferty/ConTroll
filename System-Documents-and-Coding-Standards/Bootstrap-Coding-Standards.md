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
  
[- The first item in the nav bar is the responsive menu toggle:

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

- 
