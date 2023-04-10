<?php

declare(strict_types=1);

namespace modules\common\dashboard;

use vakata\http\Response as Response;
use vakata\http\Uri as Url;
use League\Plates\Engine as Views;
use vakata\config\Config;
use vakata\database\DB;
use vakata\user\User;
use vakata\user\UserManagementInterface as UMI;

class Dashboard
{
    public static function permissions(): array
    {
        return [ 'dashboard/errors' ];
    }

    public function __construct(Views $views)
    {
        if (!$views->getFolders()->exists('dashboard')) {
            $views->addFolder('dashboard', __DIR__ . '/views');
        }
    }

    public function index(Response $res, Url $url, Views $views, User $user, UMI $usrm, Config $config, DB $db): Response
    {
        $errors = [];
        if ($user->hasPermission('dashboard/errors')) {
            if ($config->get('DEBUG')) {
                $errors[] = 'turn_off_debug';
            }
            if (strpos($url->getSegment('base'), '/admin/') !== false) {
                $errors[] = 'rename_admin_folder';
            }
            foreach (['admin', 'administrator', 'demo', 'test'] as $username) {
                try {
                    $usrm->getUserByProviderID('PasswordDatabase', $username);
                    $errors[] = 'remove_admin_user';
                    break;
                } catch (\Exception $ignore) {
                }
            }
        }
        $siks = $db->one(
            'SELECT COUNT(*) FROM siks WHERE election = ? AND video = 1',
            [ $user->site ]
        );

        return $res->setBody(
            $views->render('dashboard::index', [
                'errors' => $errors,
                'devices' => [
                    'count' => $db->one('SELECT COUNT(*) FROM devices WHERE registered IS NOT NULL'),
                    'total' => $db->one('SELECT COUNT(*) FROM devices')
                ],
                'sik_test' => [
                    'count' => $db->one(
                        "SELECT COUNT(DISTINCT(sik)) FROM streams where election = ? and mode = 2 and ended is not null and created > '2023-04-01 00:00:00'",
                        [ $user->site ]
                    ),
                    'total' => $siks
                ],
                'sik_real' => [
                    'count' => $db->one(
                        'SELECT COUNT(DISTINCT(sik)) FROM streams WHERE election = ? AND mode = 3',
                        [ $user->site ]
                    ),
                    'total' => $siks
                ],
                'sik_live' => [
                    'count' => $db->one(
                        'SELECT COUNT(DISTINCT(sik)) FROM streams WHERE election = ? AND mode = 3 AND started is not null and ended IS NULL',
                        [ $user->site ]
                    ),
                    'total' => $siks
                ],
                'servers' => $db->all('SELECT inner_host, host, monitor, enabled FROM servers ORDER BY server'),
                'restreamers' => $db->all('SELECT inner_host, host, monitor, enabled FROM restreamers ORDER BY restreamer')
            ])
        );
    }
}
