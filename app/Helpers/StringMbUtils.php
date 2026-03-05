<?php

namespace de\xovatec\financeAnalyzer\Helpers;

class StringMbUtils
{
    /**
     * UTF-8 aware string padding.
     * Pads string to the specified number of VISIBLE CHARACTERS (not bytes).
     *
     * @param string $input
     * @param int $padLength
     * @param string $padString
     * @param int $padType
     * @return string
     */
    public static function strPadUtf8(
        string $input,
        int $padLength,
        string $padString = ' ',
        int $padType = STR_PAD_RIGHT
    ): string {
        $inputLength = mb_strlen($input, 'UTF-8');
        if ($padLength <= $inputLength) {
            return $input;
        }

        $padStringLength = mb_strlen($padString, 'UTF-8');
        $padNeeded = $padLength - $inputLength;
        $fullPads = (int) ($padNeeded / $padStringLength);
        $remainder = $padNeeded % $padStringLength;
        $repeatString = str_repeat($padString, $fullPads) . mb_substr($padString, 0, $remainder, 'UTF-8');

        return match ($padType) {
            STR_PAD_LEFT => $repeatString . $input,
            STR_PAD_RIGHT => $input . $repeatString,
            STR_PAD_BOTH => self::padBoth($input, $padString, $padNeeded),
            default => $input,
        };
    }

    /**
     * Helper for STR_PAD_BOTH UTF-8 padding.
     *
     * @param string $input
     * @param string $padString
     * @param int $padNeeded
     * @return string
     */
    private static function padBoth(string $input, string $padString, int $padNeeded): string
    {
        $padStringLength = mb_strlen($padString, 'UTF-8');
        $leftPad = (int) ($padNeeded / 2);
        $rightPad = $padNeeded - $leftPad;
        $leftFullPads = (int) ($leftPad / $padStringLength);
        $leftRemainder = $leftPad % $padStringLength;
        $rightFullPads = (int) ($rightPad / $padStringLength);
        $rightRemainder = $rightPad % $padStringLength;

        $leftString = str_repeat($padString, $leftFullPads) . mb_substr($padString, 0, $leftRemainder, 'UTF-8');
        $rightString = str_repeat($padString, $rightFullPads) . mb_substr($padString, 0, $rightRemainder, 'UTF-8');

        return $leftString . $input . $rightString;
    }
}
