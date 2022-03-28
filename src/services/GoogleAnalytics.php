<?php

namespace alanrogers\tools\services;

use DateTime;
use Google_Client;
use Google_Service_Analytics;
use Google_Service_Analytics_GaData;
use yii\base\Component;

class GoogleAnalytics extends Component
{
    /**
     * @var Google_Client|null
     */
    private ?Google_Client $google_client = null;

    /**
     * @var Google_Service_Analytics|null
     */
    private ?Google_Service_Analytics $analytics_service = null;

    private function initGoogleClient(): void
    {
        $this->google_client = new Google_Client();

        // Tell the Google client to use the credentials in the environment variable "GOOGLE_APPLICATION_CREDENTIALS"
        $this->google_client->useApplicationDefaultCredentials();
    }

    private function initGoogleAnalyticsService(): void
    {
        // We are using Analytics (read only) here...
        $this->getGoogleClient()->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
        $this->analytics_service = new Google_Service_Analytics($this->google_client);
    }

    /**
     * @return Google_Client
     */
    public function getGoogleClient(): Google_Client
    {
        if ($this->google_client === null) {
            $this->initGoogleClient();
        }
        return $this->google_client;
    }

    /**
     * @return Google_Service_Analytics
     */
    public function getGoogleAnalyticsService(): Google_Service_Analytics
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
     * @return Google_Service_Analytics_GaData
     */
    public function getGAData(string $report_id, DateTime $start_date, DateTime $end_date, string $metrics, array $params=[]) : Google_Service_Analytics_GaData
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