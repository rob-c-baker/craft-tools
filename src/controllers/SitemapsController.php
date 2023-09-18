<?php declare(strict_types=1);

namespace alanrogers\tools\controllers;

use alanrogers\tools\helpers\CacheControlHelper;
use alanrogers\tools\services\sitemap\SitemapConfig;
use alanrogers\tools\services\sitemap\SitemapException;
use alanrogers\tools\services\sitemap\SitemapGenerator;
use alanrogers\tools\services\sitemap\SitemapIndexGenerator;
use alanrogers\tools\services\sitemap\SitemapType;
use Craft;
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
     * @throws NotFoundHttpException
     */
    public function actionList() : Response
    {
        if (!SitemapConfig::isEnabled()) {
            throw new NotFoundHttpException('Sitemaps disabled.');
        }

        $use_cache = SitemapConfig::isCacheEnabled();

        $service = new SitemapIndexGenerator(SitemapConfig::getAllConfigs(), $use_cache);

        $this->response->format = YiiResponse::FORMAT_RAW;
        $this->response->getHeaders()->set('Content-Type', 'application/xml; charset="UTF-8"');

        try {
            $this->response->data = $service->getXML();
        } catch (SitemapException $e) {
            throw new ServerErrorHttpException($e->getMessage(), $e->getCode(), $e);
        }

        if ($use_cache) {
            CacheControlHelper::setCacheHeaders(43200, $this->response); // 12 hours
        }

        return $this->response;
    }

    /**
     * Accessed via a URL like: sitemaps/[sectionHandle].xml
     * @param string $identifier The section slug
     * @return Response
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionXml(string $identifier) : Response
    {
        $sitemap_config = SitemapConfig::getConfig($identifier);
        if (!SitemapConfig::isEnabled() || !$sitemap_config) {
            throw new NotFoundHttpException();
        }

        if ($sitemap_config->type === SitemapType::SECTION) {
            // transform the `$section` from slug to camel case
            $sitemap_config->name = StringHelper::camelCase($sitemap_config->name);
        }

        $is_dev = Craft::$app->getConfig()->getGeneral()->devMode;

        $sitemap_config->use_cache = SitemapConfig::isCacheEnabled(); // only cache if enabled
        $sitemap_config->use_queue = !$is_dev; // only use queue in live

        $service = new SitemapGenerator($sitemap_config);
        try {
            $xml_model = $service->getXML();
        } catch (SitemapException $e) {
            // OK to throw as only happens in `devMode`.
            throw new ServerErrorHttpException($e->getMessage(), $e->getCode(), $e);
        }

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