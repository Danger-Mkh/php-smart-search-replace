<?php

namespace Imanghafoori\SearchReplace\Tests;

use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\SearchReplace\TokenCompare;
use Imanghafoori\SearchReplace\PatternParser;

class RefactorPatternParsingTest extends BaseTestClass
{
    /** @test */
    public function any_keyword2()
    {
        $patterns = [
            'name' => [
                'search' => '["<any>""<white_space>?"]',
                'replace' => '["<1>""<2>","<1>"]',
                'predicate' => function ($matches) {
                    return $matches['values'][0][0] === T_CONSTANT_ENCAPSED_STRING;
                }
            ]
        ];

        $startFile = '<?php [1 ]; ["s" ]; ["d"];';
        $resultFile = '<?php [1 ]; ["s" ,"s"]; ["d","d"];';
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));
        $this->assertEquals($resultFile, $newVersion);
    }

    /** @test */
    public function any_keyword3()
    {
        $patterns = [
            'name' => [
                'search' => '"<any>""<white_space>?"]',
                'replace' => '"<1>""<2>","<1>"]',
                'predicate' => function ($matches) {
                    return $matches['values'][0][0] === T_CONSTANT_ENCAPSED_STRING;
                }
            ]
        ];

        $startFile = '<?php [1 ]; ["s" ]; ["d"];';
        $resultFile = '<?php [1 ]; ["s" ,"s"]; ["d","d"];';
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));
        $this->assertEquals($resultFile, $newVersion);
    }

    /** @test */
    public function comment_numbering_is_not_important()
    {
        $patterns = [
            'name' => [
                'search' => '["<10:any>""<8:white_space>?"]',
                'replace' => '["<1>""<2>","<1>"]',
                'predicate' => function ($matches) {
                    return $matches['values'][0][0] === T_CONSTANT_ENCAPSED_STRING;
                }
            ]
        ];

        $startFile = '<?php [1 ]; ["s" ]; ["d"];';
        $resultFile = '<?php [1 ]; ["s" ,"s"]; ["d","d"];';
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));
        $this->assertEquals($resultFile, $newVersion);
    }

    /** @test */
    public function match_comment()
    {
        $patterns = [
           'name' => [
               'search' => '"<comment>"]',
                'replace' => ']',
            ]
        ];

        $startFile = '<?php [/**/]; [/**/ ]; [ /**/ ]; [1,]; ["s"];';
        $resultFile = '<?php []; []; [ ]; [1,]; ["s"];';
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));
        $this->assertEquals($resultFile, $newVersion);
    }

    /** @test */
    public function match_optional_comment()
    {
        $patterns = [
           'name' => [
               'search' => '"<comment>?""<white_space>?"]',
                'replace' => ']',
            ]
        ];

        $startFile = '<?php [/**/]; [/**/ ]; ["s"];';
        $resultFile = '<?php []; []; ["s"];';
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));
        $this->assertEquals($resultFile, $newVersion);
    }

    /** @test */
    public function capturing_place_holders()
    {
        $patterns = [
            "name" => [
                'search' => "if (!'<variable>' && '<boolean>') { return response()->'<name>'(['message' => __('<string>'),], '<number>'); }",
                'replace' => 'Foo::bar("<1>", "<2>", "<3>"(), "<4>");'
            ],
            'name2' => [
                'search' => 'foo(false, true, null);',
                'replace' => 'bar("hi");'
            ],
        ];
        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');
        $resultFile = file_get_contents(__DIR__.'/stubs/ResultSimplePostController.stub');
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([15, 22, 25, 26], $replacedAt);
    }

    /** @test */
    public function basic_capturing_place_holders()
    {
        $patterns = [
            "name" => [
                'search' => "'<var>' = 1;",
                'replace' => "'<1>';"
            ],
        ];
        $startFile = '<?php $var = 1;';
        $resultFile = '<?php $var;';
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([1], $replacedAt);

        // with double-quotes
        $patterns = [
            'name' => [
                'search' => '"<var>" = 1;',
                'replace' => "'<1>';"
            ],
        ];
        $startFile = '<?php $var = 1;';
        $resultFile = '<?php $var;';
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([1], $replacedAt);

    }

    /** @test */
    public function filters()
    {
        $startFile = '<?php h::h();g::h();k::h();';
        $resultFile = '<?php k::h();';

        ////////////////////////////////////////////

        $patterns = [
            '"name' => [
                'search' => '"<name>"::h();',
                'replace' => "",
                'filters' => [
                    1 => [
                        'in_array' => ['h', 'g'],
                    ]
                ]
            ],
        ];
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);

        ////////////////////////////////////////////

        $patterns = [
            'name' => [
                'search' => '"<name>"::h();',
                'replace' => "",
                'filters' => [
                    1 => [
                        'in_array' => 'h,g',
                    ]
                ]
            ],
        ];

        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
    }

    /** @test */
    public function can_parse_patterns()
    {
        $patterns = require __DIR__.'/stubs/refactor_patterns.php';
        $sampleFileTokens = token_get_all(file_get_contents(__DIR__.'/stubs/SimplePostController.stub'));

        $patterns = PatternParser::parsePatterns($patterns);
        foreach ($patterns as $pIndex => $pattern) {
            $matches[$pIndex] = TokenCompare::getMatches($pattern['search'], $sampleFileTokens, $pattern['predicate'], $pattern['mutator']);
        }

        $this->assertEquals($matches[0][0]['values'],
            [
                [T_VARIABLE, '$user', 15],
                [T_STRING, 'true', 15],
                [T_STRING, 'json', 17],
                [T_CONSTANT_ENCAPSED_STRING, "'hi'", 17],
                [T_LNUMBER, 404, 17],
            ]
        );

        $start = $matches[0][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'if');

        $end = $matches[0][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], '}');

        $this->assertEquals($matches[0][1]['values'],
            [
                [T_VARIABLE, '$club', 22],
                [T_STRING, 'FALSE', 22],
                [T_STRING, 'json', 23],
                [T_CONSTANT_ENCAPSED_STRING, "'Hello'", 23],
                [T_LNUMBER, 403, 23],
            ]
        );

        $start = $matches[0][1]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'if');

        $end = $matches[0][1]['end'];
        $this->assertEquals($sampleFileTokens[$end], '}');

        $start = $matches[1][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'foo');

        $end = $matches[1][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], ';');

        $start = $matches[1][1]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'foo');

        $end = $matches[1][1]['end'];
        $this->assertEquals($sampleFileTokens[$end], ';');
    }

    /** @test */
    public function capturing_predicate()
    {
        $patterns = [
            "name" => [
                'search' => "'<var>' = '<var>';",
                'replace' => '',
                'predicate' => function ($matches) {
                    return $matches['values'][0][1] === $matches['values'][1][1];
                }
            ],
        ];
        $startFile = '<?php
$var = 0;
$var = $var;
$user = $var;';
        $resultFile = '<?php
$var = 0;

$user = $var;';
        [$newVersion, $replacedAt] = Searcher::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([3], $replacedAt);
    }
}
