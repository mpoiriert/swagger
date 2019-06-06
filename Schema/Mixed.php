<?php namespace Draw\Swagger\Schema;

class Mixed
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public static function convert($value, $valueIsArray = false)
    {
        if(is_null($value)) {
            return $value;
        }

        if($valueIsArray && is_array($value)) {
            foreach($value as $key => $data) {
                $value[$key] = static::convert($data);
            }
            return $value;
        }

        if($value instanceof Mixed) {
            return $value;
        }

        return new static($value);
    }
}