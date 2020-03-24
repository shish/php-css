<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class FontWeight extends Value
{

    protected static $values = [
        'thin' => '100',
        'hairline' => '100',
        'extra light' => '200',
        'ultra light' => '200',
        'light' => '300',
        'normal' => '400',
        'regular' => '400',
        'medium' => '500',
        'semi bold' => '600',
        'demi bold' => '600',
        'bold' => '700',
        'extra bold' => '800',
        'ultra bold' => '800',
        'black' => '900',
        'heavy' => '900',
        'extra black' => '950',
        'ultra black' => '950'
    ];

    /**
     * convert this object to string
     * @param array $options
     * @return string
     */
    public function render(array $options = [])
    {

        if (!empty($options['compress'])) {

            $value = ucwords(strtolower(preg_replace('#(["\'])([^\1]+)\\1#', '$1', $this->data->value)));

            if (isset(static::$values[$value])) {

                return static::$values[$value];
            }
        }

        return $this->data->value;
    }

    public static function keywords () {

        return array_keys(static::$values);
    }
}
