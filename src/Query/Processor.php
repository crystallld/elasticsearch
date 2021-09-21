<?php

namespace Elasticsearch\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor as BaseProcessor;

class Processor extends BaseProcessor
{
    /**
     * Process the results of a "select" query.
     * Default select fields: _id、_source
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $results
     * @return array
     */
    public function processSelect(Builder $query, $results)
    {
        if (empty($hits = $results['hits']['hits']?? [])) return $hits;

        $data = [];
        foreach ($hits as $hit) {
            $data[] = array_merge(['_id' => $hit['_id']], $hit['_source']?? []);
        }

        return $data;
    }
}
