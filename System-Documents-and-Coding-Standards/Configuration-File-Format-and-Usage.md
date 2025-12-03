# Configuration  File Format and Usage

ConTroll three text based configuration files:

- reg_secret.ini - Contains all usernames, passwords, api id's and secrets, and other confidential information.
  - Sections in the reg_secret file should not appear in any other configuration file.  They should be 'complete' within this file.
  - (During the migration to the three file format from the single file format, reg_secret is loaded first and can be overridden by the
          other files.  In a future release it will be loaded last and its sections will overwrite the complete sections loaded by the other files.)
- reg_admin.ini - Contains all items that should not be changed on a day to day basis by Reg Admins in the controll application.
  - This file is editable by those with shell access to the config directory.
    
- reg_conf.ini - Contains all other item, such as email addresses, option usage, and text additions that the Reg Admin is allowed to edit.
  - A future release will add the ability for the reg-admin's to edit the contents of this file in the website.
  - The .sample version of this file will be converted into the specifications file for the web based editor.

All of these files are found in the config directory and samples of the current possibe contents are in the config-sample directory.

## PHP Usage of the configuration files

ConTroll uses PHP's `parse_ini_file()` function to read and place the contents into an associative array. All 3 files are read and merged into
one array by the global library function `loadConfFile()` which should be called after requiring global.php.  Since the HTTPS redirect flag
is in the configiuration file, this should be done before checking for the redirect for HTTPS code.

## File Formats

All files should be of the following format:

### Comments:
        ;;; Start of block comment
        ; continued comment or single line comment

### Section Headers
        [section]

Section headers should be:
- all lower case
- mnemonic of what is in that section
- be preceeded by  an empty (blank) line for readability
- optionally preceeded by a label comment: ;;;;; descriptive lable for this section for web editing of the file

### Parameters
       param=value ; optional comment
       param="value string" ; optional comment

Parameter names are alphanumeric and start with a letter.
Quotes are required if the value has any non alphanumeric characters in it, including blanks.

See the PHP parse_ini_file documentation for futher explaination of the rules for parameter formatting.

Case is the developers choice, but they are case sensitive. Avoid the same name with different cases or spellings. Try to be mnemonic with the parameter name.


### File Contents

The top of the file has comments with the name of this file and explaining this files usage.
If a file has global defaults, those whould be in the **[global]** section and appear first in the file.
All other sections can be in any order, but the general order is:
- Common
- Specific to an app within ConTroll

## Using Configuration File Values

Use of the `globl $db_ini` is depreciated and no longer available.
The name was historical to when the db_functions file loaded the configuration parameters.

It is now loaded by `loadConfFile()` in `/lib/global.php`.
The PHP require once for that file must preceed any require or use of any configuration file values.

Before you use any configuration file value you must call `loadConfFile()`.
This function returns true if the file was loaded and false if it's called a second time and the file was already loaded.
(All of the base.php files are already be doing this so they can do the HTTPS redirects as needed.)

If you have need for any configuration file value that appears in both the **[global]** and a **[section]** override, you must use the
`getConfValue(section, key, defaultValue = '')` function.

- section is the primary section for the key
- key is the name of the parameter in that section
- defaultValue is what to return if the parameter is not in the configutation file.  This argument defaults to the empty string if a value is not passed.
If you want null returned for not found you must pass null as the third argument.

This function first looks in the section passed for the key. If not found there it looks in 'global' for the key.
And if still not found, it returns defaultValue.

If you only need one or two keys in your function, using the getConfValue is preferred because it avoids having to code all the
`array_key_exists` protections for keys not actually in this conventions configuration file.

If you need many keys from a section, the `get_conf(section)` call is still provided, and returns an associative array of the contents of that section,
but it is depreciated and should be avoided. 
The minimal amount of CPU overhead calling getConfValue allows the isolation of the
contents of the configuration array from normal PHP code.

# Usage of reg_conf.ini.sample to Drive Web Editing of the Configuration

In the directory config-sample the sample file for reg_conf.ini serves double duty.
It not only provides a context sample for the fields for creating the file from scratch,
it also provides the parameters to drive the online editor for this configuraiton.
All of the key information is part of the comments in this file.
Each directive starts with two semicolons followed by a space.
All other lines in the file are ignored in this context.

The online editor will **only** edit this subset of the configuration.

## Format of the comments to drive the editor

Each etnry in the file that is online editable will contain the following prefix comments:

- `;;;;; HR grouping label`
    - optional
    - Will output a <HR> and the grouping label
    - Used to group a set of parameters together because they have something in common
- `;; N:name`
    - The name of the parameter
    - This entry starts a new parameter in the file and must be the first parameter for any entry
    - The name and id of the related input field will be `S_section_P_parameterName`
- `;; R:visibility,permission-role
    - permission-role: Role required to edit this permission.
    - visibility: If the user does not have the role, how is this row displayed:
      - `H` for hidden if not allowed to edit
      - `V` for visible but read/only
- `;; P:placeholder`
  - The placeholder value for the input tag that will be used to edit this field. 
- `;; H:hint`
  - A hover hint for this parameter
  - A hint may span multiple lines in the file
  - Continuation lines for the hint will start with `;;H+`
- `;; D:datatype`
  - The input data type for this field:
    - `iX` = integer, with at most X digits
    - `dX,Y` = decimal, with at most X places and Y to the right of the decimal point
    - `sX` = fill in the blank single line string, with a max length of X
    - `tX,Y` = text area with X columns and Y rows. Newlines will be preserved in the text area.
    - `e` = valid email address
    - `r:dir` = relative file path, a warning will be issued if this file is not readable starting in the *dir* directory 
relative to the root of the installation. Note: dir is not allowed to be empty.
    - `a` = absolute file path from the root of of the file system.
    - `l:list` = enumerated list of choices, separated by commas. First entry is the default one.
    - `h` = URI, must be of the format:
      - https:
      - http:
      - mailto:
- `;; B:action if blank`
  - `M` = Mandatory, cannot be empty
  - `O` = Optional, Omit the line if it is blank
  - `E` = Optional, output the line with an empty (no) value (IE: `name=`)
  - `B` = Optional, output the line with an empty string (IE: `name=''`)
