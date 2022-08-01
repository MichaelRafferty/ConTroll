LOCATION=/path-to-your-head-directory
cd $LOCATION
sh $LOCATION/scripts/backup_mysql >> $LOCATION/cronlog/backups.log 2>&1

