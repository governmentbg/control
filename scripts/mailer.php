#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use vakata\mail\driver\MailSender;
use vakata\mail\driver\SMTPSender;
use helpers\AppStatic as App;

$dbc = App::db();

$run = true;
pcntl_signal(SIGINT, function () use (&$run) {
    $run = false;
});

while ($run) {
    pcntl_signal_dispatch();
    /** @psalm-suppress TypeDoesNotContainType */
    if (!$run) {
        break;
    }
    /** @psalm-suppress RedundantCondition */
    while ($run && $dbc->one("SELECT 1 FROM mails WHERE started IS NOT NULL AND finished IS NULL")) {
        pcntl_signal_dispatch();
        usleep(1000000);
        $dbc->query(
            "UPDATE mails SET started = NULL WHERE started IS NOT NULL AND finished IS NULL AND started < ?",
            date('Y-m-d H:i:s', strtotime('-2 minutes'))
        );
    }
    /** @psalm-suppress TypeDoesNotContainType */
    if (!$run) {
        break;
    }
    /** @psalm-suppress RedundantCondition */
    do {
        pcntl_signal_dispatch();
        $task = $dbc->one("SELECT * FROM mails WHERE started IS NULL ORDER BY priority DESC, added ASC LIMIT 1");
        if ($task) {
            break;
        } else {
            usleep(1000000);
        }
    } while ($run);
    pcntl_signal_dispatch();
    /** @psalm-suppress TypeDoesNotContainType */
    if (!$run) {
        break;
    }
    if (!$task) {
        continue;
    }
    if (!filter_var($task['recipient'], FILTER_VALIDATE_EMAIL)) {
        $dbc->query(
            "UPDATE mails SET started = ?, finished = ?, result = 'ERR' WHERE mail = ?",
            [ date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $task['mail'] ]
        );
        continue;
    }

    $mailer = App::get('SMTPCONNECTION') ?
        new SMTPSender(App::get('SMTPCONNECTION')) :
        new MailSender();

    $dbc->query(
        "UPDATE mails SET started = ? WHERE mail = ?",
        [ date('Y-m-d H:i:s'), $task['mail'] ]
    );

    $mail = \vakata\mail\Mail::fromString($task['mail']);
    try {
        $mailer->send($mail);
        $dbc->query(
            "UPDATE mails SET finished = ? WHERE mail = ?",
            [ date('Y-m-d H:i:s'), $task['mail'] ]
        );
    } catch (\Exception $e) {
        $dbc->query(
            "UPDATE mails SET started = NULL WHERE mail = ?",
            [ $task['mail'] ]
        );
    }
}

echo 'DONE' . "\r\n";
exit(0);
