<?php

namespace de\xovatec\financeAnalyzer\Traits\Utils;

trait EnumFromName
{
    /**
     *
     * @param string $name
     * @return static|null
     */
    public static function tryFromName(string $name): ?static
    {
        foreach (static::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    /**
     *
     * @param string $name
     * @return static
     */
    public static function fromName(string $name): static
    {
        $case = static::tryFromName($name);

        if ($case === null) {
            throw new \ValueError(
                sprintf('"%s" is not a valid case name for enum "%s"', $name, static::class)
            );
        }

        return $case;
    }
}
