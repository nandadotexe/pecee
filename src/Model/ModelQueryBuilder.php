<?php
namespace Pecee\Model;

use Pixie\QueryBuilder\QueryBuilderHandler;

class ModelQueryBuilder {

    protected static $instance;

    /**
     * @var Model
     */
    protected $model;
    /**
     * @var QueryBuilderHandler
     */
    protected $query;

    public function __construct(Model $model) {
        $this->model = $model;
        $this->query = (new QueryBuilderHandler())->table($this->model->getTable());
    }

    protected function createInstance(\stdClass $item) {
        $class = get_class($this->model);
        $model = new $class();
        $model->setRows((array)$item);
        return $model;
    }

    public function limit($limit) {
        $this->query->limit($limit);
        return $this;
    }

    public function skip($skip) {
        $this->query->offset($skip);
        return $this;
    }

    public function take($amount) {
        return $this->limit($amount);
    }

    public function offset($offset) {
        return $this->skip($offset);
    }

    public function where($key, $operator = null, $value = null) {
        $this->query->where($key, $operator, $value);
        return $this;
    }

    public function get() {
        return $this->all();
    }

    public function all() {
        $collection = (array)$this->query->get();

        $class = get_class($this->model);
        $model = new $class();

        $models = array();

        if(count($collection)) {
            foreach($collection as $item) {
                $models[] = $this->createInstance($item);
            }
        }

        $model->setResults(array('rows' => $models, 'collection' => true));

        return $model;
    }

    public function find($id) {
        $item = $this->query->where($this->model->getPrimary(), '=', $id)->first();
        if($item !== null) {
            return $this->createInstance($item);
        }
        return null;
    }

    public function findOrfail($id) {
        $item = $this->find($id);
        if($item === null) {
            throw new ModelNotFoundException('Item was not found');
        }
        return $item;
    }

    public function first() {
        $item = $this->query->first();
        if($item !== null) {
            return $this->createInstance($item);
        }
        return null;
    }

    public function firstOrFail() {
        $item = $this->first();
        if($item === null) {
            throw new ModelNotFoundException('Item was not found');
        }
        return $item;
    }

    public function count() {
        return $this->query->count();
    }

    public function max($field) {
        $result = $this->query->select($this->query->raw('MAX('. $field .') AS max'))->get();
        return (int)$result[0]->max;
    }

    public function sum($field) {
        $result = $this->query->select($this->query->raw('SUM('. $field .') AS sum'))->get();
        return (int)$result[0]->sum;
    }

    protected function getValidData($data) {
        $out = array();
        foreach($data as $key => $value) {
            if(in_array($key, $this->model->getColumns())) {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public function update(array $data) {
        $data = $this->getValidData($data);

        if(!isset($data[$this->model->getPrimary()])) {
            throw new ModelException('Primary identifier not defined.');
        }

        if(count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $this->query->update($data);
        $this->model->setRows($data);
        return $this->model;
    }

    public function create(array $data) {
        $data = $this->getValidData($data);

        if(!isset($data[$this->model->getPrimary()])) {
            throw new ModelException('Primary identifier not defined.');
        }

        if(count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $id = $this->query->insert($data);

        if($id) {

            $this->model->setRows($data);
            $this->model->{$this->model->getPrimary()} = $id;
            return $this->model;
        }

        return false;
    }

    public function firstOrCreate(array $data) {
        $item = $this->first();
        if($item === null) {
            $item = $this->createInstance((object)$data);
            $item->save();
        }
        return $item;
    }

    public function firstOrNew(array $data) {
        $item = $this->first();
        if($item === null) {
            return $this->createInstance((object)$data);
        }
        return $item;
    }

    public function destroy($ids) {
        $this->query->whereIn('id', $ids)->delete();
        return $this;
    }

    /**
     * @return Model
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel(Model $model) {
        $this->model = $model;
    }

    /**
     * @return QueryBuilderHandler
     */
    public function getQuery() {
        return $this->query;
    }

}