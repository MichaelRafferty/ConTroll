# This script rebuilds all the sym links and images directories for file management
SCRIPT_DIR=$(dirname "$0");
echo "Running from: " $SCRIPT_DIR

# loop over directories looking for the actual directories we need first the images
for d in atcon controll onlinereg portal vendor
do
  echo "Checking $d"
  if [ -d $SCRIPT_DIR/../$d ]; then
    echo "$d/images found"
  else
    mkdir $SCRIPT_DIR/../$d/images
    chmod g-w $SCRIPT_DIR/../$d/images
    echo "$d/images created"
  fi
done

echo "Checking controll/reportdata"
if [ -d $SCRIPT_DIR/../controll/reportdata ]; then
  echo "controll/reportdata found"
else
  mkdir $SCRIPT_DIR/../controll/reportdata
  chmod g-w $SCRIPT_DIR/../controll/reportdata
  echo "controll/reportdata created"
fi

# now rebuild the symlinks in controll
cd $SCRIPT_DIR/../controll
rm atconimages onlineregimages portalimages vendorimages ReleaseNotes
ln -s ../atcon/images atconimages
ln -s ../onlinereg/images onlineregimages
ln -s ../portal/images portalimages
ln -s ../vendor/images vendorimages
ln -s ../ReleaseNotes ReleaseNotes

echo "Symlinks rebuild, relink completed"
