<?php

namespace TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class OutlineWidth extends Unit
{
    use ValueTrait;

    protected static array $keywords = [
        'thin',
        'medium',
        'thick'
    ];

    /**
     * @inheritDoc
     */
    public static function matchToken($token, $previousToken = null, $previousValue = null, $nextToken = null, $nextValue = null): bool
    {

        return $token->type == 'unit' || ($token->type == 'number' && $token->value == 0);
    }

    public function getHash() {

        if (is_null($this->hash)) {

            $this->hash = $this->render(['compress' => true]);
        }

        return $this->hash;
    }
}
