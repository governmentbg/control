<?php

declare(strict_types=1);

namespace modules\administration\permissions;

use vakata\user\UserManagementInterface;
use ReflectionMethod;
use vakata\config\Config;
use vakata\di\DIInterface;

class PermissionsService
{
    protected DIInterface $di;
    protected UserManagementInterface $usrm;
    protected string $admins;
    protected static array $classes = [];

    public static function module(string $class): void
    {
        self::$classes[] = $class;
    }

    public function __construct(DIInterface $di, UserManagementInterface $usrm, Config $config)
    {
        $this->di = $di;
        $this->usrm = $usrm;
        $this->admins = (string)$config->get('GROUP_ADMINS');
    }

    public function getAvailablePermissions(): array
    {
        $methods = [];
        foreach (self::$classes as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }
            $name = strtolower(str_replace('Controller', '', basename(str_replace('\\', '/', $class))));
            $methods[$name] = [ $name ];
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() && $method->name === 'permissions') {
                    $methods[$name] = array_merge(
                        $methods[$name],
                        $this->di->invoke($class, $method->name)
                    );
                    continue;
                }
                // if ($method->isStatic() || $method->isConstructor()) {
                //     continue;
                // }
                // $methods[$name][] = $name . '/' . $method->name;
            }
        }
        return $methods;
    }
    public function getStoredPermissions(): array
    {
        return $this->usrm->permissions();
    }
    public function setPermissions(array $permissions): void
    {
        foreach ($this->usrm->permissions() as $permission) {
            if (!in_array($permission, $permissions)) {
                $this->usrm->deletePermission($permission);
            }
        }
        $admins = $this->usrm->getGroup($this->admins);
        foreach ($permissions as $permission) {
            if (!$this->usrm->permissionExists($permission)) {
                $this->usrm->addPermission($permission);
            }
            if (!$admins->hasPermission($permission)) {
                $admins->addPermission($permission);
            }
        }
        $this->usrm->saveGroup($admins);
    }
}
