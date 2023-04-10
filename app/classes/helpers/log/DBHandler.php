<?php

declare(strict_types=1);

namespace helpers\log;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use vakata\database\DBInterface;
use vakata\database\schema\TableQuery;

/** @psalm-suppress PropertyNotSetInConstructor */
class DBHandler extends AbstractProcessingHandler
{
    protected DBInterface $db;
    protected TableQuery $table;
    protected array $columns;

    public function __construct(
        DBInterface $db,
        string $table = 'syslog',
        int|string $level = Logger::DEBUG,
        bool $bubble = true
    ) {
        $this->db = $db;
        $this->table = $this->db->table($table);
        $this->columns = $this->table->getDefinition()->getColumns();
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $data = [];
        foreach ($this->columns as $column) {
            if (isset($record['context'][$column])) {
                $data[$column] = $record['context'][$column];
                unset($record['context'][$column]);
            }
        }
        $data['created'] = $record['datetime']->format('Y-m-d H:i:s');
        $data['lvl'] = $record['level_name'];
        $data['message'] = $record['message'];
        $data['context'] = json_encode(
            $record['context'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
        $this->table->insert($data);
    }
}
