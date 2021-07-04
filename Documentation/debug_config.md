# Configuration phpstorm xdebug

1. Install xdebug extension for php
2. Create a phpstorm Web Page debug config using the Documentation/xdebug.run.xml file
3. Add these lines to the active php.ini file:

````
[xdebug]
zend_extension="c:/wamp64/bin/php/php7.2.33/zend_ext/php_xdebug-2.9.6-7.2-vc15-x86_64.dll"
xdebug.idekey=PHPSTORM
xdebug.remote_enable=1
xdebug.remote_autostart=1
xdebug.remote_host=127.0.0.1
xdebug.remote_port=9000
xdebug.remote_handler = dbgp
xdebug.profiler_enable = off
xdebug.profiler_enable_trigger = Off
xdebug.profiler_output_name = cachegrind.out.%t.%p
xdebug.profiler_output_dir ="c:/wamp64/tmp"
xdebug.show_local_vars=0
````
4. Start listening for connections to trigger breakpoints