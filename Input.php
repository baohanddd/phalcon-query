<?php
namespace Phalcon\Query;

use Phalcon\Filter;
use App\Filter\Split;

/**
 * @example
 *  $in = new \Phalcon\Query\Input();
 *  $in->process($rules);
 *  $in->
 *
 * Class Input
 * @package Phalcon\Query
 */
class Input
{
    /**
     * @var Phalcon\Di
     */
    protected $_di;

    /**
     * @var \App\Component\Param
     */
    protected $param;

    /**
     * @var array
     */
    protected $sort = [];

    public function __construct(\Phalcon\Di $di)
    {
        $this->_di = $di;
        $this->param = $this->_di->get('param');
        $this->filter = $this->_di->get('filter');
        $this->request = $this->_di->get('request');
    }

    /**
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        throw new \Exception('No validate method: ' + $name);
    }

    /**
     * Format/Truncate/Refactor input variables
     *
     * @example
     *   $data  = $request->getQuery();
     *   $data  = $input->process($data, $rule);
     *   $query = $criteria->get($data, $sort);
     *   $items = $model->find($query);
     *
     * @param array $data
     * @param array $rules
     * @return array
     */
    public function process(array $data, array $rules)
    {
        // Check there is missing required key...
        $this->chkRequire($data, $rules);

        // Trim unnecessary keys...
        $data = $this->crop($data, $rules);

        // Filter and reformat data...
        $data = $this->filter($data, $rules);

        /**
         * Parse sort in query data
         */
        $this->parseSort($data);

        /**
         * Reformat param keys
         */
        $this->param->fields($data, $keys);
        /**
         * Reformat geo queries
         */
        $this->param->geo($data);

        return $data;
    }

    /**
     * @param array $data
     * @param array $headers
     * @param array $rules
     * @return array
     * @throws Required
     */
    protected function filter(array $data, array $headers, array $rules)
    {
        foreach($rules as $key => $formats) {
            if(!isset($data[$key])) continue;
            $val = $data[$key];
            foreach($formats as $action) {
                if($action === 'required') continue;
                $val = $this->$action($data, $key);
            }
            $data[$key] = $val;
        }
        return $data;
    }

    /**
     * Crop unnecessary key in $data by $rule
     *
     * @param array $data
     * @param array $rules
     * @return array
     */
    protected function crop(array $data, array $rules)
    {
        foreach($data as $key => $val)
            if(!isset($rules[$key]))
                unset($data[$key]);

        return $data;
    }

    /**
     * @param array $data
     * @param array $rules
     * @throws \Exception
     */
    protected function chkRequire(array &$data, array $rules)
    {
        foreach($rules as $key => $formats) {
            $pos = array_search('required', $formats);
            if(!isset($data[$key])) {
                if($pos !== FALSE) throw new \Exception('No found `'.$key.'`', 4000);
            }
            unset($formats[$pos]);
        }

    }

    public function queries($conditions, $sort)
    {
        return [
            'fields' => ['_id'],
            'conditions' => $conditions,
            'sort' => $this->sort?:$sort,
            'skip' => $this->getSkip(),
            'limit' => $this->getLimit()
        ];
    }

    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param $data
     * @thrown \RuntimeException
     */
    private function parseSort(&$data)
    {
        if(isset($data['sort'])) {
            list($field, $direction) = explode("_", $data['sort']);

            if($direction == 'ASC')       $direction =  1;
            else if($direction == 'DESC') $direction = -1;
            else throw new \RuntimeException('Fails to parse sort conditions...');

            $field = strtolower($field);

            $this->sort[$field] = $direction;

            unset($data['sort']);
        }
    }

    private function getPage()
    {
        $page = $this->request->getQuery('page');
        return $page ? intval($page) : 1;
    }

    private function getLimit()
    {
        $limit = $this->request->getQuery('limit');
        return $limit ? intval($limit) : 20;
    }

    private function getSkip()
    {
        return ($this->getPage() - 1) * $this->getLimit();
    }
}