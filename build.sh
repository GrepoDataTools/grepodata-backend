#!/bin/bash
timestamp=$(date +%y%m%d_%H%M%S)
projectdir="/home/vps/grepodata/production/grepodata-backend"

# Make dir
cd $projectdir
dirname="dist_v${timestamp}"
echo "=== Creating new directory: ${dirname}"
mkdir "$dirname" || exit 1

# Clone repo
echo "=== Cloning grepodata-backend to directory: ${dirname}"
cd "$dirname"
git init .
git remote add -t \* -f origin https://github.com/grepodata/grepodata-backend/ || exit "$?"
git checkout master || exit "$?"
git log -1

# Composer install
echo "=== Running composer install"
composer install

# Update active
if [ -f vendor/autoload.php ]; then
  cd $projectdir

  echo "=== Moving config to ${dirname}/Software/config.private.php"
  cp config.private.php "${dirname}/Software/config.private.php" || exit 1

  echo "=== Updating active syslink to: ${dirname}"
  rm active
  ln -s "$dirname" active
else
  echo "=== Could not find composer autoload file. Installation may have failed. Aborting build."
  exit 1
fi

echo "=== Done!"
exit