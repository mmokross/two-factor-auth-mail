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

 **1.** install PHP 7.0 or newer

 **2.** get composer:
  
        wget https://getcomposer.org/installer
        php installer
        rm installer
        php composer.phar require mdi22/two-factor-auth-mail

**3.** copy all files (or at least the following) to the directory that has to be secured:
 * twoFactorLogin.php
 * apacheCheckTwoFactor.php
 * twoFactorConfig.ini.dist
 * the *vendor/* directory
 
The script apacheCheckTwoFactor.php is needed by apache globally and can be copied to a separate place, if multiple sites must be secured on the server.

**4.** Create a file like e.g. "twofactorSecrets" in that directory and make sure it can be accessed by apache.
E.g. in Ubuntu bash: 


        cd /var/www/my/dir/to/secure
        touch twofactorSecrets
        chgrp www-data twofactorSecrets;  # ubuntu specific, group might also be apache2 etc.
        chmod g+w twofactorSecrets;

**5.** copy twoFactorConfig.ini.dist to twoFactorConfig and set your configuration accordingly. Lines starting with ";" are comments


**6.** Add a *.htaccess* file to the directory OR edit the Apache \<VirtualHost> config OR \<Directory> config

 Hint: In Ubuntu phpmyadmin default installations in /usr/share/phpmyadmin, the \<Directory> directive in  /etc/apache2/conf-available/phpmyadmin.conf has to be used.

Add the following lines to the acording file and change the path to the secret file in RewriteCond:


        # TWO-FACTOR-AUTH
        RewriteEngine on
        RewriteCond ${TwoFacAuth:%{HTTP_COOKIE};
            /var/www/html/mysite/twoFactorSecrets} !^OK.*
        RewriteRule ^(.*)$ twoFactorLogin.php [L,QSA]
        
        ## Debug Rule: (replace rule above):
        ## RewriteRule ^(.*)$ twoFactorLogin.php?a=${TwoFacAuth:%{HTTP_COOKIE};/var/www/html/mysite/twoFactorSecrets}} [L,QSA]
        
You can also have a look at *htaccess-example*.
        
**7.** <b>Only</b> for phpmyadmin:

    Add a second RewriteCond directive directly below the first one. This will ensure the two-factor-cookie will not be overridden by phpmyadmin:
     
        RewriteCond %{HTTP_COOKIE} phpMyAdmin=(.*)
        
    Unfortunately, the phpmyadmin site has to be loaded twice, but that's the only disadvantage of that solution.
        
**8.** Define a Rewite Map in Apache - this can be done globally or in your VirtualHost or Directory configuration where you made the definitions above. This can **not** be done in .htaccess!!
 
    Change the path accordingly to your apacheCheckTwoFactor.php.
        
        # enable 2-factor-auth
        RewriteEngine On
        RewriteMap TwoFacAuth "prg:/my/path/to/file/apacheCheckTwoFactor.php"
        
You can also have a look at *apacheConf-example*.
        
**9.** Reload Apache

e.g.
        
        systemctl reload apache2
        
or
        
        /etc/init.d/apache2 reload

#
### Debugging:

Change the last Apache RewriteRule to the commented rule in 6)
    
Now, if you add "*?debug=1*" to your URL in the browser, the error messages of apacheCheckTwoFactor.php are displayed in the web interface if the authenitication fails.


#
### Help for improvement is welcome!
