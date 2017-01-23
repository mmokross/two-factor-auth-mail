<?php

CONST LOGIN_VALID_HOURS = 10;

CONST SECRET_FILE = "twofactorSecrets";
CONST MAILTOKEN_VALID_SECONDS = 120;
CONST COOKIE_SECONDS = 3600 * LOGIN_VALID_HOURS;
CONST LOGIN_SECONDS = 3600 * LOGIN_VALID_HOURS;

// TODO:
// require_once zend environment, for sending mail, OR change lines below to send mail with different method


$ALLOWED_MAILS= [
    "test1@testabcd.com",
    "test2@test2bcd.org",
];

$secrets = [];
if (file_exists(SECRET_FILE)) {
    $secrets = unserialize(file_get_contents(SECRET_FILE));
}
$flash = "";

$sendMail =!empty($_POST["mail"]) ? $_POST["mail"] : null;
$givenToken =!empty($_POST["token"]) ? $_POST["token"] : null;
$loggedIn = false;

if (!$givenToken && $sendMail) {
    if (in_array($sendMail, $ALLOWED_MAILS)) {
        $token = rtrim(base64_encode(random_bytes(8)), "=");

        $secrets[$sendMail]["token"] = $token;
        $secrets[$sendMail]["valid"] = (new DateTime())->getTimestamp() + MAILTOKEN_VALID_SECONDS;

        file_put_contents(SECRET_FILE, serialize($secrets));


        // TODO:
        // Change this to different mail sending if you don't have the Zend environment
        $mailObjbect = new Zend_Mail();
        $mailObjbect->addTo($sendMail);
        $mailObjbect->setSubject("Login-Token fuer ".$_SERVER["SSL_TLS_SNI"]);
        $mailObjbect->setBodyText("Token: $token \n\n(2 Minuten gueltig)");
        $mailObjbect->setFrom("from@mydomain.org");
        $smtpServer = "smtp.mydomain.org";
        $emailLocalHostName = "mydomin.org";
        $transport = new \Zend_Mail_Transport_Smtp($smtpServer, array("name" => $emailLocalHostName));
        $mailObjbect->send($transport);

        $flash = "Mail with Token sent!";
    } else {
        $flash = "Error: Mail not known";
    }
}

if ($givenToken) {

    if (empty($secrets[$sendMail]["token"])) {
        $flash="There was no Token sent for this Email address!";
    } elseif ($secrets[$sendMail]["token"] != $givenToken) {
        $flash="Token is wrong!";
    } elseif ($secrets[$sendMail]["valid"] > (new DateTime())->getTimestamp() + MAILTOKEN_VALID_SECONDS) {
        $flash="Token has expired! (" . MAILTOKEN_VALID_SECONDS . " Sek.)";
    } else {
        $cookieVal = rtrim(base64_encode(random_bytes(20)), "=");

        unset($secrets[$sendMail]["token"]);
        $secrets[$sendMail]["cookieVal"] = $cookieVal;
        $secrets[$sendMail]["valid"] = (new DateTime())->getTimestamp() + LOGIN_SECONDS;
        file_put_contents(SECRET_FILE, serialize($secrets));

        setcookie("twofactor-cookie", "$sendMail-$cookieVal", (new DateTime())->getTimestamp() + COOKIE_SECONDS);
        $flash="Successfully Authenticated!";
        $loggedIn = true;
    }
}


?>

<html>
<body>
<?php if ($loggedIn): ?>
    <script type="text/javascript">
        location.href = location.href;
    </script>
<?php else:?>

    <h1>Simple 2-Factor-Auth</h1>
    <h4><?php echo $flash?></h4>
    <form method="POST">
        <b>Step 1: </b>Request E-Mail with Token:<br/>
        <input type="text" name="mail" value="<?php echo $sendMail?>" />
        <button type="submit">Send Mail</button>

        <br/><br/>
        <hr/>

        <b>Step 2: </b>Insert Token:<br/>
        <input type="text" name="token" />
        <button type="submit">Login</button>
    </form>

<?php endif ?>

</body>
</html>
