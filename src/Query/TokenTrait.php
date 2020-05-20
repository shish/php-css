<?php

namespace TBela\CSS\Query;

use Exception;
use InvalidArgumentException;

trait TokenTrait
{
    protected string $type = '';

    protected function __construct($data)
    {

        foreach ($data as $key => $value) {

            if (!property_exists($this, $key)) {

                throw new InvalidArgumentException(sprintf('unknown property %s', $key), 400);
            }

            $this->{$key} = $value;
        }
    }

    public function __get($name) {

        switch ($name) {

            case 'type':

                return $this->{$name};
        }

        return null;
    }

    public static function getInstance($data) {

        if (!isset($data->type)) {

            throw new InvalidArgumentException(sprintf('invalid token %s', var_export($data, true)), 400);
        }

        $className = static::class.preg_replace_callback('#(^|-)([a-zA-Z])#', function ($matches) {

                return strtoupper($matches[2]);
        }, $data->type);

        if (!class_exists($className)) {

            throw new Exception(sprintf('class not found "%s"', $className));
        }

        return new $className($data);
    }

    protected function unique(array $context) {

        $result = [];

        foreach ($context as $element) {

            $result[spl_object_id($element)] = $element;
        }

        return array_values($result);
    }
}