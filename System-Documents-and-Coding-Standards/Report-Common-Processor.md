# ConTroll™ Report Common Processor 
### How to write .rpt files for the Reports tab in the ConTroll™ Administration Site

## Introduction

The reports menu of the ConTroll™ Administration Site has been revamped. 
Instead of every report being a custom PHP script,
a common method and PHP processor is available for “standard” reports.  
These Reports will be specified by a configuration file and called from
controll/reports.php as a set of tabs. 
The execution of these reports is from a common back end AJAX script that will:
 * Optionally prompt for any run time specific parameters
 * Retrieve the data
 * Display the report as a tabulator table
 * Offer to download the contents of the report as a CSV file

There are four directories under the reports folder:
 * groups: system defined group files and tracked by github as part of the ConTroll distribution.
 * reports: system defined php and rpt files and tracked by github as part of the ConTroll distribution.
 * local_groups: client specific group files, not tracked by github
 * local_reports: client specific php and rpt files, not tracked by github

Over time the local reports can be proposed to move into the system
distribution and become available to all users of ConTroll.

## Configuration File Format

There are two file types in the configuration set:
 * .grp:	A group of reports to make a sub menu within a Tab
 * .rpt:	A specific report that can be in one or more tabs or sub menus

The file name of each group will include a three digit numeric sort key on the front to indicate the display order for the tabs within the report tab.

A third type of report does not have a configuration file,
but runs directly from PHP and ends with .PHP.
The report outputs the HTML text to be displayed on the screen and is run in an AJAX fashion and displayed by the Javascript.
This is for reports that cannot be displayed as a tabulator table due to their special requirements or complexity.

## Report Groups (.grp)

A report group consists of all of the reports in a Tab.
A report can exist in more than one group. 
The file must be parsable by the PHP parse_ini_file function with sections in brackets and fields as name=value.
The sort order of these file names determines the tab order on the page.
Both the files in the system wide groups directory and the files in the local groups directory are sorted into one set of tabs.

### Lines in a group configuration file:
* [group] (global items)
  * name: tab name (title)
  * description: longer description for the items under this tab.  This is a single line of text, can include simple HTML, but cannot include newlines.
  * auth: auth token needed to see this tab
* [report short name]  (this grouping repeats for every report in the menu, and starts with a 3 digit sort order for the order of the menu
  * name: Name of the report
  * description: longer description for this report
  * template: file name of the report template
  * auth: auth token to see this report line (may be different than the group auth value to further restrict reports within this submenu)
  * type: php (outputs page contents)  or rpt (run sql, make table)
  * Pnnn: Prompt list for pre-execution prompts for a report. The overall string needs to be in quotes.  The three digit numbrer in the prompt name is the 
    display order of the prompt fields.
    * /~/ separated list of strings, no quotes around the strings
       * type: prompt, constant 
         * prompt is a label and input field, with a default value if desired in the value section
         * constant is a specific value in the value section
       * name: input field name
       * label: input field label for form fill-out on screen
       * placeholder: placeholder text for input item
       * value: default/constant value
    * Eg: prompt/~/perid/~/Perid of the registrant/~/1

### Sample Group File:

```
[group]
    name="RegAdmin"
    description="Registration Staff Reports"
    auth=reg_staff
[100RegHistory]
    name="Registration History"
    description="Registration History for a Person"
    template=reg_history.rpt
    auth=reg_staff
    type=rpt
    P001="prompt/~/perid/~/History for: /~/Enter Person's Perid"
```

## Reports (.rpt)

A report consists of a specific report to display as a tabulator table with a download button.

TODO: create a way to print these outside of the full web page with decorations.

This configuration file must be parsable by the parse_ini_file function with sections in brackets and fields as name=value.

### Lines in a report configuration file:
* [report] (global items)
  * name: Name of the report - used as a H1 before the report output
  * auth: auth token to execute this report (must match the auth token in the group's report section.)
  * index: index field name
  * csvfile: default file name for the csv download
  * totals: position of the overall totals (top/bottom)
  * subtotals: (field list to do a group by and subtotal, if calc fields exist
* [Cnumber] (the number is the sort order of the CTE in the sql, number should be 3 digits with leading zeros) (CTE is Common Table Expression and uses the 
  with subquery notation in the SQL)
  * name: CTE name
  * select: sql of the CTE condensed into a single line
  * tables: from/join clauses of the CTE in a single line
  * where: where clause of the CTE in a single line
* [Fnumber] (the number is the sort order of the field in the table as it is displayed, number should be 3 digits with leading zeros)
  * name: name of the column (field name in the AS clause in SQL and in tabulator
  * sql: sql select code for the column as a single line including all case statements
  * title: column title in Tabulator
  * align: right (uses hozAlign in Tabulator))
  * calc: type (sum,avg,min,max,count,unique) (see topCalc and bottomCalc in tabulator)
  * precision: integer (number of decimal points, only used if calc exists for the field, optional, default is 0) (see topCalcParameters and 
    bottomCalcParameters in Tabulator)
  * filter: header filter type (defaults to false), true, textarea, fullname, number (see headerFilter in Tabulator)
    * true: use text input style
    * textarea: use a text area instead of text for the input style
    * fullname: use ConTroll's custom fullName filter that searches (and requires) the invisible fields first_name, middle_name and last_name
    * number: use ConTroll's custom numeric filter that supports <, <=, >, >=, and = comparisions in the filter field, such as >0.
  * format: tabulatorFormatter (html,textarea,link) (see formatter in Tabulator)
    * the default format if omitted is 'text'
    * html: display the contents as HTML
    * link: display the contents as a clickable link
  * sort: header sort (true/false) (see headerSort in Tabulator)
  * width: optional width in pixels (see width in Tabulator)
  * minWidth: optional minimum width in pixels (see minWidth in Tabulator)
  * visible: true (default) or false (see visible in Tabulator)
* [Tnumber] (the number is the order of the tables in the join, with 0 being the "FROM" table, number should be 3 digits with leading zeros)
  * name: table name
  * alias: table alias
  * join: join clause
  * left: true to use left outer join versus normal join
* [Pnumber] (the number is the order of the parameters in the sql)
  * type: config, post (config is from reg_conf.ini, post is from the prompt/const parameters)
  * section: config section (if config)
  * item: config item (if config) or input field if prompt
  * datatype: sql datatype: s,i,d (string, integer, floating point decimal)
* [where]: sql where clause (broken into lines for readability)
  * 001: line 1 of where clause
  * 002: line 2 of where clause… (repeat as needed for readability)
* [group]: sql group by clause (broken into lines for readability)
  * 001: line 1 of group by clause
  * 002: line 2 of group by clause… (repeat as needed for readability)
* [sort]: sql order by clause (broken into lines for readability)
  * 001: sql order by clause
  * 002: sql order by clause continued for readability

### Sample Report File:

```
[report]
	name="RegHistory"
	auth=reg_staff
	csvfile="RegHistory"
    index=regid
[F001]
    name=Perid
	sql=p.id
	title="Perid"
	align=right
	minWidth=80
[F002]
    name=fullName
    sql="TRIM(REGEXP_REPLACE(CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name, ' ', p.suffix), '  *', ' '))"
    title="Full Name"
    minWidth=300
[F003]
    name=Conid
    sql=r.conid
    title=Conid
    sort=true
    aligh=right
    minWidth=80
    filter=number
[F004]
    name=Type
    sql=m.memType
    title=Type
    filter=true
[F005]
    name=Category
    sql=m.memCategory
    title=Category
    filter=true
[F006]
    name=Label
    sql=m.label
    title=Label
    filter=true
[F007]
    name=price
    sql=r.price
    title=Price
    filter=number
    align=right
[F008]
    name=createdBy
    sql=r.create_user
    title="Created by"
    align=right
    minWidth=80
[F009]
    name=createDate
    sql=r.create_date
    title="Create Date"
[F010]
    name=pickup
    sql=ra.logdate
    title="First Pickup"
[F011]
    name=source
    sql=t.type
    title=Source
[T001]
	name=reg
	alias=r
[T002]
	name=memList
	alias=m
	join="m.id = r.memId"
[T003]
    name=transaction
    alias=t
    join="r.complete_trans = t.id"
[T004]
    name=perinfo
    alias=p
    join="r.perid = p.id"
[T014]
    name=pickup
    alias=ra
    join="ra.regid = r.id"
    left=true
[C001]
    name=pickup
    select="SELECT regid, MIN(logdate) AS logdate, action"
    tables="FROM regActions"
    where="WHERE action='print' GROUP BY regid, action"
[P001]
	type=prompt
	item=perid
	datatype=i
[where]
	001="r.perid = ?"
[sort]
	001=conid, createDate
```