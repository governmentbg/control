<?php

declare(strict_types=1);

namespace modules\administration\translation;

use vakata\http\Uri as Url;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use vakata\config\Config;
use vakata\session\Session as Session;

class TranslationController
{
    protected TranslationService $translations;

    public function __construct(Request $req, Config $config)
    {
        $this->translations = new TranslationService(
            $config->get('STORAGE_INTL') . DIRECTORY_SEPARATOR . basename($req->getAttribute('locale')) . '.json'
        );
    }
    public function getIndex(Request $req, Response $res, Views $views): Response
    {
        if (!$views->getFolders()->exists('translation')) {
            $views->addFolder('translation', __DIR__ . '/views');
        }
        return $res->setBody(
            $views->render('translation::index', [
                'all'  => $req->getQuery('all', '0', 'int'),
                'data' => $req->getQuery('all', '0', 'int') ?
                    $this->translations->getTranslations() :
                    $this->translations->getMissingTranslations()
            ])
        );
    }
    public function postIndex(Request $req, Response $res, Session $sess, Url $url): Response
    {
        $data = array_combine($req->getPost('keys'), $req->getPost('values'));
        try {
            $this->translations->addTranslations($data, $req->getQuery('all', '0', 'int') === 1);
            $sess->set('success', 'translation.success');
        } catch (\Exception $e) {
            $sess->set('success', 'translation.fail');
        }
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postMissing(Request $req, Response $res): Response
    {
        $add = array_combine($req->getPost('keys'), $req->getPost('values'));
        if (!$add) {
            $add = [];
        }
        $rem = [];
        foreach ($add as $k => $v) {
            if ($v === '') {
                $rem[] = $k;
                unset($add[$k]);
            }
        }
        try {
            if (count($rem)) {
                $this->translations->removeTranslations($rem);
            }
            if (count($add)) {
                $this->translations->addTranslations($add);
            }
            return $res->withStatus(200);
        } catch (\Exception $e) {
            return $res->withStatus(500);
        }
    }
}
