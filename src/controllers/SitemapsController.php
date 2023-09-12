<?php declare(strict_types=1);

namespace alanrogers\tools\controllers;

use alanrogers\tools\helpers\CacheControlHelper;
use alanrogers\tools\services\sitemap\SitemapConfig;
use alanrogers\tools\services\sitemap\SitemapException;
use alanrogers\tools\services\sitemap\SitemapGenerator;
use alanrogers\tools\services\sitemap\SitemapIndexGenerator;
use alanrogers\tools\services\sitemap\SitemapType;
use craft\helpers\StringHelper;
use craft\web\Controller;
use craft\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\Response as YiiResponse;
use yii\web\ServerErrorHttpException;

class SitemapsController extends Controller
{
    protected int|bool|array $allowAnonymous = [
        'xml',
        'list'
    ];

    /**
     * Produces XML for the initial list of campsites i.e. /sitemap.xml that contains the <sitemapindex> element
     * @return Response
     * @throws ServerErrorHttpException
     */
    public function actionList() : Response
    {
        $is_dev = $_SERVER['ENVIRONMENT'] === 'dev';

        $service = new SitemapIndexGenerator(
            SitemapConfig::getAllConfigs(),
            !$is_dev
        );

        $this->response->format = YiiResponse::FORMAT_RAW;
        $this->response->getHeaders()->set('Content-Type', 'application/xml; charset="UTF-8"');

        try {
            $this->response->data = $service->getXML();
        } catch (SitemapException $e) {
            throw new ServerErrorHttpException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$is_dev) {
            CacheControlHelper::setCacheHeaders(43200, $this->response); // 12 hours
        }

        return $this->response;
    }

    /**
     * Accessed via a URL like: sitemaps/[sectionHandle].xml
     * @param string $identifier The section slug
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionXml(string $identifier) : Response
    {
        $sitemap_config = SitemapConfig::getConfig($identifier);
        if (!$sitemap_config) {
            throw new NotFoundHttpException();
        }

        if ($sitemap_config->type === SitemapType::SECTION) {
            // transform the `$section` from slug to camel case
            $sitemap_config->name = StringHelper::camelCase($sitemap_config->name);
        }

        $is_dev = $_SERVER['ENVIRONMENT'] === 'dev';

        $sitemap_config->use_cache = !$is_dev; // only cache in live
        $sitemap_config->use_queue = !$is_dev; // only use queue in live

        $service = new SitemapGenerator($sitemap_config);
        $xml_model = $service->getXML();

        $this->response->format = YiiResponse::FORMAT_RAW;
        $this->response->getHeaders()->set('Content-Type', 'application/xml; charset="UTF-8"');

        if (!$xml_model->generated) {

            $this->response->setStatusCode(503);
            $this->response->getHeaders()->add('Retry-After', 600);

            if (!$is_dev) {
                // varnish should not cache this, nor should any other clients:
                CacheControlHelper::setNoCacheHeaders($this->response);
            }

        } elseif (!$is_dev) {
            // we have good content, keep it for a while
            CacheControlHelper::setCacheHeaders(43200, $this->response); // 12 hours
        }

        $this->response->data = $xml_model->xml;

        return $this->response;
    }
}