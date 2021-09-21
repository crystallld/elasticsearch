<?php
namespace Elasticsearch\Eloquent;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
    protected $connection = 'elasticsearch';

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (empty($this->table)) {
            $this->table = Str::snake(class_basename($this));
        }

        return $this->table;
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function newQueryWithoutScopes()
    {
        return $this->newModelQuery();
    }
}