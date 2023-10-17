<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use alanrogers\tools\exceptions\GAException;
use DateTime;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use yii\base\Component;

class GoogleAnalytics extends Component
{
    private ?BetaAnalyticsDataClient $client = null;

    const ORDER_UNSPECIFIED = DimensionOrderBy\OrderType::ORDER_TYPE_UNSPECIFIED;
    const ORDER_ALPHANUMERIC = DimensionOrderBy\OrderType::ALPHANUMERIC;
    const ORDER_CASE_INSENSITIVE_ALPHANUMERIC = DimensionOrderBy\OrderType::CASE_INSENSITIVE_ALPHANUMERIC;
    const ORDER_NUMERIC = DimensionOrderBy\OrderType::NUMERIC;

    const ORDER_BY_TYPE_METRIC = 'metric';
    const ORDER_BY_TYPE_DIMENSION = 'dimension';

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
                'property' => 'properties/' . $ga_property,
                ...$args,
            ]);
        } catch (ApiException $e) {
            throw new GAException('Unable to run Analytics report.', (int) $e->getCode(), $e);
        }
    }

    /**
     * Cretaes a DateRange object
     * @param DateTime $start
     * @param DateTime $end
     * @return DateRange
     */
    public function dateRange(DateTime $start, DateTime $end) : DateRange
    {
        return new DateRange([
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d')
        ]);
    }

    /**
     * Creates a Dimension object
     * @param string $name
     * @param array $args
     * @return Dimension
     */
    public function dimension(string $name, array $args=[]) : Dimension
    {
        return new Dimension([
            'name' => $name,
            ...$args
        ]);
    }

    /**
     * Creates a Metric object
     * @param string $name
     * @param array $args
     * @return Metric
     */
    public function metric(string $name, array $args=[]) : Metric
    {
        return new Metric([
            'name' => $name,
            ...$args
        ]);
    }

    /**
     * @param DimensionOrderBy|MetricOrderBy $order_by
     * @param bool $desc
     * @param string $type
     * @return OrderBy
     */
    public function orderBy(DimensionOrderBy|MetricOrderBy $order_by, bool $desc=true, string $type=self::ORDER_BY_TYPE_METRIC) : OrderBy
    {
        $config = [
            'desc' => $desc
        ];
        if ($type === self::ORDER_BY_TYPE_METRIC) {
            $config['metric'] = $order_by;
        } else {
            $config['dimension'] = $order_by;
        }
        return new OrderBy($config);
    }

    /**
     * @param string $name
     * @param int $type One of the `DimensionOrderBy\OrderType` class constants
     * @return DimensionOrderBy
     */
    public function dimensionOrderBy(string $name, int $type=self::ORDER_UNSPECIFIED) : DimensionOrderBy
    {
        return new DimensionOrderBy([
            'dimension_name' => $name,
            'order_type' => $type
        ]);
    }

    /**
     * @param string $name
     * @return MetricOrderBy
     */
    public function metricOrderBy(string $name) : MetricOrderBy
    {
        return new MetricOrderBy([
            'metric_name' => $name
        ]);
    }
}