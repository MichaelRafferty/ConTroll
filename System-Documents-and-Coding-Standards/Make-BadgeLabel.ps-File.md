# How to make a badgeLabel.ps File
An optional feature to ConTroll badge labels is the ability to embed a graphic in the background to brand the label and make them slightly harder to fake.
This document describes how to create such a file.

## Software Required
- You need some software to convert a image file into an EPSF file, which you will be editing to embed it into the standard blank label.
  - Adobe Photoshop is what the Authors use to perform this function.  All directions in this
  document will be based on Adobe Photoshop.  However, any software that can convert an image to
  an EPSF file will work.

## Logo Format
Any logo in PNG or JPEG format will work if the software can output it as an EPSF file.
### Logo Specifications
- Size: 
  - height 200px, image should somewhat close to a 1:1 aspect ratio, 
  but it doesn't have to be exact. 
  This is a 1:1 scaling and is recommended to keep the image size small.
- Background: white, or transparent.  It doesn't matter, as it prints as the bottom layer of the
  label.
- Color format: grayscale

## Steps:
1. Open the logo in Photoshop
2. If needed change the image mode to grayscale
3. Crop the image to remove any excess whitespace from around the image.
4. Resize the image to 200 pixels high. Leave the aspect ratio linked and let the horiziontal size
   range from 100 to 300.  If the image is more out of apsect ration than that, 
   choose a different logo.
5. Set the blend transparency of the image to between 25% and 35%  Past experience has shown 30% 
   to give the best visibility.
6. Save the file using "Save a copy" and output it as a "Photoshop EPS file."
   1. Specify the options on the first save screen as all unchecked (no proof, no color profile)
   2. On the "EPS Options" popup:
      1. Preview: None
      2. Encoding: ASCII85
      3. All other options unchecked
7. Copy the file `config-sample/BaddgePrintTestItems/blank.ps` to `config/<name>.ps` 
   where name is the name you choose for this label variant.
8. Use a text editor to edit the new file `config/<name>.ps`
    1. Find the commented out line `%.25 .25 scale` and delete that and the following line.  Let Photoshop do the scaling and the blend transparency.
   2. After the `/showpage` and before the `%%Trailer` line insert the contents of your logo epsf file.  You may need to convert carriage returns in that 
      file to newlines. The section should now read:      

          180 4 translate
          /showpage {} bind def
          
          %!PS-Adobe-3.0 EPSF-3.0`
   3. Delete all of the lines from `%BeginPhotoShop:` through `%end_xml_code` inclusive. The next line should be `gsave % EPS gsave`.
   4. At the end of the included data delete the lines after the trailing `grestore % EPS grestore` so the next line after that is the line `%%Trailer` from 
      the original blank.ps file.
   5. Adjust the `180 4 translate` as needed to position the image and scale it appropriately.
   6. Save the resulting file.
