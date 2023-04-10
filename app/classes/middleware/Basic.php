<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\authentication\AuthenticationInterface;
use vakata\authentication\AuthenticationException;
use vakata\authentication\AuthenticationExceptionNotSupported;
use League\Plates\Engine as Views;
use vakata\user\UserException;
use vakata\user\UserManagementInterface;

class Basic
{
    protected AuthenticationInterface $auth;
    protected UserManagementInterface $usrm;
    protected string $path;
    protected Views $view;
    protected bool $force;

    public function __construct(
        AuthenticationInterface $auth,
        UserManagementInterface $usrm,
        Views $view,
        string $path,
        bool $force = false
    ) {
        $this->auth = $auth;
        $this->usrm = $usrm;
        $this->path = $path;
        $this->view = $view;
        $this->force = $force;
    }
    public function __invoke(Request $req, callable $next): Response
    {
        $url = $req->getUrl();
        $token = $req->getAttribute('token');

        // basic auth
        $has = strpos(strtolower(trim($req->getHeaderLine('Authorization'))), 'basic') === 0;
        if ($has) {
            try {
                $claims = $this->auth->authenticate($req->getAuthorization() ?? [])->toArray();
                $token->setClaims($claims);
                try {
                    $user = $this->usrm->getUserByProviderID($claims['provider'], $claims['id']);
                } catch (UserException $e) {
                    throw new AuthenticationException();
                }
                if (trim($url->getRealPath(), '/') === $this->path) {
                    if (
                        ($req->getMethod() === 'GET' || $req->getMethod() === 'HEAD') &&
                        $token->getClaim('impersonate')
                    ) {
                        $token->setClaim('impersonate', null);
                        return (new Response())
                            ->withHeader('X-Log', 'Logout')
                            ->withHeader('Location', $url->linkTo(''));
                    }
                    return (new Response(401))
                        ->setBody($this->view->render('common/login/basic'))
                        ->withHeader('WWW-Authenticate', 'Basic realm="' . $url->getHost() . '"');
                }
                return $next(
                    $req
                        ->withAttribute('token', $token)
                        ->withAttribute('user', $user)
                );
            } catch (AuthenticationExceptionNotSupported $ignore) {
            } catch (AuthenticationException $e) {
                return (new Response(401))->withHeader('WWW-Authenticate', 'Basic realm="' . $url->getHost() . '"');
            } catch (\Exception $ignore) {
            }
        }

        if ($this->force) {
            return (new Response(401))->withHeader('WWW-Authenticate', 'Basic realm="' . $url->getHost() . '"');
        }

        return $next($req);
    }
}
