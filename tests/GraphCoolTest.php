<?php


namespace Mrap\GraphCool\Tests;


use Mrap\GraphCool\GraphCool;

class GraphCoolTest extends TestCase
{
    public function testRun()
    {
        $this->expectOutputRegex('/Syntax Error: Unexpected \<EOF\>/i');
        GraphCool::run();
    }

}