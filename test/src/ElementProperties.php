<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use TBela\CSS\Element;
use TBela\CSS\Element\AtRule;
use TBela\CSS\Element\Stylesheet;
use TBela\CSS\Compiler;
use TBela\CSS\Parser;
use TBela\CSS\Renderer;

final class ElementProperties extends TestCase
{
    /**
     * @param $content
     * @param string $expected
     * @dataProvider elementPropertiesProvider
     */
    public function testElementProperties($content, $expected): void
    {
        $this->assertEquals(
            $expected,
            $content
        );
    }

    public function elementPropertiesProvider () {

//        $renderer = new Renderer();
        $parser = new Parser();

        $parser->setContent('body{
background-color: green;
color: #fff;
font-family: Arial, Helvetica, sans-serif;
}
div {

    background-color: yellow;
}
h1{
color: #fff;
font-size: 50px;
font-family: Arial, Helvetica, sans-serif;
font-weight: bold;
}');

        $element = $parser->parse();

        $data = [];

        $data[] = [(string) $element['childNodes'][1],
            'div {
 background-color: #ff0
}'];

        $data[] = [(string) $element['firstChild'],
            'body {
 background-color: green;
 color: #fff;
 font-family: Arial, Helvetica, sans-serif
}'];

        $data[] = [(string) $element['lastChild'],
            'h1 {
 color: #fff;
 font-size: 50px;
 font-family: Arial, Helvetica, sans-serif;
 font-weight: bold
}'];

        $data[] = [implode("\n", $element['childNodes']),
            'body {
 background-color: green;
 color: #fff;
 font-family: Arial, Helvetica, sans-serif
}
div {
 background-color: #ff0
}
h1 {
 color: #fff;
 font-size: 50px;
 font-family: Arial, Helvetica, sans-serif;
 font-weight: bold
}'];

        return $data;
    }
}
