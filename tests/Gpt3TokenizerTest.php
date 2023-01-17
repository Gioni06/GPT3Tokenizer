<?php
namespace Gioni06\Gpt3Tokenizer\Tests;

use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use PHPUnit\Framework\TestCase;

class Gpt3TokenizerTest extends TestCase {

    public function test_encodeStr_function(): void
    {
        $this->assertEquals([ '32', '119', '111', '114', '108', '100' ], Gpt3Tokenizer::encodeStr(" world"));
        $this->assertEquals([ '32', '240', '159', '140', '141' ], Gpt3Tokenizer::encodeStr(" 🌍"));
    }


    public function test_decodeStr_function(): void
    {
        $this->assertEquals(" world", Gpt3Tokenizer::decodeStr([ '32', '119', '111', '114', '108', '100' ]));
        $this->assertEquals(" 🌍", Gpt3Tokenizer::decodeStr([ '32', '240', '159', '140', '141' ]));
    }

    public function test_bpe_merges()
    {
        $lines = file(__DIR__ . '/__fixtures__/merges_example.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertEquals(array(
            ['Ġ', 't'],
            ['Ġ','a'],
            ['h', 'e'],
            ['i', 'n'],
            ['r', 'e'],
            ['o', 'n'],
            ['Ġt', 'he']
        ), Gpt3Tokenizer::bpeMerges($lines));
    }

    public function test_get_pairs_function()
    {
        $this->assertEquals(array(
            [ 'Ġ', 'w' ],
            [ 'w', 'o' ],
            [ 'o', 'r' ],
            [ 'r', 'l' ],
            [ 'l', 'd' ]
        ), Gpt3Tokenizer::get_pairs([ 'Ġ', 'w', 'o', 'r', 'l', 'd' ]));

        $this->assertEquals(array(
            [ 'ĠðŁĳ', 'ĭ' ]
        ), Gpt3Tokenizer::get_pairs([ 'ĠðŁĳ', 'ĭ' ]));

        $this->assertEquals(array(
            [ 'he', 'l' ], [ 'l', 'l' ], [ 'l', 'o' ]
        ), Gpt3Tokenizer::get_pairs([ 'he', 'l', 'l', 'o' ]));
    }

    public function test_dictZip_function()
    {
        $lines = file(__DIR__ . '/__fixtures__/merges_example.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $bpeMerges = Gpt3Tokenizer::bpeMerges($lines);
        $this->assertEquals(array(
            'Ġ,t' => 0,
            'Ġ,a' => 1,
            'h,e' => 2,
            'i,n' => 3,
            'r,e' => 4,
            'o,n' => 5,
            'Ġt,he' => 6
        ), Gpt3Tokenizer::dictZip(Gpt3Tokenizer::zipBpe($bpeMerges), range(0, count($bpeMerges) - 1)));
    }

    public function test_split_string()
    {
        $this->assertEquals([
            'Ġ', 'h', 'a', 's',
            'O', 'w', 'n', 'P',
            'r', 'o', 'p', 'e',
            'r', 't', 'y'
        ], Gpt3Tokenizer::splitString("ĠhasOwnProperty"));
    }

    public function test_bpe_function()
    {
        $this->assertEquals("Ġhas Own Property", Gpt3Tokenizer::bpe("ĠhasOwnProperty"));
    }

    public function test_bytes_to_unicode_function()
    {
        $this->assertEquals("Ā", Gpt3Tokenizer::bytes_to_unicode()[0]);
        $this->assertEquals("d", Gpt3Tokenizer::bytes_to_unicode()[100]);
        $this->assertEquals("È", Gpt3Tokenizer::bytes_to_unicode()[200]);
        $this->assertEquals("ÿ", Gpt3Tokenizer::bytes_to_unicode()[255]);
    }

    public function test_encode_function()
    {
        $longText = <<<EOT
BPE ensures that the most common words are represented in the vocabulary as a single token while the rare words are broken down into two or more subword tokens and this is in agreement with what a subword-based tokenization algorithm does.
EOT;

        $this->assertEquals(array(1212,318,617,2420), Gpt3Tokenizer::encode("This is some text"));
        $this->assertEquals([10134, 23858, 21746], Gpt3Tokenizer::encode("hasOwnProperty"));
        $this->assertEquals([10163, 2231, 30924, 3829], Gpt3Tokenizer::encode("1234567890"));
        $this->assertEquals([ 15496, 11854, 616, 1468, 1545 ], Gpt3Tokenizer::encode("Hello darkness my old friend"));
        $this->assertEquals([33, 3732, 641, 354, 10203, 403, 1010, 794, 2150, 82, 585, 77, 2150], Gpt3Tokenizer::encode("Binnenschiffsuntersuchungsordnung"));
        $this->assertEquals([33, 11401, 19047, 326, 262, 749, 2219, 2456, 389, 7997, 287, 262, 25818, 355, 257, 2060, 11241, 981, 262, 4071, 2456, 389, 5445, 866, 656, 734, 393, 517, 850, 4775, 16326, 290, 428, 318, 287, 4381, 351, 644, 257, 850, 4775, 12, 3106, 11241, 1634, 11862, 857, 13], Gpt3Tokenizer::encode($longText));
    }

    public function test_decode_function()
    {
        $tokens = [33, 11401, 19047, 326, 262, 749, 2219, 2456, 389, 7997, 287, 262, 25818, 355, 257, 2060, 11241, 981, 262, 4071, 2456, 389, 5445, 866, 656, 734, 393, 517, 850, 4775, 16326, 290, 428, 318, 287, 4381, 351, 644, 257, 850, 4775, 12, 3106, 11241, 1634, 11862, 857, 13];
        $longText = <<<EOT
BPE ensures that the most common words are represented in the vocabulary as a single token while the rare words are broken down into two or more subword tokens and this is in agreement with what a subword-based tokenization algorithm does.
EOT;
        $this->assertEquals($longText, Gpt3Tokenizer::decode($tokens));
        $this->assertEquals('Binnenschiffsuntersuchungsordnung', Gpt3Tokenizer::decode([33, 3732, 641, 354, 10203, 403, 1010, 794, 2150, 82, 585, 77, 2150]));
    }

    public function test_count_function()
    {
        $this->assertEquals(6, Gpt3Tokenizer::count("Hello darkness my old friend!"));
    }
}
