<?php

namespace Gioni06\Gpt3Tokenizer;

class Gpt3Tokenizer
{
    public static function bytes_to_unicode(): array
    {
        $bs = array_merge(range(mb_ord('!'), mb_ord('~') + 1), range(mb_ord('¡'), mb_ord('¬') + 1), range(mb_ord('®'), mb_ord('ÿ') + 1));

        $cs = $bs;
        $n = 0;
        for ($b = 0; $b < 2 ** 8; $b++) {
            if (!in_array($b, $bs)) {
                $bs[] = $b;
                $cs[] = 2 ** 8 + $n;
                $n = $n + 1;
            }
        }

        $cs = array_map(function($x) {
            return mb_chr($x);
        }, $cs);

        $result = array();
        array_map(function($_, $i) use(&$result, $bs, $cs) {
            $result[$bs[$i]] = $cs[$i];
        }, $bs, array_keys($cs));
        if (array_key_exists(256, $result)) {
            unset($result[256]);
        }
        ksort($result);
        return $result;
    }

    public static function encodeStr(string $str): array {
        $bytes = str_split(bin2hex(mb_convert_encoding($str, 'UTF-8')), 2);
        return array_map(function($byte){
            return hexdec($byte);
        },$bytes);
    }

    public static function decodeStr(array $codes): string {
        $bytes = array_map(function($code) {
            return chr($code);
        }, $codes);
        return implode($bytes);
    }

    public static function bpeMerges(array $lines): array
    {
        return array_map(function($x) {
            return array_filter(preg_split("/(\s+)/", $x), function($e) { return strlen(trim($e)) > 0; });
        }, array_slice($lines, 1, count($lines) - 1));
    }

    public static function get_pairs($input_arr): array
    {
        $pairs = array();
        for ($i = 0; $i < count($input_arr) - 1; $i++) {
            $pairs[] = array($input_arr[$i], $input_arr[$i + 1]);
        }
        // remove duplicates
        return array_unique($pairs, SORT_REGULAR);
    }

    public static function zipBpe(array $bpeMerges): array
    {
        $bpe = [];
        foreach ($bpeMerges as $merge) {
            $bpe[] = $merge[0] . ',' . $merge[1];
        }
        return $bpe;
    }

    public static function dictZip(array $x, array $y): array
    {
        return array_combine($x, $y);
    }

    public static function splitString($string): array|bool|null
    {
        return mb_str_split($string);
    }
    public static function bpe(string $token): string
    {
        $bpeMerges = Gpt3Tokenizer::bpeMerges((new Merges())->lines());
        $bpe_ranks = Gpt3Tokenizer::dictZip(Gpt3Tokenizer::zipBpe($bpeMerges), range(0, count($bpeMerges) - 1));
        $chars = self::splitString($token);
        $pairs = self::get_pairs($chars);
        if(!count($pairs)) {
            return implode(" ", $chars);
        }

        while (true) {
            $minPairs = [];
            foreach ($pairs as $pair) {
                $pairStr = implode(",", $pair);
                if (array_key_exists($pairStr, $bpe_ranks)) {
                    $minPairs[$bpe_ranks[$pairStr]] = $pair;
                } else {
                    $minPairs[10e10] = $pair;
                }
            }
            ksort($minPairs);

            $bigram = $minPairs[min(array_map(function($x) {
                return intval($x);
            }, array_keys($minPairs)))];

            $bigramStr = implode(",", $bigram);
            if (!array_key_exists($bigramStr, $bpe_ranks)) {
                break;
            }

            $first = $bigram[0];
            $second = $bigram[1];
            $new_word = array();
            $i = 0;

            while ($i < count($chars)) {
                $j = array_search($first, array_slice($chars, $i));
                if ($j === false) {
                    $new_word = array_merge($new_word, array_slice($chars, $i));
                    break;
                }
                $new_word = array_merge($new_word, array_slice($chars, $i, $j));
                $i = $i + $j;

                if ($chars[$i] === $first && $i < count($chars) - 1 && $chars[$i + 1] === $second) {
                    $new_word[] = $first . $second;
                    $i = $i + 2;
                } else {
                    $new_word[] = $chars[$i];
                    $i++;
                }
            }
            $chars = $new_word;
            if (count($chars) === 1) {
                break;
            } else {
                $pairs = self::get_pairs($chars);
            }
        }
        return implode(" ", $chars);
    }

    public static function encode(string $text): array
    {
        $encoder = (new Vocab())->data();
        $byte_encoder = self::bytes_to_unicode();
        $pat = "/'s|'t|'re|'ve|'m|'ll|'d| ?[[:alpha:]]+| ?[[:digit:]]+| ?[^[:space:]\pL\pN]+|\s+(?!\S)|\s+/u";
        $bpe_tokens = array();
        $matches = array();
        preg_match_all($pat, $text, $matches);
        foreach ($matches[0] as $token) {
            $token = implode(array_map(function($x) use ($byte_encoder) {
                return $byte_encoder[$x];
            }, self::encodeStr($token)));

            $new_tokens = array_map(function($x) use ($encoder) {
                return $encoder[$x];
            }, explode(' ', self::bpe($token)));
            $bpe_tokens = array_merge($bpe_tokens, $new_tokens);
        }
        return $bpe_tokens;
    }

    public static function decode(array $tokens): string
    {
        $encoder = (new Vocab())->data();
        $decoder = array_flip($encoder);
        $byte_decoder = array_flip(self::bytes_to_unicode());

        $text = array_map(function($x) use ($decoder) {
            return $decoder[$x];
        }, $tokens);

        $text = implode($text);
        $chars = self::splitString($text);
        $decodedChars = array();
        for ($i = 0; $i < count($chars); $i++) {
            $decodedChars[] = $byte_decoder[$chars[$i]];
        }
        return self::decodeStr($decodedChars);
    }

    public static function count(string $text): int
    {
        $tokens = self::encode($text);
        return count($tokens);
    }
    public function __construct(){}
}
