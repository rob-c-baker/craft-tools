<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use alanrogers\tools\exceptions\GAException;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use yii\base\Component;

class GoogleAnalytics extends Component
{
    private ?BetaAnalyticsDataClient $client = null;

    /**
     * @throws GAException
     */
    public function getGoogleAnalyticsService(): BetaAnalyticsDataClient
    {
        if ($this->client === null) {
            $credentials_path = $_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] ?? null;
            try {
                $this->client = new BetaAnalyticsDataClient([
                    'credentials' => json_decode(file_get_contents($credentials_path), true)
                ]);
            } catch (ValidationException $e) {
                throw new GAException('Unable to create Analytics data client.', (int) $e->getCode(), $e);
            }
        }
        return $this->client;
    }

    /**
     * @param string $ga_property
     * @param array $args
     * @return RunReportResponse
     * @throws GAException
     * @see BetaAnalyticsDataGapicClient::runReport()
     */
    public function runReport(string $ga_property, array $args=[]): RunReportResponse
    {
        try {
            return $this->getGoogleAnalyticsService()->runReport([
                ...$args,
                'property' => 'properties/' . $ga_property
            ]);
        } catch (ApiException $e) {
            throw new GAException('Unable to run Analytics report.', (int) $e->getCode(), $e);
        }
    }
}