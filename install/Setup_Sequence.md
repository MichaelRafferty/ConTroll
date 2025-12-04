# Setting up the ConTroll™ Registration System on a new server

### Notes:
This set of instructions is high level.
It expects you know how to create databases in the database server and can set up your web server.
It also expects you to know how to fetch/update files from GitHub.
The software and these instructions reside in the repository
    https://github.com/MichaelRafferty/ConTroll

1.  Make sure all prerequisites in System_Requirements.txt are satisified by your system.

2.  Decide what account is going to host ConTroll.
    1. These instructions will create two complete systems: reg and reg-test.
        1. create said account and create two directories under it:
        2. one for reg (labeled {reg} below) 
        3. one for reg-test (labeled {reg-test} below}.
        4. We recomment using the FQDN for the site as the directory name to make it clear which site you are editing/updating.
    2. retrieve the production tree to reg and the test tree to reg-test.  
       1. If you wish to use master and test branches, use git clone of
           MichaelRafferty/ConTroll.git. 
       2. If you wish to make local changes, create your own github account and create a fork of MichaelRafferty/ConTroll.git.
    
       In either case use your repositories path in the statements below.

           cd {reg}
           git clone https://github.com/MichaelRafferty/ConTroll.git
           cd {regtest}
           git https://github.com/MichaelRafferty/ConTroll.git

    3. copy the regfetch script to your bin directory
    
           cd {reg}/ConTroll/scripts-sample
           cp regfetchprod ~/bin
           cp regfetchprod ~/bin/regfetchtest
    
    4. Edit those two scripts in the bin directory:
       1. Change the HOMEDIR in the scripts to your locations
       2. Set the branch to the branch you are using
    5. Make sure the appropriate version of PHP (>=8.2) is in your PATH
       - (edit your appropriate profile if needed to add it)
    6. Make sure your approiate time zone variable is in your profile
    7. Run php -v and check output for proper version, if wrong, go back to PATH step and fix it.
    8. Run composer for each tree by refetching the tree. The fetch script will handle composer.
    
             regfetchprod
             regfetchtest
    9. Make the local directories in each directory (if they do not already exist)

         cd {reg}/ConTroll
         mkdir backups config cronlog crons reglogs scripts onlinereg/images vendor/images portal/images controll/images
         cd {regtest}/ConTroll
         mkdir backups config cronlog crons reglogs scripts onlinereg/images vendor/images portal/images controll/images

   3. Create the two databases in your database server
      1. Suggested names are reg and reg_test
      2. Load the print servers schema into each database
         1. Order of load is:
            1. schema
            2. servers
            3. printers
            4. routines
      3. Copy the sample configuration files into the config directory
   
             cd config
             cp ../config-sample/reg-secret.ini-sample reg-secret.ini
             cp ../config-sample/reg-admin.ini-sample reg-admin.ini
             cp ../config-sample/reg-conf.ini-sample reg-conf.ini
   
      4. Edit at least the [mysql] section of the reg-secret.ini configuration to set the database parameters
      5. Load the reg schema into each database using the InstallSetup.php program
   
                  cd {reg}/install
               
      6. Use php InstallSetup.php -h to check the appropriate options for your system.
      7. It is recommended that the schema be set up outside of InstallSetup.php.
      8. If you have not yet edited the rest of reg_conf.ini, use the -v option

4. Populate the tables far enough to use the GUI to continue, or as far as you want.
    1. Note: InstallSetup will populate initial values for conlist,
    user, user_auth, atcon_user and atcon_auth for the first user.
    2. The schema install will populate auth, patchLog and the custom text  tables.  
    3. It also  populates the required values in 
       1. memCategories
       2. memTypes
       3. perinfo
       4. policies
    3. Necessary tables for the GUI:
       1. conList:
          - at least for the current convention
          - preferred for the current and the next convention
          - (can approximate the dates for that one), 
       2. InstallSetup.php will insert the first conlist entry.
          - user and user_auth:
             - InstallSetup.php will install your master admin for you.
          - atcon_user and atcon_auth:
             - InstallSetup.php will install your master manager for you.
          - Custom Text Tables
            - The required values in
                - memCategories
                - memTypes
                - perinfo

5. Edit the following files in your config directory
    - Edit reg_secret.ini copied in the steps above
      - Minimum just to get the GUI working:
        1. [mysql] section in its entirity
        2. [client] section in its entirity
        3. [google] all
        4. [cc] set up a test version to start
        5. [email] all
        6. [usps] none
        7. [api] none
    - Edit reg_admin.ini copied in the steps above
      - Minimum just to get the GUI working:
        1. [global] (all)
           - Note: testemail is used to override the to/cc addresses of all emails and send them to that email address instead
           - It should only be used in test systems
           - It is recommended in test systems to avoid sending emails to real users by accident
        2. [con]
           - conname: the short string identifying your convention
           - id: the numeric id of this year (ordinal or 4 digit calendar)
           - currency: USD is the default
           - minComp: earliest convention id to compare against
           - conLen: number of days in the convention
        3. [debug] (you can just use the defaults)
        4. [reg] none
        5. [atcon] none required to get started
        6. [vendor]
           - vendorsite
           - artistsite
        7. [portal]
           - portalsite
        8. [controll]
           - useportal: are you using the portal (1) or onlinereg (0)
    - Edit reg_conf.ini copied in the steps above
        - Mimimim just to get the gui working:
            1. [global]
               - required: which fields are required: see comments in file
               - (you can use the defaults for the rest for now)
            2. [con] 
               - label
               - server
               - (you can sue the defaults for the rest for now)
            3. [reg] (use the defaults for now)
            4. [atcon] (use the defaults for now)
            5. [vendor]
                - vendor (default admin email address)
                - artist (default admin email address)
                - (use the defaults for the rest to get started)            
            6. [portal] (use the defaults for now)
            7. [controll] (use the defaults for now)
            8. [local] (use the defaults for now)
 
6. Set up Google Authentication in the google console
     1. copy your json file into the config directory using the name you specified in the [google] block in reg_secret.ini file.
     2. make sure all of the url's are authorized to use google authentication
         1. (your controll URL))/
         2. (your portal URL))/
         3. (your controll test system URL)/
         4. (your portal test system URL)/
     3. Since the printservers database is used to hold the loanable Pi print servers,
   you will need to grant select access to it's tables to your reg database user
        
       GRANT SELECT ON printservers.servers TO '(reg_db_user)'@'(dbhost)';
       GRANT SELECT ON printservers.printers TO '(reg_db_user)'@'(dbhost)';

7. Set up your web server to server PHP FPM for the following URL types, for both the test and the production systems
    1. reg.(domain)  => 
       - => {reg}/onlinereg
         - (if using online reg as primary)
       - => {reg}/portal
         - (if using portal as primary)
    2. atcon.(domain) => .{reg}/atcon
    3. vendor.(domain) => {reg}/vendor
    4. artist.(domain) => {reg}/vendor 
       - yes, vendor, it uses the server name to determine which interface to show)
    5. controll.(domain) => {reg}/controll
    6. reg-test.(domain) 
       - => {reg-test}/onlinereg
         - (if using online reg as primary)
       - => {reg-test}/portal
         - (if using portal as primary)
    7. atcon-test.(domain) => .../reg-test/atcon
    8. vendor-test.(domain) => .../reg-test/vendor
    9. artist-test.(domain) => .../reg-test/vendor
    10. controll-test.(domain) => .../reg-test/controll

8. use controll's admin or the database directly to set up:
    1. users

9. use controll's reg-admin to set up:
    1. Membership Configuration Tables
    2. Current Configuration Setup

10. If you are running the Zambia scheduling system, zambia needs to access the perinfo, reg, and memList tables to keep its copy of the program
participant information updated and in sync with the ConTroll.  
Zambia pulls the membership and contact info for its program participants
and when a program participant updates their contact info in Zambia it pushs this 
change back to ConTroll as well, keeping them in sync.
You'll need to know the zambia_db_user and the host the database is on. The host is usually 'localhost';
The parens are not part of the sequnce below, just showing what you need to substitute.
    1. Grant select and update to the zambia user for perinfo:
    
           GRANT SELECT ON perinfo TO '(zanbia_db_user)'@'(databasehost);
           GRANT UPDATE ON perinfo TO '(zanbia_db_user)'@'(databasehost);
    
    2. Grant select only to reg and memList:
    
           GRANT SELECT ON reg to '(zanbia_db_user)'@'(databasehost);
           GRANT SELECT ON memList to '(zanbia_db_user)'@'(databasehost);

11. Set up the backup scripts in cron
    1. Create the scripts directory and copy the backup_mysql script from the scripts-sample directory to it.
    2. Set the variables at the top for your install

           DEST=full absolute path to the destination for the backups files.  Suggested is a directory called backups at the same level as scripts directory
           D=`/bin/date +%Y_%m_%d_%H` export D
           DBNAME=name of your registration database
           DBHOSTNAME=host name for the database server
           DBUSERID=database user id
           DBPASSWORD=database password
           DBPORT=this is usually 3306 for mysql, but could differ on your local server depending on how it was set up
    
    3. test the backup script by executing it manually
    4. make the crons and cronlog directory at the same level as the scripts directory
    5. copy the bkup.sh file from crons-sample to your crons directory
    6. set the variable for the install directory
        DIR=absolute path to the install directory (the one containing crons and scripts)
    7. add an entry to your crontab to call this script at the frequency of backups you prefer, suggested is every day
        
           crontab -e
           6 0 * * * /bin/sh /path-to-production-install-directory/crons/bkup.sh
           8 0 * * 0 /bin/sh /path-to-test-install-directory/crons/bkup.sh

12. set up the periodic scripts in cron
    1. for payment plan reminders:
        1. copy the planreminders.php script from scripts-sample to script
        2. copy the planreminders.sh script from crons-sample to crons
        3. edit the planreminders.sh script and set the apporpriate value for PHPPATH, and LOCATION
        4. add an entry to your crontab to call this script at the frequency of reminders you prefer, suggested is every day as it
            internally limits the reminders to once per week per plan.
    2. interest emails: notify departments of who change their interest requests
        1. copy the sendinterests.php script from scripts-sample to scripts
        2. copy the sendinterests.sh script from crons-sample to crons
        3. edit the sendinterests.sh script and set the apporpriate value for PHPPATH, and LOCATION
        4. add an entry to your crontab to call this script at the frequency of notifications you prefer, suggested is weekly
            
                7 0 * * 1 /bin/sh /path-to-production-install-directory/crons/sendinterests.sh
