# two-factor-auth-mail
Simple PHP script to secure a host/directory delivered with apache site by sending a mail with a token. A cookie is created to access these site which is valid for a defined time. Works also with phpmyadmin.

The idea is, that it is much over the top to have a two-factor-auth with a smartphone app or SMS, if you just want to secure e.g. phpmyadmin from access of everyone from the internet to a limited list of users with email addresses. 

The two-factor-auth works by sending a mail to pre-defined mail addresses. After the token has been sent with a web-form, a cookie is created in the browser. The server checks if the cookie is not expired on the server side. The expiration time can be define (default: 10 hours).

On every request the cookie authenticiation is executed. When the authentication is valid, the secured site is shown directly with apache. 

This script works well for me.
To work for everyone, the following has to be done:
* Change the code for mail sending so that there is no more dependency on Zend Mailer (or install Zend1)
* Add configuration interface / ini file instead of using constants
* Improve design
* Translation ?
* Refactoring etc...

# Installation
(For Linux / Ubuntu, other platforms might differ)

1. copy the 2 scripts twoFactorLogin.php and apacheCheckTwoFactor.php to the directory that has to be secured on the server (or copy it somewhere and create a symbolic link of twoFactorLogin.php in the directory to secure). The script apacheCheckTwoFactor.php must be set executable.

2. Create a file "twofactorSecrets" in that directory and make sure it can be accessedd by apache.
E.g. in Ubuntu bash: 


    cd <dir>
    touch twofactorSecrets
    chgrp www-data twofactorSecrets;  # ubuntu specific, group might also be apache2
    chmod g+w twofactorSecrets;

3. Edit the Apache \<VirtualHost> config OR \<Directory> config OR add a .htaccess file to the directory to secure

Hint: In Ubuntu phpmyadmin default installations in /usr/share/phpmyadmin, the \<Directory> directive in  /etc/apache2/conf-available/phpmyadmin.conf has to bes used.

4. Add the following lines to the file found in 3.:

        # TWO-FACTOR-AUTH
        RewriteEngine on
        RewriteCond ${TwoFacAuth:%{HTTP_COOKIE};
            /usr/share/phpmyadmin/twofactorSecrets} !^OK.*
        RewriteCond %{HTTP_COOKIE} phpMyAdmin=(.*)
        RewriteRule ^(.*)$ twoFactorLogin.php [L,QSA]
        
        ## Debug Rule: (replace rule above):
        ## RewriteRule ^(.*)$ twoFactorLogin.php?a=${TwoFacAuth:%{HTTP_COOKIE};/usr/share/phpmyadmin/twofactorSecrets}} [L,QSA]

5. Replace the path /usr/share/phpmyadmin/ by the path to secure (where the file "twofactorSecrets" is stored)

6. Relaod Apache (not necessary when using .htaccess)
        
        systemctl reload apache2


### Debugging:

1. Change the last the Apache RewriteRule to the commented rule below
2. Add a line 

        print_r($_GET);
    below the <?php in the file apacheCheckTwoFactor.php
    
Now, when the authenitication fails, the error messages of

# Help for improvement is welcome!