# GPT3Tokenizer for PHP

This is a PHP port of the GPT-3 tokenizer. It is based on the [original Python implementation](https://huggingface.co/docs/transformers/model_doc/gpt2#transformers.GPT2Tokenizer) and the [Nodejs implementation](https://github.com/latitudegames/GPT-3-Encoder).

GPT-2 and GPT-3 use a technique called byte pair encoding to convert text into a sequence of integers, which are then used as input for the model.
When you interact with the OpenAI API, you may find it useful to calculate the amount of tokens in a given text before sending it to the API.

## Encode a text

```php
use Gioni06\GPT3Tokenizer;

$text = "This is some text";
$tokens = GPT3Tokenizer::encode($text);
// [1212,318,617,2420]
```

## Decode a text

```php
use Gioni06\GPT3Tokenizer;

$tokens = [1212,318,617,2420]
$text = GPT3Tokenizer::decode($tokens);
// "This is some text"
```

## Count the number of tokens in a text

```php
use Gioni06\GPT3Tokenizer;

$text = "This is some text";
$numberOfTokens = GPT3Tokenizer::count($text);
// 4
```

## License
This project uses the Apache License 2.0 license. See the [LICENSE](LICENSE) file for more information.