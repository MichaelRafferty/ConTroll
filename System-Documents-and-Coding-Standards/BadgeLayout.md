# Badge Layout - Postscript and Proposed PDF Layout
Current print badge is postscript based and moving to PDF.
First we need to document the current postscript layout.

The current badge PS file is init.ps followed by the logo image PS followed by what printBadge writes to the PS file.

The PDF badge will be similar but in areas and a different font.

## Postscript Functions used in init.ps

- /firstline: font for the first line of the badge name
  - Courier-Boldfont
  - 24pt Font-size
- /details: ???
  - Courier
  - 12 pt
- /childfont: font for the child area
  - Courier-bod
  - 12 pt
- /secondline: Secont line of the badge name
    - Courier-Boldfont
    - 18pt Font-size

## Badge Fields
- badge name
  - badge_name field of badge associative array
  - if greater than 16 characters
    - split into two lines, firstline and secondline
    - split at last blank in the 16 characters, or after the 16th character. Line 2 is limited to 20 characters
  - line 1 written 22 points from the top of the label in 24pt
  - line 2 written 40 prints from the top of the label in 18pt
- info line
  - 3rd line on badge
  - indented 60 pts
  - Membership Category Label, a blank, and Person id (PERID)
  - in 12pt
- limitations line
  - bottom line of the badge (4th line)
  - If badge type is oneday
    - print three character day name in firstline font at the left margin
  - If badge age is non blank
    - print age string in reverse video to the right of where the day field would be
  - in 12pt

## Proposed PDF layout
Badge is 80pt's in height (~1.125 inches) and 250 pt wide (~3.5 inches) using a 4 pt margin on all sides yields a 72pt x 242pt print area.  

- Line1: Primary Badge Name
  - Bounding Box 0, 0 -> 242, 28
  - 24pt Arial Bold text plus 4 pt leading
  - Use maximim number of characters that will fit in 242 pts based on Arial Bold 24pt, moving one word or character to line 2 as needed
  - hold remaining text for line 2

- Line2: Secondary Badge Name
  - Bounding Box: 28, 0 -> 242, 48
  - 18pt Arial Bold text plus 2 pt leading
  - if any remaining badge name characters left
    - use those
    - followed by ' - ' 
    - followed by whatever of badgename 2 will fit
  - If none, use whatever of badgename 2 will fit
  - allow reduction to 16, then 14 point for long names

- Line 3: Info line 
  - Barcode
    - Bounding Box: 48, 0 -> 62, 120
    - Barcode of PERID 12 pt high
  - Info area
    - Bounding box: 48, 120 -> 62, 242
    - 12 pt Arial text + 2pt leading
    - Membership Category Label, a blank, and Person id (PERID)

- Line 4: Limitations line
  - Day of Badge
    - Bounding Box: 62,0 -> 80, 80
    - 14 pt Arial Bold Text
    - 3 character Day
  - Badge Limitation Note
    - Bounding Box: 62, 90 -> 80, 242
    - Restriction Lable in 14pt Arial Bold Type White on black box (reverse colors)
    - Center text in box

Alternate line 3/4:
- Day of Badge
  - Bounding Box 48, 0 -> 80, 100
  - 3 character day of one day
  - 30pt Arial Bold Font
- Info area
    - Bounding box: 48, 100 -> 62, 242
    - 12 pt Arial text + 2pt leading
    - Membership Category Label, a blank, and Person id (PERID)
- Badge Limitation Note
    - Bounding Box: 62, 80 -> 80, 180
    - Restriction Lable in 14pt Arial Bold Type White on black box (reverse colors)
    - Center text in box 
- Barcode
  - Bounding Box: 62, 180 -> 80, 240
  - Barcode of PERID 12 pt high

NOTE: Adjust bounding box of lines 3/4 for size of day label and barcode needs
