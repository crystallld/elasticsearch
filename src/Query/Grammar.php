<?php

namespace Elasticsearch\Query;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;

class Grammar extends BaseGrammar
{
    protected $operators = [
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!=',
        'like', 'not like', 'ilike', 'rlike',
        'between', 'not between', 'in', 'not in',
//        '&', '&=', '|', '|=', '^', '^=', '<<', '>>',
        'regexp', 'not regexp', 'regex', 'exists', 'text',
    ];

    public function compileOperator($where)
    {
        $operator = $where['operator']?? $where['type']?? '=';
        $operator = str_replace(['!', 'not '], '', $operator);

        $filter = null;
        if (in_array($operator, ['=', '<>', '!='])) {
            $filter = 'terms';
        }else if (in_array($operator, ['between', 'in'])) {
            $filter = 'range';
        }else if (in_array($operator, ['like', 'ilike'])) {
            $filter = 'query_string';
        }else if (in_array($operator, ['rlike'])) {
            $filter = 'prefix';
        }else if (in_array($operator, ['regexp', 'regex'])) {
            $filter = 'regexp';
        }

        return $filter;
    }

    public function compileTermLevel($where, $operator = null)
    {
        if (empty($field = $where['column']?? null)) return null;

        $operator = $this->compileOperator($where);
        $value = $where['value']?? $where['values']?? null;

        if (!in_array($operator, ['exists', 'type']) && empty($value)) return null;

        return $this->buildQueryItem($operator, $field, $value);
    }

    public function compileFullText($where)
    {
        $operator = $this->compileOperator($where);
        $filter = [];
        $field = $where['column']?? null;
        $value = $where['value']?? null;

        return $this->buildQueryItem($operator, $field, $value);
    }

    protected function buildQueryItem($operator, $field = null, $value = null)
    {
        switch ($operator) {
            case 'terms':
                $filter = [$field => Arr::wrap($value)];
                break;
            case 'fuzzy':
            case 'prefix':
                $filter = [
                    $field => [
                        'value' => $value
                    ]
                ];
                break;
            case 'range':
                $filter = [
                    $field => array_combine(['gte', 'lte'], $value)
                ];
                break;
            case 'regexp':
                $filter = [
                    $field => [
                        'value' => $value,
                    ]
                ];
                break;
            case 'exists':
                $filter = ['field' => $field];
                break;
            case 'type':
                $filter = ['value' => $field];
                break;
            case 'match_phrase':
                $filter = [
                    $field => [
                        'query' => $value,
                    ]
                ];
                break;
            case 'query_string':
                $filter = [
                    'query' => $value,
                ];
                if (!empty($field)) {
                    $filter['fields'] = Arr::wrap($field);
                }
                break;
        }

        if (empty($filter)) return null;

        return [$operator => $filter];
    }

    public function compileBoolean($where)
    {
        $boolean = $where['boolean']?? 'and';

        $operator = $where['operator']?? '=';
        if (!empty($where['not'])
            || Str::startsWith($operator, '!')
            || Str::startsWith($operator, 'not')
        ) {
            $boolean = 'not';
        }

        switch ($boolean) {
            case 'and':
                $bool = 'must';
                break;
            case 'or':
                $bool = 'should';
                break;
            case 'not':
                $bool = 'must_not';
                break;
            default:
                throw new InvalidArgumentException($boolean.' not support.');
        }

        return $bool;
    }
}
