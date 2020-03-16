<?php

namespace TBela\CSS\Property;

use ArrayAccess;
use TBela\CSS\ArrayTrait;
use TBela\CSS\Rendererable;
use TBela\CSS\Value\Set;

class Comment implements ArrayAccess, Rendererable {

    use ArrayTrait;

    protected $value;

    protected $type = 'Comment';

    /**
     * PropertyComment constructor.
     * @param $value
     */
    public function __construct($value)
    {

        $this->value = $value;
    }

    public function setValue($value) {

        if (is_string($value)) {

            $value = Value::parse($value);
        }

        else if (!is_array($value)) {

            $value = new Set([$value]);
        }

        $this->value = $value;
        return $this;
    }

    public function getValue() {

        return $this->value;
    }

    public function getType () {

        return $this->type;
    }

    /**
     * @param bool $compress
     * @param array $options
     * @return string
     */
    public function render ($compress = false, array $options = []) {

        if ($compress || !empty($options['remove_comments'])) {

            return '';
        }

        return $this->value;
    }

    public function __toString()
    {

        return $this->render();
    }
}