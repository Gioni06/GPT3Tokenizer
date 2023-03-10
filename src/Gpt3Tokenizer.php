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
            /** @noinspection PhpComposerExtensionStubsInspection */
            apcu_store($key, $val);
        } else {
            $this->cache[$key] = $val;
        }
    }

    private function cacheGet($key): mixed
    {
        if ($this->apcuAvailable) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            return apcu_fetch($key);
        } else {
            return $this->cache[$key] ?? null;
        }
    }

    private function cacheExists($key): array|bool
    {
        if ($this->apcuAvailable) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            return apcu_exists($key);
        } else {
            return isset($this->cache[$key]);
        }
    }

    public static function bytes_to_unicode(): array
    {
        // Bytes-to-Unicode is a list of utf-8 byte and a corresponding unicode string.
        // Using this static list is much faster than decoding the utf-8 everytime a character is encountered.
        // Also, it produces the exact output as tokenizer from OpenAI uses. https://beta.openai.com/tokenizer
        return [
            0 => '??',
            1 => '??',
            2 => '??',
            3 => '??',
            4 => '??',
            5 => '??',
            6 => '??',
            7 => '??',
            8 => '??',
            9 => '??',
            10 => '??',
            11 => '??',
            12 => '??',
            13 => '??',
            14 => '??',
            15 => '??',
            16 => '??',
            17 => '??',
            18 => '??',
            19 => '??',
            20 => '??',
            21 => '??',
            22 => '??',
            23 => '??',
            24 => '??',
            25 => '??',
            26 => '??',
            27 => '??',
            28 => '??',
            29 => '??',
            30 => '??',
            31 => '??',
            32 => '??',
            33 => '!',
            34 => '"',
            35 => '#',
            36 => '$',
            37 => '%',
            38 => '&',
            39 => '\'',
            40 => '(',
            41 => ')',
            42 => '*',
            43 => '+',
            44 => ',',
            45 => '-',
            46 => '.',
            47 => '/',
            48 => '0',
            49 => '1',
            50 => '2',
            51 => '3',
            52 => '4',
            53 => '5',
            54 => '6',
            55 => '7',
            56 => '8',
            57 => '9',
            58 => ':',
            59 => ';',
            60 => '<',
            61 => '=',
            62 => '>',
            63 => '?',
            64 => '@',
            65 => 'A',
            66 => 'B',
            67 => 'C',
            68 => 'D',
            69 => 'E',
            70 => 'F',
            71 => 'G',
            72 => 'H',
            73 => 'I',
            74 => 'J',
            75 => 'K',
            76 => 'L',
            77 => 'M',
            78 => 'N',
            79 => 'O',
            80 => 'P',
            81 => 'Q',
            82 => 'R',
            83 => 'S',
            84 => 'T',
            85 => 'U',
            86 => 'V',
            87 => 'W',
            88 => 'X',
            89 => 'Y',
            90 => 'Z',
            91 => '[',
            92 => '\\',
            93 => ']',
            94 => '^',
            95 => '_',
            96 => '`',
            97 => 'a',
            98 => 'b',
            99 => 'c',
            100 => 'd',
            101 => 'e',
            102 => 'f',
            103 => 'g',
            104 => 'h',
            105 => 'i',
            106 => 'j',
            107 => 'k',
            108 => 'l',
            109 => 'm',
            110 => 'n',
            111 => 'o',
            112 => 'p',
            113 => 'q',
            114 => 'r',
            115 => 's',
            116 => 't',
            117 => 'u',
            118 => 'v',
            119 => 'w',
            120 => 'x',
            121 => 'y',
            122 => 'z',
            123 => '{',
            124 => '|',
            125 => '}',
            126 => '~',
            127 => '??',
            128 => '??',
            129 => '??',
            130 => '??',
            131 => '??',
            132 => '??',
            133 => '??',
            134 => '??',
            135 => '??',
            136 => '??',
            137 => '??',
            138 => '??',
            139 => '??',
            140 => '??',
            141 => '??',
            142 => '??',
            143 => '??',
            144 => '??',
            145 => '??',
            146 => '??',
            147 => '??',
            148 => '??',
            149 => '??',
            150 => '??',
            151 => '??',
            152 => '??',
            153 => '??',
            154 => '??',
            155 => '??',
            156 => '??',
            157 => '??',
            158 => '??',
            159 => '??',
            160 => '??',
            161 => '??',
            162 => '??',
            163 => '??',
            164 => '??',
            165 => '??',
            166 => '??',
            167 => '??',
            168 => '??',
            169 => '??',
            170 => '??',
            171 => '??',
            172 => '??',
            173 => '??',
            174 => '??',
            175 => '??',
            176 => '??',
            177 => '??',
            178 => '??',
            179 => '??',
            180 => '??',
            181 => '??',
            182 => '??',
            183 => '??',
            184 => '??',
            185 => '??',
            186 => '??',
            187 => '??',
            188 => '??',
            189 => '??',
            190 => '??',
            191 => '??',
            192 => '??',
            193 => '??',
            194 => '??',
            195 => '??',
            196 => '??',
            197 => '??',
            198 => '??',
            199 => '??',
            200 => '??',
            201 => '??',
            202 => '??',
            203 => '??',
            204 => '??',
            205 => '??',
            206 => '??',
            207 => '??',
            208 => '??',
            209 => '??',
            210 => '??',
            211 => '??',
            212 => '??',
            213 => '??',
            214 => '??',
            215 => '??',
            216 => '??',
            217 => '??',
            218 => '??',
            219 => '??',
            220 => '??',
            221 => '??',
            222 => '??',
            223 => '??',
            224 => '??',
            225 => '??',
            226 => '??',
            227 => '??',
            228 => '??',
            229 => '??',
            230 => '??',
            231 => '??',
            232 => '??',
            233 => '??',
            234 => '??',
            235 => '??',
            236 => '??',
            237 => '??',
            238 => '??',
            239 => '??',
            240 => '??',
            241 => '??',
            242 => '??',
            243 => '??',
            244 => '??',
            245 => '??',
            246 => '??',
            247 => '??',
            248 => '??',
            249 => '??',
            250 => '??',
            251 => '??',
            252 => '??',
            253 => '??',
            254 => '??',
            255 => '??',
        ];
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
