<?php

namespace TBela\CSS;

trait ArrayTrait  {

    public function offsetSet($offset, $value) {

        if (is_callable([$this, 'set'.$offset])) {

            call_user_func([$this, 'set'.$offset], $value);
        }
    }

    public function offsetExists($offset) {
        return is_callable([$this, 'get'.$offset]) ||
                is_callable([$this, 'set'.$offset]) ||
            (isset($this->ast->elements) && in_array($offset, ['childNodes', 'firstChild', 'lastChild']));
    }

    public function offsetUnset($offset) {

        if (is_callable([$this, 'set'.$offset])) {

            call_user_func([$this, 'set'.$offset], null);
        }
}

    public function offsetGet($offset) {

        if(is_callable([$this, 'get'.$offset])) {

            return call_user_func([$this, 'get' . $offset]);
        }

        if (isset($this->ast->elements)) {

            switch ($offset) {

                case 'childNodes':

                    return $this->ast->elements;

                case 'firstChild':

                    return isset($this->ast->elements[0]) ? $this->ast->elements[0] : null;

                case 'lastChild':

                    $count = count($this->ast->elements);
                    return $count > 0 ? $this->ast->elements[$count - 1] : null;
            }
        }

        return null;
    }
}