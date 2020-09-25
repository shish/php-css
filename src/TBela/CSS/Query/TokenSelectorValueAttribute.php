<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttribute extends TokenSelectorValue
{
    use FilterTrait;

    protected $value = [];

    /**
     * @var TokenSelectorValueInterface
     */
    protected  $expression;

    /**
     * TokenSelectorValueAttribute constructor.
     * @param object $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        parent::__construct($data);

        if (count($data->value) > 3) {

            $data->value = $this->trim($data->value);
        }

        if (count($data->value) == 3) {

            $this->expression = new TokenSelectorValueAttributeExpression($data->value);
        }

        else if (count($data->value) == 1) {

            if (!isset($data->value[0]->name) && $data->value[0]->type != 'index') {

                if ($data->value[0]->type != 'attribute_name') {

                    throw new \Exception(sprintf('Expected attribute_name but %s found "%s"', $data->value[0]->type, $data->value[0]->value), 400);
                }

                $this->expression = new TokenSelectorValueAttributeTest($data->value[0]->value);
            }

            else {

                $this->expression = call_user_func([TokenSelectorValueAttribute::class, 'getInstance'], $data->value[0]);
            }
        }

        else {

            throw new \Exception(sprintf('attribute not implemented %s', var_export($data, true)), 501);
        }
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
    {

        return $this->expression->evaluate($context);
    }

    /**
     * @param array $options
     * @return string
     */
    public function render(array $options = []) {

        return '['.$this->expression->render($options).']';
    }
}