<?php

$ini_vars = parse_ini_file(__DIR__ . "/twoFactorConfig.ini");

$LOGIN_VALID_HOURS = $ini_vars["login_valid_hours"];
$SECRETS_FILE = $ini_vars["secrets_file_name"];
$MAILTOKEN_VALID_SECONDS = $ini_vars["mailtoken_valid_seconds"];
$COOKIE_SECONDS = 3600 * $LOGIN_VALID_HOURS;
$LOGIN_SECONDS = 3600 * $LOGIN_VALID_HOURS;
$ALLOWED_MAIL_ADDRESSES= $ini_vars["allowed_mail_addresses"];
$SERVER_MAIL_MESSAGE = $ini_vars["server_title_shown"] ?? $_SERVER["SSL_TLS_SNI"];

$SMTP_HOST=$ini_vars["smtp_host"];
$SMTP_PORT=$ini_vars["smtp_port"] ?? 25;
$SMTP_USERNAME=$ini_vars["smtp_username"] ?? null;
$SMTP_PASSWORD=$ini_vars["smpt_password"] ?? null;
$SMTP_LOCAL_DOMAIN=$ini_vars["smtp_local_domain"] ?? null;

$MAIL_FROM = $ini_vars["mail_from"];
$MAIL_FROM_TEXT = $ini_vars["mail_from_text"];

require_once __DIR__. "/vendor/autoload.php";

$secrets = [];
if (file_exists($SECRETS_FILE)) {
    $secrets = unserialize(file_get_contents($SECRETS_FILE));
}
$flash = "";

$sendMail = $_POST["mail"] ?? null;
$givenToken = $_POST["token"] ?? null;
$loggedIn = false;

if (!$givenToken && $sendMail) {
    /* SEND MAIL */
    if (in_array($sendMail, $ALLOWED_MAIL_ADDRESSES)) {
        $token = rtrim(base64_encode(random_bytes(8)), "=");

        $secrets[$sendMail]["token"] = $token;
        $secrets[$sendMail]["valid"] = (new DateTime())->getTimestamp() + $MAILTOKEN_VALID_SECONDS;

        if (FALSE === file_put_contents($SECRETS_FILE, serialize($secrets))) {
            $flash = "Cannot write to secrets file!";
        } else {
            $message = Swift_Message::newInstance()
                ->setSubject("Login-Token for ". $_SERVER["SSL_TLS_SNI"])
                ->setFrom([$MAIL_FROM => $MAIL_FROM_TEXT])
                ->setTo([$sendMail])
                ->setBody("Token: $token \n\n(valid $MAILTOKEN_VALID_SECONDS seconds)");
            ;
            $transport = Swift_SmtpTransport::newInstance($SMTP_HOST, $SMTP_PORT)
                ->setUsername($SMTP_USERNAME)
                ->setPassword($SMTP_PASSWORD);
            if ($SMTP_LOCAL_DOMAIN) {
                $transport->setLocalDomain($SMTP_LOCAL_DOMAIN);
            }
            $mailer = Swift_Mailer::newInstance($transport);
            $mailer->send($message);
            $flash = "Mail with Token sent!";
        }
    } else {
        $flash = "Error: Mail not known";
    }
}

if ($givenToken) {
    /* SET AUTH COOKIE */
    if (empty($secrets[$sendMail]["token"])) {
        $flash="There was no Token sent for this Email address!";
    } elseif ($secrets[$sendMail]["token"] != $givenToken) {
        $flash="Token is wrong!";
    } elseif ($secrets[$sendMail]["valid"] > (new DateTime())->getTimestamp() + $MAILTOKEN_VALID_SECONDS) {
        $flash="Token has expired! ($MAILTOKEN_VALID_SECONDS seconds.)";
    } else {
        $cookieVal = rtrim(base64_encode(random_bytes(20)), "=");

        unset($secrets[$sendMail]["token"]);
        $secrets[$sendMail]["cookieVal"] = $cookieVal;
        $secrets[$sendMail]["valid"] = (new DateTime())->getTimestamp() + $LOGIN_SECONDS;
        file_put_contents($SECRETS_FILE, serialize($secrets));

        setcookie("twofactor-cookie", "$sendMail-$cookieVal", (new DateTime())->getTimestamp() + $COOKIE_SECONDS);
        $flash="Successfully Authenticated!";
        $loggedIn = true;
    }
}
?>


<html>
<body>
    <?php if ($loggedIn): ?>
        <script type="text/javascript">
            // Reload page on Success
            location.href = location.href;
        </script>
    <?php else:?>

        <h1>Simple 2-Factor-Auth </h1>
        <b>(<?php echo $SERVER_MAIL_MESSAGE ?>)</b>
        <h4><?php echo $flash?></h4>
        <form method="POST">
            <b>Step 1: </b>Request E-Mail with Token:<br/>
            <input type="text" name="mail" value="<?php echo $sendMail?>" />
            <button type="submit">Send Mail</button>

            <br/><br/><br/>
            <hr/>

            <b>Step 2: </b>Insert Token:<br/>
            <input type="text" name="token" />
            <button type="submit">Login</button>
        </form>

    <?php endif ?>

    <?php if (!empty($_GET["debug"])): ?>
        <?php echo $_GET["a"]?><br/>
        <?php echo $_GET["b"]?><br/>
    <?php endif ?>
</body>
</html>
