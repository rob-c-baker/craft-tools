<?php

namespace alanrogers\tools\services;

use DateTime;
use Google\Client;
use Google\Service\Analytics;
use Google\Service\Analytics\GaData;
use yii\base\Component;

class GoogleAnalytics extends Component
{
    /**
     * @var Client|null
     */
    private ?Client $google_client = null;

    /**
     * @var Analytics|null
     */
    private ?Analytics $analytics_service = null;

    private function initGoogleClient() : void
    {
        $this->google_client = new Client();

        // Tell the Google client to use the credentials in the environment variable "GOOGLE_APPLICATION_CREDENTIALS"
        $this->google_client->useApplicationDefaultCredentials();
    }

    private function initGoogleAnalyticsService() : void
    {
        // We are using Analytics (read only) here...
        $this->getGoogleClient()->addScope(Analytics::ANALYTICS_READONLY);
        $this->analytics_service = new Analytics($this->google_client);
    }

    /**
     * @return Client
     */
    public function getGoogleClient() : Client
    {
        if ($this->google_client === null) {
            $this->initGoogleClient();
        }
        return $this->google_client;
    }

    /**
     * @return Analytics
     */
    public function getGoogleAnalyticsService() : Analytics
    {
        if ($this->analytics_service === null) {
            $this->initGoogleAnalyticsService();
        }
        return $this->analytics_service;
    }

    /**
     * @param string $report_id
     * @param DateTime $start_date
     * @param DateTime $end_date
     * @param string $metrics A comma-separated list of Analytics metrics. E.g. 'ga:sessions,ga:pageviews'.
     * @param array $params
     * @return GaData
     */
    public function getGAData(string $report_id, DateTime $start_date, DateTime $end_date, string $metrics, array $params=[]) : GaData
    {
        return $this->getGoogleAnalyticsService()->data_ga->get(
            $report_id,
            $start_date->format('Y-m-d'),
            $end_date->format('Y-m-d'),
            $metrics,
            $params
        );
    }
}