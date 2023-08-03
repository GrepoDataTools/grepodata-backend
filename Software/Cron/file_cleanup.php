<?php

## MAPS (~65k files per year @ 100kb/file; ~6.6GB/year)
# Goal: delete maps older than 2 years
# grepodata/production/grepodata-frontend/maps/{{WORLD}}/
# exclude grepodata-frontend/maps/mapbg.png
# exclude /grepodata-frontend/maps/{{WORLD}}/[map_today.png, animated.gif, temp.mp4]

## REPORTS (~80k files per year @ 240kb/file; ~19.2GB/year)
# Goal: delete reports older than 1 year; exclude report_notfound.png
# for all reports, if report older than 1 year and if report is not already symlink:
#    DELETE file {{original_report_name.png}}
#    CREATE 404 symlink: ln -s report_notfound.png {{original_report_name.png}}


## COMMANDS:
# total filesystem usage: df -h
# count size of folder: du -hs FOLDER