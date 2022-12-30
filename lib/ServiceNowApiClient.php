<?php

namespace ohtarr;

use GuzzleHttp\Client as GuzzleClient;

class ServiceNowApiClient
{
    public $table;
    public $where;
    public $orderByString;
    public $pagination;
    public $page;
    public $fields;

    public function __construct($baseurl, $username, $password, $table = "incident")
    {
        $this->baseurl = $baseurl;
        $this->username = $username;
        $this->password = $password;
        $this->table = $table;
    }

    public static function guzzle(array $guzzleparams)
    {
        $options = [];
        $params = [];
        $verb = 'get';
        $url = '';
        if(isset($guzzleparams['options']))
        {
            $options = $guzzleparams['options'];
        }
        if(isset($guzzleparams['params']))
        {
            $params = $guzzleparams['params'];
        }
        if(isset($guzzleparams['verb']))
        {
            $verb = $guzzleparams['verb'];
        }
        if(isset($guzzleparams['url']))
        {
            $url = $guzzleparams['url'];
        }

        $client = new GuzzleClient($options);
        $apiRequest = $client->request($verb, $url, $params);
        $response = $apiRequest->getBody()->getContents();
        $array = json_decode($response,true);
        if(is_array($array))
        {
            return $array;
        } else {
            return $response;
        }
    }

    public function where($column, $operator = NULL, $value = NULL)
    {
                    $argscount = count(func_get_args());
                    if($argscount == 2)
                    {
                            $value = $operator;
                            $operator = "=";
                    }
                    $tmp = [
                            "column"        =>      $column,
                            "operator"      =>      $operator,
                            "value"         =>      $value,
                    ];
            $this->where[] = $tmp;
            return $this;
    }

    public function orderBy($column)
    {
            $this->orderByString = 'ORDERBY' . $column;
            return $this;
    }
   
    public function orderByDesc($column)
    {
            $this->orderByString = 'ORDERBYDESC' . $column;
            return $this;
    }

    public function paginate($perPage = null, $page = null)
    {
        if($perPage)
        {
            $this->pagination = $perPage;
        }
        if($page)
        {
            $this->page = $page;
        }
        return $this;
    }

    public function select($columns = [])
    {
            $this->fields = $columns;
            return $this;
    }

    public function generateQueryString()
    {
        $query = "";
        if($this->where)
        {
                $tmpsearch = [];
                foreach($this->where as $search)
                {
                        $tmpsearch[] = $search['column'] . $search['operator'] . $search['value'];
                }
                $query = implode("^", $tmpsearch);
        }
        if($this->orderByString)
        {
            if($this->where)
            {
                $query .= "^";
            }
            $query .= $this->orderByString;
        }
        if($query)
        {
            return "sysparm_query=" . $query;
        }
    }

    public function generateLimitString()
    {
        if($this->pagination)
        {
            return "sysparm_limit=" . $this->pagination;
        }
    }

    public function generateOffsetString()
    {
        if($this->page)
        {
            $offset = ($this->page * $this->pagination) - $this->pagination;
            return "sysparm_offset=" . $offset;
        }
    }

    public function generateFieldsString()
    {
        if($this->fields)
        {
            return "sysparm_fields=" . implode(',', $this->fields);
        }

    }

    public function generateQuery()
    {
        $query = [];
        if($this->generateQueryString())
        {
            $query[] = $this->generateQueryString();
        }
        if($this->generateLimitString())
        {
            $query[] = $this->generateLimitString();
        }
        if($this->generateOffsetString())
        {
            $query[] = $this->generateOffsetString();            
        }
        if($this->generateFieldsString())
        {
            $query[] = $this->generateFieldsString();
        }
        return implode('&',$query);
    }

    public function generateUrl()
    {
        $url = $this->baseurl . '/api/now/v1/table/' . $this->table;
        if($this->generateQuery())
        {
            $url .= "?" . $this->generateQuery(); 
        }
        return $url;
    }

    public function get()
    {
        $guzzleparams = [
            'verb'      =>  'get',
            'url'       =>  $this->generateUrl(),
            'options'   =>  [],
            'params'    =>  [
                'auth'  =>  [$this->username, $this->password],
                'headers'   =>  [],
            ]
        ];

        $response = $this->guzzle($guzzleparams);
        return $response['result'];
    }

    public function first()
    {
        $this->paginate(1,1);
        return $this->get()[0];
    }

}
