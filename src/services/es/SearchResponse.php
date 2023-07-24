<?php declare(strict_types=1);

namespace alanrogers\tools\services\es;

use Elastic\Elasticsearch\Response\Elasticsearch;
use stdClass;

class SearchResponse
{
    /**
     * @var bool
     */
    public bool $success = false;

    /**
     * An error if there was one set
     * @var string|null
     */
    public ?string $error = '';

    /**
     * Number of ms taken to execute query
     * @var int|null
     */
    public ?int $took = null;

    /**
     * Total number of results (count of hits may be less than this as pagination or limiting may be in effect)
     * @var int|null
     */
    public ?int $total = null;

    /**
     * Highest search rank score
     * @var float|null
     */
    public ?float $max_score = null;

    /**
     * Search hits as an array containing SearchHit instances
     * @var SearchHit[]
     */
    public array $hits = [];

    /**
     * @var stdClass|null
     */
    public ?stdClass $aggregations = null;

    /**
     * Gets an array of ids from the hits array
     * @return int[]
     */
    public function ids(): array
    {
        if ($this->hits) {
            return array_map('intval', array_column($this->hits, 'id'));
        }
        return [];
    }

    /**
     * @param Elasticsearch|null $response
     * @param string $error (Optional)
     * @return SearchResponse
     */
    public static function build(?Elasticsearch $response, string $error=''): SearchResponse
    {
        $r = new self();

        $r->error = $error;

        if ($response) {
            $resp = $response->asObject();

            // Initial check for success
            $r->success = (!isset($resp->timed_out) || !$resp->timed_out || isset($resp->hits));
            if (!$r->success) {
                return $r;
            }

            // Other vars
            if (isset($resp->hits)) {
                if (isset($resp->took)) {
                    $r->took = $resp->took;
                }
                if (isset($resp->hits->total)) {
                    $r->total = intval($resp->hits->total->value);
                }
                if (isset($resp->hits->max_score)) {
                    $r->max_score = floatval($resp->hits->max_score);
                }
                if (isset($resp->hits->hits)) {
                    $r->hits = SearchHit::fromHitsArray($resp->hits->hits);
                }
            }

            // aggregations
            if (isset($resp->aggregations)) {
                $r->aggregations = $resp->aggregations;
            }
        }

        return $r;
    }
}