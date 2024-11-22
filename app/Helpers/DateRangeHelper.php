<?php

namespace de\xovatec\financeAnalyzer\Helpers;

use Carbon\Carbon;
use InvalidArgumentException;

class DateRangeHelper
{
    public const BOUNDARY_START = 'start';
    public const BOUNDARY_END = 'end';
    public const FROM = 'from';
    public const TO = 'to';

    /**
     *
     * @param string $range
     * @return boolean
     */
    public static function validFormat(string $range): bool
    {
        return preg_match('/^(\d{4}|\d{6}|\d{8})(-(\d{4}|\d{6}|\d{8}))?$/', $range);
    }

    /**
     *
     * @param string $from
     * @param string $to
     * @return boolean
     */
    public static function validFromTo(string $from, string $to): bool
    {
        return !Carbon::parse($to)->lessThan(Carbon::parse($from));
    }

    /**
     *
     * @param string $range
     * @return array
     */
    public static function parseDateRange(string $range): array
    {
        if (!self::validFormat($range)) {
            throw new InvalidArgumentException('The format of the time period is invalid');
        }

        $parts = explode('-', $range);
        $from = null;
        $to = null;

        if (count($parts) === 2) {
            $from = self::parsePartialDate($parts[0], self::BOUNDARY_START);
            $to = self::parsePartialDate($parts[1], self::BOUNDARY_END);
        } elseif (count($parts) === 1) {
            $from = self::parsePartialDate($parts[0], self::BOUNDARY_START);
            $to = self::parsePartialDate($parts[0], self::BOUNDARY_END);
        }

        return [
            self::FROM => $from,
            self::TO => $to,
        ];
    }

    private static function parsePartialDate(string $input, string $boundary): ?string
    {
        $input = trim($input);

        if (preg_match('/^\d{4}$/', $input)) {
            return $boundary === self::BOUNDARY_START
                ? Carbon::createFromFormat('Y-m-d', "$input-01-01")->toDateString()
                : Carbon::createFromFormat('Y-m-d', "$input-12-31")->toDateString();
        }

        if (preg_match('/^\d{6}$/', $input)) {
            $year = substr($input, 0, 4);
            $month = substr($input, 4, 2);

            return $boundary === self::BOUNDARY_START
                ? Carbon::createFromFormat('Y-m-d', "$year-$month-01")->toDateString()
                : Carbon::createFromFormat('Y-m-d', "$year-$month-01")->endOfMonth()->toDateString();
        }

        if (preg_match('/^\d{8}$/', $input)) {
            return Carbon::createFromFormat(
                'Y-m-d',
                substr($input, 0, 4) . '-' . substr($input, 4, 2) . '-' . substr($input, 6, 2)
            )->toDateString();
        }

        throw new InvalidArgumentException('The date is invalid');
    }
}
