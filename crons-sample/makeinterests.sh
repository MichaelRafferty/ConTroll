PHPPATH=/var/nfph-opt/alt/php81/usr/bin
PATH=$PHPPATH:$PATH:$HOME/.local/bin:$HOME/bin
TZ='America/Los_Angeles'

export PATH TZ

LOCATION=/path-to-your-head-directory
cd $LOCATION/scripts
date >> $LOCATION/cronlog/sendinterests.log 2>&1
php sendinterests.php >> $LOCATION/cronlog/sendinterests.log 2>&1
