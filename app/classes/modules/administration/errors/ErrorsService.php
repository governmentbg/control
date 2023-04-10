<?php

declare(strict_types=1);

namespace modules\administration\errors;

use DateTime;
use vakata\config\Config;
use vakata\user\User;

class ErrorsService
{
    protected User $user;
    protected string $storage;

    public function __construct(User $user, Config $config)
    {
        $this->user = $user;
        $this->storage = $config->get('STORAGE_LOG');
    }
    public function list(DateTime $date): array
    {
        $errors = [];
        $file = realpath($this->storage) . '/' . $date->format('Y') . '/' . $date->format('m.d') . '.log';
        if (is_file($file)) {
            $handle = fopen($file, 'r') ?: throw new \RuntimeException();
            while (($row = fgets($handle))) {
                $row = trim($row, "\r\n");
                preg_match('(\[(?P<date>[^\]]+)\] .*?\.(?P<level>[a-z]+): (?P<error>.*))i', $row, $data);
                $ekey = md5($data['error']);
                if (isset($errors[$ekey])) {
                    if (strtotime($data['date'])) {
                        $errors[$ekey]['time'] = strtotime($data['date']);
                    }
                    $errors[$ekey]['count'] ++;
                } else {
                    $temp = explode(' in ', str_replace('[]', '', $data['error']), 2);
                    $file = explode(' on line ', $temp[1] ?? '');
                    $line = $file[1] ?? '';
                    $file = $file[0] ?? '';
                    $text = $temp[0];
                    $dt = strtotime($data['date']);
                    if (!$dt) {
                        $dt = time();
                    }
                    $errors[$ekey] = [
                        'level' => strtolower($data['level']),
                        'count' => 1,
                        'time'  => $dt,
                        'text'  => $text,
                        'file'  => $file,
                        'line'  => $line,
                    ];
                    if ($errors[$ekey]['level'] === 'critical') {
                        $errors[$ekey]['level'] = 'error';
                    }
                }
            }
            fclose($handle);
        }
        uasort($errors, function (array $a, array $b) {
            return $b['time'] <=> $a['time'];
        });
        return $errors;
    }
}
