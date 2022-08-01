# grepodata-backend

PHP 7 backend for grepodata.com

## Building
- Create a `config.private.php` file using the file `grepodata-backend/Software/config.example.php` as an example.
- Run `composer install` to install the required packages
- To convert reports to images: make sure `wkhtmltoimage` is available on the server CLI
- To create the daily world map gif: make sure `ffmpeg` is available on the server CLI
