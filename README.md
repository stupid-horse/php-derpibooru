php-derpibooru
==============

About
-----

This is a collection of various PHP tools to assist you in archiving derpibooru.

Usage
--------
- zautoadd.php
    $ php zautoadd.php
    This file takes no arguments and will query the database for the largest image ID, then polls Derpibooru for the newest image id. It will then download everything in between.
    
    


Installing
----------

Simply create a MySQL database, and import the provided "database.sql" file. 

Configure the settings at the top of "phpstart.php".

Edit the function notify to your liking. It is invoked when a few things happen. Pretty useful. 

Use zcheckdb.php to crawl for images between the id's you give it. See usage for more info. It is recommended that you use multiple instances of zcheckdb at varying intervals to speed up downloads.

Make sure you complete your database before you do this step (unless you like waiting).

Add a cronjob on whatever interval you like for "php zautoadd.php", use a webcron, or call the page whenever you want missing images to be added to your database.

Feedback
--------

Please open a GitHub Issue.

License
-------

Licensed under GNU GPL v2
See LICENSE for the full license text
