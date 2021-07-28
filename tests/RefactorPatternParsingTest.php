<?php

namespace Imanghafoori\SearchReplace\Tests;

use Imanghafoori\SearchReplace\PatternParser;
use PHPUnit\Framework\TestCase;

class RefactorPatternParsingTest extends TestCase
{
    /** @test */
    public function capturing_place_holders()
    {
        $patterns = [
            "if (!'<variable>' && '<boolean>') { return response()->'<name>'(['message' => __('<string>')], '<number>'); }" => ['replace' => 'Foo::bar("<1>", "<2>", "<3>"(), "<4>");'],
            'foo(false, true, null);' => ['replace' => 'bar("hi");'],
        ];
        $startFile = file_get_contents(__DIR__.'/../stubs/SimplePostController.stub');
        $resultFile = file_get_contents(__DIR__.'/../stubs/ResultSimplePostController.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([15, 23, 26, 27], $replacedAt);
    }

    /** @test */
    public function can_parse_patterns()
    {
        $patterns = require __DIR__.'/../stubs/refactor_patterns.php';
        $sampleFileTokens = token_get_all(file_get_contents(__DIR__.'/../stubs/SimplePostController.stub'));

        $matches = PatternParser::search($patterns, $sampleFileTokens);

        $this->assertEquals($matches[0][0]['values'],
            [
                [T_VARIABLE, '$user', 15],
                [T_STRING, 'true', 15],
                [T_STRING, 'json', 18],
                [T_CONSTANT_ENCAPSED_STRING, "'hi'", 18],
                [T_LNUMBER, 404, 18],
            ]
        );

        $start = $matches[0][0][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'if');

        $end = $matches[0][0][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], '}');

        $this->assertEquals($matches[0][1]['values'],
            [
                [T_VARIABLE, '$club', 23],
                [T_STRING, 'FALSE', 23],
                [T_STRING, 'json', 24],
                [T_CONSTANT_ENCAPSED_STRING, "'Hello'", 24],
                [T_LNUMBER, 403, 24],
            ]
        );

        $start = $matches[0][1][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'if');

        $end = $matches[0][1][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], '}');

        $start = $matches[1][0][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'foo');

        $end = $matches[1][0][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], ';');

        $start = $matches[1][1][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'foo');

        $end = $matches[1][1][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], ';');
    }
}
