<?php

## MAPS (~65k files per year @ 100kb/file; ~6.6GB/year)
# Goal: delete maps older than 2 years
# grepodata/production/grepodata-frontend/maps/{{WORLD}}/
# exclude grepodata-frontend/maps/mapbg.png
# exclude /grepodata-frontend/maps/{{WORLD}}/[map_today.png, animated.gif, temp.mp4]

## REPORTS (~80k files per year @ 240kb/file; ~19.2GB/year)
# Apache2 is configured to route 404s to report_notfound.png
# Goal: delete reports older than 1 year; exclude report_notfound.png
# Count reports older than 2 years: find /home/vps/grepodata/production/grepodata-frontend/reports/ -mtime +730 | wc -l
# Delete: find /home/vps/grepodata/production/grepodata-frontend/reports/ -mtime +730 ! -name "report_notfound.png" -delete


## COMMANDS:
# total filesystem usage: df -h
# count size of folder: du -hs FOLDER