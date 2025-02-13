<?php

namespace TBela\CSS;

/**
 * Utility class that enable array like access to getter / setter and some properties.
 *
 * - Getter syntax: $value = $element['value']; // $value = $element->getValue()
 * - Setter syntax: $element['value'] = $value; // $element->setValue($value);
 * - Properties: $element['childNodes'], $element['firstChild'], $element['lastChild'], $element['parentNode']
 * @package TBela\CSS
 */
trait ArrayTrait
{

    public function __get($name) {

        if (is_callable([$this, "get$name"])) {

            return $this->{"get$name"}();
        }
    }

    public function __set($name, $value) {

        if (is_callable([$this, "set$name"])) {

            return $this->{"set$name"}($value);
        }
    }

    public function __isset($name) {

        return $this->offsetExists($name);
    }

    public function __unset($name) {

        return $this->offsetUnset($name);
    }

    /**
     * @param string $offset
     * @param string $value
     * @ignore
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {

        if (is_callable([$this, 'set' . $offset])) {

            call_user_func([$this, 'set' . $offset], $value);
        }
    }

    /**
     * @param string $offset
     * @return bool
     * @ignore
     */
    public function offsetExists(mixed $offset): bool
    {
        return is_callable([$this, 'get' . $offset]) ||
            is_callable([$this, 'set' . $offset]) ||
            (isset($this->ast->children) && in_array($offset, ['childNodes', 'firstChild', 'lastChild', 'parentNode']));
    }

    /**
     * @param string $offset
     * @ignore
     */
    public function offsetUnset(mixed $offset): void
    {

        if (is_callable([$this, 'set' . $offset])) {

            call_user_func([$this, 'set' . $offset], null);
        }
    }

    /**
     * @param string $offset
     * @return mixed|null
     * @ignore
     */
    public function offsetGet(mixed $offset): mixed
    {

        if (is_callable([$this, 'get' . $offset])) {

            return call_user_func([$this, 'get' . $offset]);
        }

        if ($offset == 'parentNode') {

            return $this->parent;
        }

        if (isset($this->ast->children)) {

            switch ($offset) {

                case 'childNodes':

                    return $this->ast->children;

                case 'firstChild':

                    return $this->ast->children[0] ?? null;

                case 'lastChild':

                    return end($this->ast->children);
            }
        }

        return null;
    }
}
