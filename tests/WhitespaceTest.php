<?php

namespace Imanghafoori\SearchReplace\Tests;

use Imanghafoori\SearchReplace\PatternParser;
use Imanghafoori\LaravelMicroscope\Tests\BaseTestClass;

class WhitespaceTest extends BaseTestClass
{
    /** @test */
    public function white_space()
    {
        $patterns = [
            "use App\Club;'<white_space>'use App\Events\MemberCommentedClubPost;" =>
                [
                    'replace' => "use App\Club; use App\Events\MemberCommentedClubPost;",
                ],

            "use Illuminate\Http\Request;'<white_space>'" =>
                [
                    'replace' => ''
                ],
        ];
        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');

        $resultFile = file_get_contents(__DIR__.'/stubs/EolSimplePostControllerResult.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([5, 8], $replacedAt);
    }

    /** @test */
    public function white_space_placeholder()
    {
        $patterns = [
            ")'<white_space>'{" => ['replace' => '){'],
        ];
        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');
        $resultFile = file_get_contents(__DIR__.'/stubs/NoWhiteSpaceSimplePostController.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([13, 15, 20], $replacedAt);
    }

    /** @test */
    public function optional_white_space_placeholder()
    {
        $patterns = [
            "response('<white_space>?')'<white_space>?'->json" => ['replace' => 'response()"<2>"->mson'],
        ];
        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');

        $resultFile = file_get_contents(__DIR__.'/stubs/OptionalWhiteSpaceSimplePostController.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([16, 23], $replacedAt);
    }

    /** @test */
    public function optional_comment_placeholder()
    {
        $patterns = [
            ";'<white_space>?''<comment>';" => ['replace' => ';"<1>""<2>"'],
        ];
        $startFile = '<?php ; /*H*/ ;';

        $resultFile = '<?php ; /*H*/';
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([1], $replacedAt);

        $patterns = [
            ";'<white_space>?''<comment>?';" => ['replace' => ';"<2>"'],
        ];
        $startFile = '<?php ; ;';

        $resultFile = '<?php ;';
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([1], $replacedAt);
    }

    /** @test */
    public function begins_with_optional_white_space()
    {
        $patterns = [
            "'<white_space>?'];'<white_space>?'" => ['replace' => ',"<1>"];"<2>"'],
        ];
        $startCode = '<?php [1,2,3]; ';

        $resultCode = '<?php [1,2,3,]; ';
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startCode));

        $this->assertEquals($resultCode, $newVersion);
        $this->assertEquals([1], $replacedAt);
    }

    /** @test */
    public function begins_with_optional_white_space3()
    {
        $patterns = [
            "'<white_space>?'];'<white_space>?'" => ['replace' => ',"<1>"];"<2>"'],
        ];
        $startCode = '<?php [1,2,3 ]; ';

        $resultCode = '<?php [1,2,3, ]; ';
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startCode));

        $this->assertEquals($resultCode, $newVersion);
        $this->assertEquals([1], $replacedAt);
    }

    /** @test */
    public function begins_with_optional_white_space_2()
    {
        $patterns = [
            "'<string>?''<white_space>?'];" => ['replace' => ' "<2>"];'],
        ];
        $startCode = '<?php [1,2,"3"   ]; ';

        $resultCode = '<?php [1,2,    ]; ';
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startCode));

        $this->assertEquals($resultCode, $newVersion);
        $this->assertEquals([1], $replacedAt);
    }
}
