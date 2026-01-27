# Badge Layout - Postscript and Proposed PDF Layout
Current print badge format is PDF.

## PDF layout
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
