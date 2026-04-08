# How to make a badgeLabel.png File
An optional feature to ConTroll badge labels is the ability to embed a graphic in the background to brand the label and make them slightly harder to fake.
This document describes how to create such a file.

## Software Required
- You need some software to convert a image file into an png file with alpha channel
  - Adobe Photoshop is what the Authors use to perform this function.  All directions in this
  document will be based on Adobe Photoshop.  However, any software that can edit a PNG file will work.

## Logo Format
Any logo in PNG or JPEG format will work if the software can output it as an PNG file with alpha channel.
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
6. Save the file using "Save a copy" and output it as a "PNG File."
7. Upload this file to the config directory.
8. Edit the reg_admin.ini file and set the line:

       badgeLogo=name.png

      where name is the name you choose for this label variant.
