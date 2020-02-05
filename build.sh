#! /bin/bash
timestamp=$(date +%y%m%d_%H%M%S)

# Make dir
dirname="dist_v${timestamp}"
echo "=== Creating new directory: ${dirname}"
mkdir "$dirname"
chown vps:vps "$dirname"

# Clone repo
echo "=== Cloning gd-stats-api to directory: ${dirname}"
cd "$dirname"
sudo -u vps git clone git@github.com:grepodata/grepodata-backend.git .
git log -1

# Composer install
echo "=== Running composer install"
composer install

# Update active
if [ -f vendor/autoload.php ]; then
	echo "=== Updating active syslink to: ${dirname}"
	cd /home/vps/gd-stats-api
	rm active
	ln -s "$dirname" active
else
	echo "=== Could not find composer autoload file. Installation may have failed. Aborting build."
fi
