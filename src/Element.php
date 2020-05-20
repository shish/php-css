<?php 

namespace TBela\CSS;

use Exception;
use InvalidArgumentException;
use JsonSerializable;
use ArrayAccess;
use stdClass;
use TBela\CSS\Query\Evaluator;
use function get_class;
use function is_callable;
use function is_null;
use function str_ireplace;

/**
 * Css node base class
 * @package TBela\CSS
 */
abstract class Element implements Query\QueryInterface, JsonSerializable, ArrayAccess, Rendererable   {

    use ArrayTrait;

    /**
     * @var stdClass|null
     * @ignore
     */
    protected $ast = null;
    /**
     * @var RuleList
     * @ignore
     */
    protected $parent = null;

    /**
     * Element constructor.
     * @param object|null $ast
     * @param RuleList|null $parent
     * @throws Exception
     */
    public function __construct($ast = null, RuleList $parent = null) {

        if (is_null($ast)) {

            $ast = new stdClass;
            $ast->type = str_ireplace(Element::class.'\\', '', get_class($this));
        }

        $this->ast = $ast;

        if (!is_null($parent)) {

            $parent->append($this);
        }

        if (is_callable([$this, 'createElements'])) {

            $this->createElements();
        }
    }

    /**
     * create an instance from ast or another Element instance
     * @param Element|object $ast
     * @param bool $remove_duplicates
     * @return mixed
     */
	public static function getInstance($ast, $remove_duplicates = false) : Element {

        $type = '';

        if ($remove_duplicates) {

            $ast = (new Parser())->deduplicate($ast);
        }

        if ($ast instanceof Element) {

            $ast = clone $ast->ast;
        }

        if (isset($ast->type)) {

            $type = $ast->type;
            unset($ast->parsingErrors);
        }

        if ($type === '') {

            throw new InvalidArgumentException('Invalid ast provided');
        }

        $className = Element::class.'\\'.ucfirst($ast->type);
        
		return new $className($ast);
    }

    /**
     * @param string $query
     * @return array
     * @throws Parser\SyntaxError
     */
    public function query(string $query): array {

	    return (new Evaluator())->evaluate($query, $this);
    }
    /**
     * return the root element
     * @return Element
     */
    public function getRoot () {

        $element = $this;

        while ($parent = $element->parent) {

            $element = $parent;
        }

        return $element;
    }

    /**
     * return Value\Set|string
     * @return Value\Set|string
     */
    public function getValue () {

        if (isset($this->ast->value)) {

            return $this->ast->value;
        }

        return '';
    }

    /**
     * assign the value
     * @param Value\Set|string $value
     * @return $this
     */
    public function setValue ($value) {

        $this->ast->value = $value;
        return $this;
    }

    /**
     * get the parent node
     * @return RuleList|null
     */
    public function getParent () {

        return $this->parent;
    }

    /**
     * return the type
     * @return string
     */
    public function getType() {

        return $this->ast->type;
    }

    /**
     * Clone parents, children and the element itself. Useful when you want to render this element only and its parents.
     * @return Element
     */
    public function copy() {

        $parent = $this;
        $node = clone $this;

        while ($parent = $parent->parent) {

            $ast = clone $parent->ast;

            if (isset($ast->elements)) {

                $ast->elements = [];
            }

            $parentNode = Element::getInstance($ast);
            $parentNode->append($node);
            $node = $parentNode;
        }

        return $node;
    }

    /**
     * @return stdClass
     * @ignore
     */
    public function jsonSerialize () {

        if (isset($this->ast->elements) && empty($this->ast->elements)) {

            $ast = clone $this->ast;

            unset ($ast->elements);
            return $ast;
        }

        return $this->ast;
    }

    /**
     * convert to string
     * @return string
     * @throws Exception
     */
    public function __toString()
    {
        try {

            return (new Renderer(['remove_empty_nodes' => false]))->render($this, null, true);
        }

        catch (Exception $ex) {

            error_log($ex->getTraceAsString());
        }

        return '';
    }

    /**
     * clone object
     * @ignore
     */
    public function __clone()
    {
        $this->ast = clone $this->ast;
        $this->parent = null;

        if (isset($this->ast->elements)) {

            foreach ($this->ast->elements as $key => $value) {

                $this->ast->elements[$key] = clone $value;
                $this->ast->elements[$key]->parent = $this;
            }
        }
    }
}