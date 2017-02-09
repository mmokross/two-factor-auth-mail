#!/usr/bin/php
<?php

set_file_buffer(STDOUT, 0);

while (!feof(STDIN)) {

    $line = urldecode(rtrim(fgets(STDIN), "\n"));
    $mail = preg_replace('/^.*twofactor-cookie=(.*)\-.*$/', '$1', $line);
    $mail = explode("-", $mail)[0];
    $cookieSecret = preg_replace('/^.*twofactor-cookie=[^\-]*\-(.*;)/', '$1', $line);
    $cookieSecret = explode(";", $cookieSecret)[0];
    $SECRETS_FILE = preg_replace('/^.*\;([^\;]*)$/', '$1', $line);

    $secrets = [];
    if (FALSE !== file_get_contents($SECRETS_FILE)) {
        $secrets = unserialize(file_get_contents($SECRETS_FILE));
    } else {
        fputs(STDOUT,"FAIL:SECRET-FILE-NOT-EXISTS:" . $SECRETS_FILE . "\n");
        continue;
    }

    $timestamp = (new DateTime())->getTimestamp();

    if (!isset($secrets[$mail]["cookieVal"])) {
        fputs(STDOUT,"FAIL:MAIL-NOT-KNOWN $mail\n");
        continue;

    }
    if ($secrets[$mail]["cookieVal"] != $cookieSecret) {
        fputs(STDOUT,"FAIL:FAIL-WRONG-VALUE $cookieSecret\n");
        continue;
    }
    if ($timestamp >= $secrets[$mail]["valid"]) {
        fputs(STDOUT,"FAIL:FAIL-EXPIRED\n");
        continue;
    }
    fputs(STDOUT,"OK\n");

}
