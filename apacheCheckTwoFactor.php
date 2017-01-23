#!/usr/bin/php
<?php

set_file_buffer(STDOUT, 0);

while (!feof(STDIN)) {

    $line = urldecode(rtrim(fgets(STDIN), "\n"));
    $mail = preg_replace('/^.*twofactor-cookie=(.*)\-.*$/', '$1', $line);
    $mail = explode("-", $mail)[0];
    $cookieSecret = preg_replace('/^.*twofactor-cookie=[^\-]*\-(.*);/', '$1', $line);
    $cookieSecret = explode(";", $cookieSecret)[0];
    $secretFile = preg_replace('/^.*\;([^\;]*)$/', '$1', $line);

    $secrets = [];
    if (file_exists($secretFile)) {
        $secrets = unserialize(file_get_contents($secretFile));
    } else {
        fputs(STDOUT,"FAIL:SECRET-FILE-NOT-EXISTS:" . $secretFile . "\n");
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
