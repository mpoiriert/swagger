<?php namespace Draw\Swagger\Schema;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class SecurityRequirement
{
    private $data;

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Each name must correspond to a security scheme which is declared in the Security Definitions.
     * If the security scheme is of type "oauth2", then the value is a list of scope names required for the execution.
     * For other security scheme types, the array MUST be empty.
     *
     * @param $name
     * @param string[] $value
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name];
    }
} 