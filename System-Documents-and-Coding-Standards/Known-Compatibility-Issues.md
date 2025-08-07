# Known Compatibility Issues
This file will document issues we've discovered that some browsers, databases, or PHP versions.  These are incompatibility issues that broke existing 
ConTroll code.  

## Javascript Issues

### ECMAScript 2018 Regexp Lookbehind

Apple Safari versions before 2023 before iOS 16.4 macOS 14.4 did not implement the Javascript 2018 standard for lookbehind.  
While Apple implemented the rest of the ECMAScript 2018 standard, this feature lagged behind until mid 2023

In ConTroll the email validation regular expression used lookbehind.  This failed in Seattle's iPad Mini 4's which ended support at iOS 15 (15.8.4).
The validateAddress routine in global.js needed to be modified to detect this version and adapt. 
Just trying to compile a regular expression caused a fatal error.

A similar work around will be needed for any regular expression using lookbehind at least for the next few years after 2025, 
until all devices of the same era as the iPad mini 4 are retired.


