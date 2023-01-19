<?php

namespace Gioni06\Gpt3Tokenizer;

class Gpt3Tokenizer
{
    private mixed $vocab;
    private array $bpeMerges;
    private array $bpe_ranks;
    private bool $apcuAvailable;

    private array $cache = [];

    private bool $useCache;


    public function __construct(Gpt3TokenizerConfig $config)
    {
        $vocabPath = $config->getConfig()['vocabPath'];
        $vocab = new Vocab($vocabPath);
        $this->vocab = $vocab->data();
        // Free memory that is no longer needed
        unset($vocab);

        $mergesPath = $config->getConfig()['mergesPath'];
        $merges = new Merges($mergesPath);
        $this->bpeMerges = $merges->bpeMerges();
        $this->bpe_ranks = array_combine(Gpt3Tokenizer::zipBpe($this->bpeMerges), range(0, count($this->bpeMerges) - 1));
        // Free memory that is no longer needed
        unset($this->bpeMerges);
        unset($merges);

        $this->apcuAvailable = function_exists('apcu_enabled') && apcu_enabled();
        $this->useCache = $config->getConfig()['useCache'];
    }

    private function cacheSet($key, $val): void
    {
        if ($this->apcuAvailable) {
            apcu_store($key, $val);
        } else {
            $this->cache[$key] = $val;
        }
    }

    private function cacheGet($key): mixed
    {
        if ($this->apcuAvailable) {
            return apcu_fetch($key);
        } else {
            return $this->cache[$key] ?? null;
        }
    }

    private function cacheExists($key): array|bool
    {
        if ($this->apcuAvailable) {
            return apcu_exists($key);
        } else {
            return isset($this->cache[$key]);
        }
    }

    public static function bytes_to_unicode(): array
    {
        $bs = array_merge(range(mb_ord('!'), mb_ord('~') + 1), range(mb_ord('¡'), mb_ord('¬') + 1), range(mb_ord('®'), mb_ord('ÿ') + 1));

        $cs = $bs;
        $n = 0;
        foreach (range(0, 2 ** 8 - 1) as $b) {
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

    public function bpe(string $token): string
    {
        if($this->useCache && $this->cacheExists($token)) {
            return $this->cacheGet($token);
        }

        $chars = mb_str_split($token);
        $pairs = self::get_pairs($chars);
        if(!count($pairs)) {
            return implode(" ", $chars);
        }

        while (true) {
            $minPairs = [];
            foreach ($pairs as $pair) {
                $pairStr = implode(",", $pair);
                if (array_key_exists($pairStr, $this->bpe_ranks)) {
                    $minPairs[$this->bpe_ranks[$pairStr]] = $pair;
                } else {
                    $minPairs[10e10] = $pair;
                }
            }
            ksort($minPairs);

            $bigram = $minPairs[min(array_map(function($x) {
                return intval($x);
            }, array_keys($minPairs)))];

            $bigramStr = implode(",", $bigram);
            if (!array_key_exists($bigramStr, $this->bpe_ranks)) {
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
        $result = implode(" ", $chars);
        if($this->useCache) {
            $this->cacheSet($token, $result);
        }
        return $result;
    }

    public function encode(string $text): array
    {
        $byte_encoder = self::bytes_to_unicode();
        $pat = "/'s|'t|'re|'ve|'m|'ll|'d| ?[[:alpha:]]+| ?[[:digit:]]+| ?[^[:space:]\pL\pN]+|\s+(?!\S)|\s+/u";
        $bpe_tokens = array();
        $matches = array();
        preg_match_all($pat, $text, $matches);
        foreach ($matches[0] as $token) {
            $token = implode(array_map(function($x) use ($byte_encoder) {
                return $byte_encoder[$x];
            }, self::encodeStr($token)));

            $new_tokens = array_map(function($x) {
                return $this->vocab[$x];
            }, explode(' ', $this->bpe($token)));
            $bpe_tokens = array_merge($bpe_tokens, $new_tokens);
        }
        return $bpe_tokens;
    }

    public function decode(array $tokens): string
    {
        $decoder = array_flip($this->vocab);
        $byte_decoder = array_flip(self::bytes_to_unicode());

        $text = array_map(function($x) use ($decoder) {
            return $decoder[$x];
        }, $tokens);

        $text = implode($text);
        $chars = mb_str_split($text);
        $decodedChars = array();
        for ($i = 0; $i < count($chars); $i++) {
            $decodedChars[] = $byte_decoder[$chars[$i]];
        }
        return self::decodeStr($decodedChars);
    }

    public function count(string $text): int
    {
        $tokens = self::encode($text);
        return count($tokens);
    }
}
