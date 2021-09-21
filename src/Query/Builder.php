<?php
namespace Elasticsearch\Query;

use http\Exception\InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * The database collection.
     * @var \MongoDB\Collection
     */
    protected $index;

    /**
     * Set the table which the query is targeting.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
     * @param  string|null  $as
     * @return $this
     */
    public function from($table, $as = null)
    {
        if (!empty($prefix = $this->connection->getPrefix())) {
            $table = $prefix.$table;
        }

        $this->from = $table;

        return $this;
    }

    public function whereLike($column, $value, $boolean = 'and', $not = false)
    {
        $type = 'like';

        $value = '*'.$value.'*';

        $this->wheres[] = compact('type', 'column', 'value', 'like', 'not');

        $this->addBinding($value, 'where');

        return $this;
    }

    public function whereNotLike($column, $value, $boolean = 'and', $not = false)
    {
        return $this->whereLike($column, $value, $boolean, true);
    }

    public function wherePrefix($column, $value, $boolean = 'and', $not = false)
    {
        $type = 'rlike';

        $this->wheres[] = compact('type', 'column', 'value', 'like', 'not');

        $this->addBinding($value, 'where');

        return $this;
    }

    protected function prepareBindings()
    {
        $body = ['query' => ['bool' => []]];
        foreach ($this->wheres as $where) {
            $filter = $this->prepareWhereRaw($where);
            if (empty($filter)) continue;

            $boolean = $this->grammar->compileBoolean($where);
            $body['query']['bool'][$boolean][]= $filter;
        }

        $body['size'] = 10;

        return [
            'index' => $this->from,
            'body' => $body,
        ];
    }

    /**
     * @TODO 支持 full-text query
     * @param $where
     * @return array
     */
    protected function prepareWhereRaw($where)
    {
        return $this->grammar->compileTermLevel($where);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array|string  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        return collect($this->onceWithColumns(Arr::wrap($columns), function () {
            return $this->processor->processSelect($this, $this->search());
        }));
    }

    /**
     * Run the query as a "select" statement against the connection.
     * @TODO 返回到model层
     * @return array
     */
    public function search()
    {
        $params = $this->prepareBindings();

        return $this->connection->search($params);
    }
}