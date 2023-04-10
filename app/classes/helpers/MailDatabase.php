<?php

declare(strict_types=1);

namespace helpers;

use vakata\database\DBInterface;
use vakata\mail\driver\SenderInterface;
use vakata\mail\MailInterface;

class MailDatabase implements SenderInterface
{
    protected DBInterface $db;
    protected string $table;

    public function __construct(DBInterface $db, string $table = 'mails')
    {
        $this->db = $db;
        $this->table = $table;
    }
    public function send(MailInterface $message): array
    {
        $recp = array_filter(
            array_unique(
                array_merge(
                    $message->getTo(true),
                    $message->getCc(true),
                    $message->getBcc(true)
                )
            )
        );
        $this->db->table($this->table)->insert([
            'added' => date('Y-m-d H:i:s'),
            'recipient' => implode(', ', $recp),
            'subject' => $message->getSubject(),
            'content' => (string)$message
        ]);
        return [ 'good' => $recp, 'fail' => [] ];
    }
}
