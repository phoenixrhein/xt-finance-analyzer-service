<?php

namespace de\xovatec\financeAnalyzer\Helpers;

use Exception;
use Illuminate\Support\Str;

class ExceptionMessageHelper
{
    /**
     *
     * @param string $msg
     * @return object
     */
    public static function parse(string $msg): object
    {
        $text = '';
        $key = '';
        $json = array();

        if (preg_match('/\[:(.*?):\]/', $msg, $matches)) {
            $fullMatch = $matches[0];
            $keyJsonPart = $matches[1];

            if (strpos($keyJsonPart, '{') !== false) {
                $startJson = strpos($keyJsonPart, '{');
                $key = substr($keyJsonPart, 0, $startJson);
                $json = json_decode(substr($keyJsonPart, $startJson), true);
            } else {
                $key = $keyJsonPart;
            }

            $msg = str_replace($fullMatch, '', $msg);
        }

        $text = trim($msg);

        return new class($text ?: null, $key ?: null, $json) {
            public function __construct(
                public ?string $text,
                public ?string $key,
                public array $json
            ) {

            }
        };
    }

    /**
     *
     * @param Exception $exception
     * @return string
     */
    public static function cleanException(Exception $exception): string
    {
        return Str::replaceMatches(
            pattern: '/\[:.*.:]/',
            replace: '',
            subject: $exception->__toString()
        );
    }

    /**
     *
     * @param string $text
     * @param string $key
     * @param array $replaceParams
     * @return string
     */
    public static function generateMessageString(string $text = '', string $key = '', array $replaceParams = []): string
    {
        return $text . '[:' . $key . json_encode($replaceParams) . ':]';
    }
}
