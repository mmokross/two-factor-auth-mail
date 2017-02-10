# two-factor-auth-mail
Simple PHP script to secure a host/directory/site delivered with apache by sending a mail with a token. A cookie is created to access these site which is valid for a defined time. Works also with phpmyadmin.

The idea is, that it is much over the top to have a two-factor-auth with a smartphone app or SMS, if you just want to secure e.g. phpmyadmin from access of everyone from the internet to a limited list of users with email addresses. 

The two-factor-auth works by sending an email to one of pre-defined mail addresses. After the token has been sent with a web-form, a cookie is created in the browser. The server checks if the cookie is correct and has not expired on the server side. The expiration time can be defined (default: 10 hours).

On every request the cookie authenticiation is executed. When the authentication is valid, the secured site is shown directly with apache. Otherwise, a rewrite to the login script id executed by apache. 

This script works well for me using an SMTP server.

Work, that could be done:
* configuration to work with sendmail or other methods 
* Translation ?
* Improve design
* Refactoring etc...

# Installation
(For Linux / Ubuntu, other platforms might differ)

 1. install PHP 7.0 or newer

* using composer:
 0. get composer:
 
        wget https://getcomposer.org/installer
        php installer
        php composer.phar require 

 

1. copy the 2 scripts twoFactorLogin.php and apacheCheckTwoFactor.php to the directory that has to be secured on the server (or copy it somewhere and create a symbolic link of twoFactorLogin.php in the directory to secure). The script apacheCheckTwoFactor.php must be set executable.

2. Create a file "twofactorSecrets" in that directory and make sure it can be accessedd by apache.
E.g. in Ubuntu bash: 


        cd /var/www/my/dir/to/secure
        touch twofactorSecrets
        chgrp www-data twofactorSecrets;  # ubuntu specific, group might also be apache2
        chmod g+w twofactorSecrets;

3. Edit the Apache \<VirtualHost> config OR \<Directory> config OR add a .htaccess file to the directory to secure

 Hint: In Ubuntu phpmyadmin default installations in /usr/share/phpmyadmin, the \<Directory> directive in  /etc/apache2/conf-available/phpmyadmin.conf has to bes used.

4. Add the following lines to the file found in 3. and set the path in RewriteConde:

        # TWO-FACTOR-AUTH
        RewriteEngine on
        RewriteCond ${TwoFacAuth:%{HTTP_COOKIE};
            /usr/share/phpmyadmin/twofactorSecrets} !^OK.*
        RewriteRule ^(.*)$ twoFactorLogin.php [L,QSA]
        
        ## Debug Rule: (replace rule above):
        ## RewriteRule ^(.*)$ twoFactorLogin.php?a=${TwoFacAuth:%{HTTP_COOKIE};/usr/share/phpmyadmin/twofactorSecrets}} [L,QSA]
        
5. <b>Only</b> for phpmyadmin:

    Add a second RewriteCond directly below the first one. This will ensure the two-factor-cookie will not be overridden by phpmyadmin:
     
        RewriteCond %{HTTP_COOKIE} phpMyAdmin=(.*)
        
6. Define a Rewite Map in Apache - this can be done globally or in your VirtualHost or Directory directive where you madde the definitions above. This can **not** be done in .htaccess . Change the path to apacheCheckTwoFactor.php.
        
        # enable 2-factor-auth
        RewriteEngine On
        RewriteMap TwoFacAuth "prg:/set/path/to/file/apacheCheckTwoFactor.php"

7. Replace the path /usr/share/phpmyadmin/ by the path to secure (where the file "twofactorSecrets" is stored)

8. Relaod Apache (not necessary when using .htaccess)
        
        systemctl reload apache2


### Debugging:

1. Change the last Apache RewriteRule to the commented rule below
2. Add a line 

        print_r($_GET);
    below the <?php in the file twoFactorLogin.php
    
Now, when the authenitication fails, the error messages of apacheCheckTwoFactor.php are displayed in the web interface.

# Help for improvement is welcome!
