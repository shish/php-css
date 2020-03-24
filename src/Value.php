<?php

namespace TBela\CSS;

use InvalidArgumentException;
use stdClass;
use TBela\CSS\Value\FontWeight;
use TBela\CSS\Value\Set;

/**
 * CSS value base class
 * @package CSS
 */
class Value
{
    /**
     * var stdClass;
     * @ignore
     */
    protected $data = null;

    /**
     * @var array
     * @ignore
     */
    protected static $cache = [];

    /**
     * Value constructor.
     * @param stdClass $data
     */
    public function __construct($data)
    {

        $this->data = $data;
    }

    /**
     * Cleanup cache
     * @ignore
     */
    public function __destruct()
    {
        unset (static::$cache[spl_object_hash($this)]);
    }

    /**
     * get property
     * @param string $name
     * @return mixed|null
     * @ignore
     */
    public function __get($name)
    {
        if (isset($this->data->{$name})) {

            return $this->data->{$name};
        }

        if (is_callable([$this, 'get' . $name])) {

            return call_user_func([$this, 'get' . $name]);
        }

        return null;
    }

    /**
     * test if this object matches the specified type
     * @param string $type
     * @return bool
     */
    public function match($type)
    {

        return strtolower($this->data->type) == $type;
    }

    /**
     * test if $data matches this class
     * @param stdClass $data
     * @return bool
     */
    protected static function validate($data)
    {

        return isset($data->value);
    }

    /**
     * create an instance
     * @param stdClass $data
     * @return Value
     */
    public static function getInstance($data)
    {

        if ($data instanceof Value) {

            return $data;
        }

        if (!isset($data->type)) {

            throw new InvalidArgumentException('Type property is required: ' . gettype($data) . ':' . var_export($data, true), 400);
        }

        $className = static::class . '\\' . ucfirst($data->type);

        if (!class_exists($className)) {

            error_log(__METHOD__ . ' missing data type? ' . $className);
            $className = static::class;
        }

        if (!$className::validate($data)) {

            throw new InvalidArgumentException('Invalid argument: $className:' . $className . ' data:' . var_export($data, true), 400);
        }

        return new $className($data);
    }

    /**
     * convert this object to string
     * @param array $options
     * @return string
     */
    public function render(array $options = [])
    {

        if (!empty($options['compress']) && strlen($this->data->value) > 2) {

            return preg_replace('#^(["\'])([^"\'\s]+)\\1$#', '$2', $this->data->value);
        }

        return $this->data->value;
    }

    /**
     * parse a css value
     * @param Set|string $string
     * @param string $property
     * @param bool $capture_whitespace
     * @return Set
     */
    public static function parse($string, $property = null, $capture_whitespace = true)
    {
        // too lazy to reparse ...
        if (!is_string($string)) {

            return $string;
        }

        $string = trim($string);
        $property = (string) $property;

        $i = -1;
        $j = strlen($string) - 1;

        $buffer = '';
        $tokens = [];

        while (++$i <= $j) {

            if ($property == 'font' || $property == 'font-weight') {

                foreach (FontWeight::keywords() as $keyword) {

                    $word = $keyword;

                    if ($string[$i] == '"' || $string[$i] == "'") {

                        $word = $string[$i].$word.$string[$i];
                    }

                    $length = strlen($word);

                    if (strcasecmp(substr($string, $i, $length), $word) == 0) {

                        $token = new stdClass;
                        $token->type = 'fontWeight';
                        $token->value = $keyword;
                        $tokens[] = $token;

                        $i += $length;
                        $buffer = '';

                        if ($i >= $j) {

                            continue 2;
                        }

                        if ($string[$i] == ' ') {

                            $tokens[] = type(' ');
                            continue 2;
                        }
                    }
                }
            }

            switch ($string[$i]) {

                case ' ':
                case "\t":
                case "\n":
                case "\r":

                    if ($buffer !== '') {

                        $tokens[] = type($buffer);
                        $buffer = '';
                    }

                    if ($capture_whitespace) {

                        $k = $i;

                        while (++$k <= $j) {

                            if (preg_match('#\s#', $string[$k])) {

                                continue;
                            }

                            $buffer = '';

                            if (!is_separator($string[$k]) || ($j >= $k + 1 && !is_whitespace($string[$k + 1]))) {

                                $token = new stdClass;
                                $token->type = 'whitespace';
                                $tokens[] = $token;
                            }

                            $i = $k - 1;
                            break 2;
                        }
                    }

                    break;

                case '"':
                case "'":

                    $next = $i;

                    while (true) {

                        $next = strpos($string, $string[$i], $next + 1);

                        if ($next !== false) {

                            if ($string[$next - 1] != '\\') {

                                break;
                            }
                        } else {

                            break;
                        }
                    }

                    $token = new stdClass;

                    $token->type = 'cssString';
                    $token->value = substr($string, $i, $next === false ? $j + 1 : $next - $i + 1);

                    $tokens[] = $token;
                    $buffer = '';


                    if ($next === false) {

                        $i = $j;
                        continue 2;
                    }

                    $i = $next;

                    break;

                case '(':

                    if ($string[$i - 1] != '\\') {

                        $params = _close($string, ')', '(', $i, $j);

                        if ($params !== false) {

                            $token = new stdClass;

                            if (preg_match('#^(-([a-zA-Z]+)-(\S+))#i', $buffer, $matches)) {

                                $token->name = $matches[3];
                                $token->vendor = $matches[2];
                            } else {

                                $token->name = $buffer;
                            }

                            if (in_array(strtolower($token->name), ['rgb', 'rgba', 'hsl', 'hsla'])) {

                                $token->type = 'color';
                            } else {

                                $token->type = 'cssFunction';
                            }

                            $token->arguments = static::parse(substr($params, 1, -1), true);

                            $tokens[] = $token;

                            $buffer = '';
                            $i += strlen($params) - 1;
                        } else {

                            $tokens[] = type($buffer . $params);
                            $i = $j;
                        }

                        break;
                    }

                case ',':
                case '/':

                    if ($i < $j && $string[$i + 1] == '*' && $string[$i] == '/') {

                        //     if ($string[$i + 1] == '*') {

                        $params = match_comment($string, $i, $j);

                        if ($params !== false) {

                            $token = new stdClass;

                            $token->type = 'comment';
                            $token->value = $params;

                            $tokens[] = $token;

                            $i += strlen($params) - 1;
                            $buffer = '';
                            break;
                        }
                    }

                    if (
                        $string[$i] == ',' ||
                        ($i > 0 &&
                            $i < $j &&
                            preg_match('#\s#', $string[$i - 1]) &&
                            preg_match('#\s#', $string[$i + 1]))) {

                        if ($buffer !== '') {

                            $tokens[] = type($buffer);
                            $buffer = '';
                        }

                        $token = new stdClass;
                        $token->type = 'separator';
                        $token->value = $string[$i];
                        $tokens[] = $token;

                        $k = $i;

                        while (++$k <= $j) {

                            if (preg_match('#\s#', $string[$k])) {

                                continue;
                            }

                            $i = $k;
                            $buffer = $string[$k];
                            break 2;
                        }

                        break;
                    }

                default:

                    if ($string[$i] == '!') {

                        if ($buffer !== '') {

                            $tokens[] = type($buffer);
                            $buffer = '';
                        }
                    }

                    $buffer .= $string[$i];
            }
        }

        if ($buffer !== '') {

            $tokens[] = type($buffer);
        }

        return new Set(reduce($tokens));
    }

    /**
     * return the list of keywords
     * @return array
     * @ignore
     */
    public static function keywords () {

        return [];
    }

    public function __toString()
    {
        return $this->render();
    }
}

function is_separator($char)
{

    switch ($char) {

        case ',':
        case '/':
        case '+':
        case '-':

            return true;
    }

    return false;
}

function reduce($tokens)
{

    $count = count($tokens) - 1;

    if ($count > 1) {

        $j = $count;

        while ($j-- >= 1) {

            $token = $tokens[$j];

            if ($token->type == 'whitespace' && ($tokens[$j + 1]->type == 'separator' || ($tokens[$j + 1]->type == 'cssString' && $tokens[$j + 1]->value == '!important'))) {

                array_splice($tokens, $j, 1);
            } else if ($token->type == 'separator' && $tokens[$j + 1]->type == 'whitespace') {

                array_splice($tokens, $j + 1, 1);
            }
        }
    }

    return $tokens;
}

function match_comment($string, $start, $end)
{

    $i = $start + 1;

    while ($i++ < $end) {

        switch ($string[$i]) {

            case '*':

                if ($string[$i + 1] == '/') {

                    return substr($string, $start, $i + 2 - $start);
                }

                break;
        }
    }

    return false;
}

function _close($string, $search, $reset, $start, $end)
{

    $count = 1;
    $i = $start;

    while ($i++ <= $end) {

        switch ($string[$i]) {

            case $search:

                if ($string[$i - 1] != '\\') {

                    $count--;
                }

                break;

            case $reset:

                if ($string[$i - 1] != '\\') {

                    $count++;
                }

                break;

            // in string matching
            case '"':
            case "'":

                $match = _close($string, $string[$i], $string[$i], $i, $end);

                if ($match === false) {

                    return false;
                }

                $i += strlen($match) - 1;

                break;
        }

        if ($count == 0) {

            break;
        }
    }

    if ($count == 0) {

        return substr($string, $start, $i - $start + 1);
    }

    return false;
}

function is_whitespace($char)
{

    return preg_match("#^\s$#", $char);
}

function type($token)
{

    $type = new stdClass;

    $type->value = $token;

    if (substr($token, 0, 1) != '#' && is_numeric($token)) {

        $type->type = 'number';
    } else if ($token == 'currentcolor' || isset(Color::COLORS_NAMES[$token]) || preg_match('#^\#([a-f0-9]{8}|[a-f0-9]{6}|[a-f0-9]{4}|[a-f0-9]{3})$#i', $token)) {

        $type->type = 'color';
        $type->colorType = 'hex';
    } else if (preg_match('#^(((\+|-)?(?=\d*[.eE])([0-9]+\.?[0-9]*|\.[0-9]+)([eE](\+|-)?[0-9]+)?)|(\d+|(\d*\.\d+)))([a-zA-Z]+|%)$#', $token, $matches)) {

        $type->type = 'unit';
        $type->value = $matches[1];
        $type->unit = $matches[9];
    } else {

        $type->type = 'cssString';
    }

    return $type;
}
