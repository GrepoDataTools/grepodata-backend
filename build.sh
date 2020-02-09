#!/bin/bash
timestamp=$(date +%y%m%d_%H%M%S)

# Make dir
cd "/home/vps/grepodata/acceptance/grepodata-backend"
dirname="dist_v${timestamp}"
echo "=== Creating new directory: ${dirname}"
mkdir "$dirname" || exit 1

# Clone repo
echo "=== Cloning grepodata-backend to directory: ${dirname}"
cd "$dirname"
git init .
git remote add -t \* -f origin https://github.com/grepodata/grepodata-backend/ || exit "$?"
git checkout develop || exit "$?"
git log -1

echo "=== Moving config"
cp config.private.php "${dirname}/Software/config.private.php" || exit 1

# Composer install
echo "=== Running composer install"
composer install

# Update active
if [ -f vendor/autoload.php ]; then
  echo "=== Updating active syslink to: ${dirname}"
  cd "/home/vps/grepodata/acceptance/grepodata-backend"
  rm active
  ln -s "$dirname" active
else
  echo "=== Could not find composer autoload file. Installation may have failed. Aborting build."
  exit 1
fi

echo "=== Done!"
exit