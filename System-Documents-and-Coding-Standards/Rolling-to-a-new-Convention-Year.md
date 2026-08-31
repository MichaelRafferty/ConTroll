# Rolling ConTroll to a New Convention Year

While one-off conventions like WorldCon's don't have this issue, every year a recurring convention has to roll the system from the prior convention year to 
the new one.  ConTroll automates most of this for you.  However there are some things you will still need to check and clean up.

## Overview
The major steps are:
1. Take a database backup before changing anything, save this off as the final backup for the prior year.
2. Clean up the backups directory removing unnecessary old backups.
3. Edit the reg_admin.ini file 
   1. Suspend the portals by changing the suspended=0 to suspended=1 while you upgrade for the new year
   2. Change the id= from the current value to the value for the next convention year
4. Edit the reg_conf.ini file for the new year (usually the con number is in some email addresses or strings.) Note: you can do this later in the system 
   using the configuration editor.
5. Let the system do the major work of the upgrade by running the build new year tool from the home page of ConTroll.
6. Clean-up and things that are not exactly right, like dates that it did not correctly approximage.

## Detailed Steps

### Logout
1. Logout of ConTroll, any portal windows and any exhibitor portal windows.

### Backups
It is a good idea to have a final backup database of the system. 
1. Using the bkup.sh script in the crons directory, run:
```sh crons/bkup.sh```
2. Change to the backups database and rename the backup it just created something like: (conname)_reg.(old id value).final.bz2
3. Remove any backups from last year that are not the final backup. If you wish to keep some of them, that is ok, but you do not need every day.

### Edit the configuration files
1. In the config directory, edit the reg_admin.ini file:
   1. The convention id needs changing. Change the id= line to the new convention id
   2. Suspend access to the system by changing the global suspended=0 to suspended=1
   3. Update the suspend reason variable as needed
   4. Save the file
2. Optionally edit the reg_conf.ini file.  Note: you can do this later in the system using the configuration editor.
   1. Anywhere the convention id is found, change it to the new one.
   2. Update any email addresses that are no longer correct. In particular
      * label=
   3. Save the file

### Run the systems built in update process
1. Sign into the controll back end as an admin.
2. On the home page, you should see a button that says: ```Build <id> Setup```  
   Where id is the new convention id.
3. Click the button, it will tell you what it did, note any errors or "unable to" items for further cleanup
4. Check that it built the data properly for the new convention and the one following it:
   1. In Registation Admin:
      1. Check Current Convention Setup:
         1. In Current Convention Data: Check the start and end date of the convention were correctly changed. 
         It uses the same week of the month and adjusts the day, your convention could have a new date, or the week may be off by one.
         2. In Current Membership types: Make sure the start date, end date, and price for each item is correct for the new year.
It uses a formula to guess the new proper dates, but they may not be exactly correct.
      2. Repeat this process for "Next Convention Setup"
      3. Check the ages in "Membership Configuration". They should be just fine for both years,
but if the convention has changed the meanings or age types, update this accordingly.
      4. Check Membership Rules to make sure all of the rules updated their memlist entries correctly.
      5. Use Configuration editor to update any other settings that many need to be changed for the new year.

### Exhibits Configuration
1. Check the Regions for this year tab and updaete the email addresses if needed, and make sure the included and additional memtypes are correct.
2. Check the Spaces within the Region and make sure they are still correct for this year
3. Check the space pricing options and make sure they are still correct for this year

### Take a starting backup
Run crons/bkup.sh to take a starting backup of the new year.
### Re-enable access to the system
Edit the reg_admin.conf file and set suspended back to 0 to re-enable the system.

