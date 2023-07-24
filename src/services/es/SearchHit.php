<?php

namespace alanrogers\tools\services\es;


use stdClass;

class SearchHit
{
    /**
     * The index the hit came from
     * @var string
     */
    public string $index;

    /**
     * The type of hit (usually "_doc")
     * @var string
     */
    public string $type;

    /**
     * The identifier for the hit
     * @var string
     */
    public string $id;

    /**
     * The score for the hit
     * @var float
     */
    public float $score;

    /**
     * The data for the fields requested in the query.
     * @var stdClass
     */
    public stdClass $source;

    /**
     * An explanation (if enabled) as to why a particular query matched
     * @var stdClass|null
     */
    public ?stdClass $explanation = null;

    /**
     * @param stdClass $hit_obj
     * @return SearchHit
     */
    public static function fromObject(stdClass $hit_obj) : SearchHit
    {
        $hit = new SearchHit();

        if (isset($hit_obj->_index)) {
            $hit->index = $hit_obj->_index;
        }

        if (isset($hit_obj->_type)) {
            $hit->type = $hit_obj->_type;
        }

        if (isset($hit_obj->_id)) {
            $hit->id = $hit_obj->_id;
        }

        if (isset($hit_obj->_score)) {
            $hit->score = $hit_obj->_score;
        }

        if (isset($hit_obj->_source)) {
            $hit->source = $hit_obj->_source;
        }

        if (isset($hit_obj->_explanation)) {
            $hit->explanation = $hit_obj->_explanation;
        }

        return $hit;
    }

    /**
     * @param array $hits_array
     * @return SearchHit[]
     */
    public static function fromHitsArray(array $hits_array) : array
    {
        $hits = [];
        foreach ($hits_array as $hit) {
            $hits[] = self::fromObject($hit);
        }
        return $hits;
    }
}