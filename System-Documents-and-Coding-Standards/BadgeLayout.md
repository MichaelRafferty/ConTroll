# Badge Layout - Postscript and Proposed PDF Layout
Current print badge format is PDF.

## PDF layout
Badge is 81pt's in height (1.125 inches) and 250 pt wide (~3.5 inches) 
The print area is 81pt height and 234pt wide. 

- Background Image: a png file scaled to 1"x1" (or the config file height and width)
  - position of logo: 2.2x, 0.1y (from top left corner of printable area)
  
- Line1: Primary Badge Name
  - position: 6/72x, 15/72y
  - Variable font width and weight to keep as much as it can on line 1
    - 22pt then 20pt
    - Roborto Bold, then Roberto Semi Condensed Bold, then Roberto Condensed Bold
    - If it can't make all of badge name line 1 fit on the first line
      - badge line 2 is discarded
      - badge line 1 is split on a blank or number of characters if no blanks to fit maximum amount on line 1, remainder of line 1 becomes line 2

- Line2: Secondary Badge Name
  - position: 6/72x, 37/72y
  - Variable font width and weight to keep as much as it can on line 1
    - 16pt, 15pt, 14pt
    - Roborto Bold, then Roberto Semi Condensed Bold, then Roberto Condensed Bold
    - if too long to fit (either remainder or line 2 contents) truncate to fit

- Line 3: Day of Week
  - position: x: 6/72, y: 57/72
  - Roberto Black Italic (BI), 24pt

- Line 3: Type and badge number
  - position: x: 112/72, y: 53/72
  - Roberto Regular, 11pt

- Line 4: Barcode
  - Barcode
    - position: x: 12/72, y: 69/72
    - Barcode of PERID (90/72x8/72 width and height of barcode area)

- Line 4: Limitations line
  - Holds age flag
  - Reverse Image (white type on back box)
    - position of box: x: 112/72, y: 67/72 filled in black
  - Text: white, FFFFFF
    - centered in box, width of area 85/72
    - Font is Roborto Black (BK) 11pt
