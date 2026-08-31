# Date Filtering
Table filtering is controlled by typing in the white boxes at the top of table columns just below the column name.

While number fields generally support:

|Op| Meaning                                     |
|:--:|---------------------------------------------|
|  <   | less than                                   |
|  <=  | less than or equal to                       |
|  >   | greater than                                |
|  >=  | greater than or equal to                    |
|  =   | equal to                                    |
|      | no operation is the same as =,  or equal to |
<br/>
Start date and end date also support additional filtering shortcuts.

All of the filters for date strings support two methods.
If a comparision operator character is not provided it treats the filter as if the 
date string includes the text string typed in the filter field.

For example -04- would find anything in the column with -04- in the string, or dates in April, as the date strings are formatted yyyy-mm-dd.

If the filter starts with one of the following comparision operator characters it performs a date comparision based on the optional value following the operator. 
It looks at the string following the operator and extends it into a full date/time for comparision if needed for the operator:
* Nothing follows the operator: appends the current date/time
* Less than 4 characters: converts the year to the current 4 digit year and appends '-01-01 00:00:00'
* Exactly 4 characters:  appends '-01-01 00:00:00'
* A single '-' in the string: appends '-01 00:00:00'
* Two '-' in the string: appends ' 00:00:00'
* Has the hour part: appends ':00:00'
* Has the minute part: appends ':00'

The following special date comparision operaters are supported:

|  Op  | Meaning|                                                 
|:----:|------------------------------------------------------------------------------------|
|  <   | date is before the date specified or now                                           |
|  <=  | date is before or matches the date specified or now                                |
|  =   | date is exactly the date specified or now                                          |
|  >   | date is after the date specified or now                                            |
|  >=  | date is after or matches the date specified or now                                 |
|  s   | the date string starts with the string in the filter box (with no date completion) |
|  e   | the date string ends with the string in the filter box (with no date completion    |
|  n   | (Now) the start date is <= now and the end date is > now or the date provided      |
|  a   | (Active) A synonym for now                                                         |
|  f   | (Future) The start date is > now or the date provided                              |
|  x   | (Expired) the end date is <= now or the date provided                              |
<br/>
