<?php

declare(strict_types=1);

namespace helpers;

use middleware\ClientIP;
use vakata\authentication\Manager;
use vakata\database\DBInterface;

class AuthManager extends Manager
{
    protected array $all = [];

    final public function __construct(array $providers = [])
    {
        parent::__construct($providers);
    }

    public function getProviders(bool $all = false): array
    {
        return $all ? $this->all : parent::getProviders();
    }

    public static function fromDatabase(
        DBInterface $dbc,
        string $login = '',
        string $table = 'authentication',
        string $passwordKey = ''
    ): AuthManager {
        return static::fromArray(
            $dbc->all("SELECT * FROM {$table} WHERE disabled = 0 ORDER BY position, authentication"),
            $dbc,
            $login,
            $passwordKey
        );
    }
    public static function fromArray(
        array $providers,
        DBInterface $dbc = null,
        string $login = '',
        string $passwordKey = ''
    ): AuthManager {
        $auth = new static();
        foreach ($providers as $provider) {
            $skip = false;
            $inst = null;
            if (isset($provider['conditions']) && $provider['conditions']) {
                $conditions = json_decode($provider['conditions'], true) ?? [];
                if (isset($conditions['ip']) && is_array($conditions['ip']) && !ClientIP::check($conditions['ip'])) {
                    $skip = true;
                }
            }
            $settings = @json_decode($provider['settings'], true);
            if (!$settings) {
                $settings = [];
            }
            switch ($provider['authenticator']) {
                case 'Password':
                    if (isset($dbc)) {
                        $inst = new \vakata\authentication\password\PasswordDatabase(
                            $dbc,
                            'user_providers',
                            $settings,
                            [
                                'username' => 'id',
                                'password' => 'data'
                            ],
                            [
                                'provider' => 'PasswordDatabase'
                            ],
                            $passwordKey
                        );
                    }
                    break;
                case 'Certificate':
                    $inst = new \vakata\authentication\certificate\Certificate();
                    break;
                case 'CertificateAdvanced':
                    $inst = new \vakata\authentication\certificate\CertificateAdvanced($settings);
                    break;
                case 'LDAP':
                    $inst = new \vakata\authentication\ldap\LDAP(
                        $settings['host'],
                        $settings['base'] ?? null,
                        $settings['user'] ?? null,
                        $settings['pass'] ?? null,
                        array_map('trim', array_filter(explode(',', $settings['attr'] ?? '')))
                    );
                    break;
                case 'SMTP':
                    $inst = new \vakata\authentication\mail\SMTP($settings['host']);
                    break;
                case 'AzureAD':
                    $inst = new \vakata\authentication\oauth\AzureAD(
                        $settings['public'],
                        $settings['private'],
                        $login . '/azure',
                        $settings['permissions'] ?? null,
                        $settings['tenant'] ?? 'common',
                    );
                    break;
                case 'Facebook':
                    $inst = new \vakata\authentication\oauth\Facebook(
                        $settings['public'],
                        $settings['private'],
                        $login . '/facebook',
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'Github':
                    $inst = new \vakata\authentication\oauth\Github(
                        $settings['public'],
                        $settings['private'],
                        $login . '/github',
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'Google':
                    $inst = new \vakata\authentication\oauth\Google(
                        $settings['public'],
                        $settings['private'],
                        $login . '/google',
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'LinkedIn':
                    $inst = new \vakata\authentication\oauth\Linkedin(
                        $settings['public'],
                        $settings['private'],
                        $login . '/linkedin',
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'Microsoft':
                    $inst = new \vakata\authentication\oauth\Microsoft(
                        $settings['public'],
                        $settings['private'],
                        $login . '/microsoft',
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'StampIT':
                    $inst = new \vakata\authentication\oauth\StampIT(
                        $settings['public'],
                        $settings['private'],
                        $login . '/stampit',
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'EAuth':
                    $inst = new \vakata\authentication\saml\EAuth(
                        $settings['providerID'],
                        $settings['serviceID'],
                        $login . '/saml',
                        $settings['metaURL'],
                        $settings['privatePEM'],
                        $settings['certificatePEM'],
                        $settings['remotePEM']
                    );
                    break;
                default:
                    // unknown authenticator - continue
                    break;
            }
            if ($inst) {
                if (!$skip) {
                    $auth->addProvider($inst);
                }
                $auth->all[] = $inst;
            }
        }
        return $auth;
    }
}
