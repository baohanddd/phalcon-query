<?php
namespace App\Component;

use Phalcon\Filter;
use App\Filter\Split;

class Criteria
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

    public function conditions($rules = [], $keys = [])
    {
        /**
         * Reformat param vals
         */
        $data = $this->param->filter($rules)->toArray();

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