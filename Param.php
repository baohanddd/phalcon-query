<?php
namespace App\Component;

use App\Exception\Required;
use Phalcon\Filter;
use App\Filter\Split;
use App\Filter\Like;
use App\Filter\MongoId;
use App\Filter\Double;
use App\Filter\Json;

class Param
{
    /**
     * @var \Phalcon\Di
     */
    protected $_di;

    public $filter;

    /**
     * @var \App\Interfaces\Token
     */
    public $token;

    /**
     * @var \Phalcon\Http\Request
     */
    public $request;

    public function __construct(\Phalcon\Di $di)
    {
        $this->_di = $di;
        $this->filter  = $di->get('filter');
        $this->token   = $di->get('token');
        $this->request = $di->get('request');
    }

    /**
     * @param array $rules
     * @return \App\Component\Collection
     * @throws Required
     */
    public function filter(array $rules = [])
    {
        $data = $this->getData();

        $this->trim($data, $rules);

        foreach($rules as $key => $formats) {
            $this->token   ($data, $key, $formats);
            $this->required($data, $key, $formats);
            if(!isset($data[$key])) continue;
            $data[$key] = $this->filter->sanitize($data[$key], $formats);
        }
        return new \App\Component\Collection($data);
    }

    /**
     * @param array $data
     * @param array $keys
     */
    public function fields(array &$data = [], array $keys = [])
    {
        foreach($keys as $old => $new) {
            if(!isset($data[$old])) continue;
            $val = $data[$old];
            unset($data[$old]);
            $data[$new] = $val;
        }
    }

    /**
     * @param array $data
     */
    public function geo(array &$data = [])
    {
        if(isset($data['latitude']) && isset($data['longitude'])) {
            $data['location'] = [
                '$near' => [
                    $data['latitude'],
                    $data['longitude']
                ]
            ];
            unset($data['latitude']);
            unset($data['longitude']);
        }
    }

    /**
     * @param array $data
     * @param array $rules
     * @return void
     */
    private function trim(array &$data, array $rules)
    {
        foreach($data as $key => $val)
            if(!isset($rules[$key]))
                unset($data[$key]);
    }

    /**
     * @param array $data
     * @param $key
     * @param array $formats
     */
    private function token(array &$data, $key, array $formats)
    {
        if(in_array('token', $formats)) {
            $data[$key] = $this->token->getId();
        }
    }

    /**
     * @param array $data
     * @param $key
     * @param array $formats
     * @throws Required
     */
    private function required(array &$data, $key, array &$formats)
    {
        $pos = array_search('required', $formats);
        if(!isset($data[$key])) {
            if($pos !== FALSE) throw new Required($this, $key);
        }
        unset($formats[$pos]);
    }

    /**
     * @return mixed
     */
    private function getData()
    {
        if($this->request->isPut()) {
            $data = $this->request->getPut();
            return $data;
        } else {
            $data = $this->request->get();
            return $data;
        }
    }
}