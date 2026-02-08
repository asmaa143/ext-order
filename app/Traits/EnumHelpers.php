<?php

namespace App\Traits;


trait EnumHelpers
{
    /**
     * Get all enum values as array
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all enum names as array
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Check if a value is valid for this enum
     *
     * @param string $value The value to check
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Try to create enum from value, return null if invalid
     *
     * @param string $value
     * @return static|null
     */
    public static function tryFrom(string $value): ?static
    {
        return self::isValid($value) ? self::from($value) : null;
    }

    /**
     * Get all enum cases as associative array [value => label]
     * Requires the enum to have a label() method
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = method_exists($case, 'label')
                ? $case->label()
                : $case->name;
        }

        return $options;
    }

    /**
     * Get random enum case
     * Useful for testing and seeding
     *
     * @return static
     */
    public static function random(): static
    {
        $cases = self::cases();
        return $cases[array_rand($cases)];
    }

    /**
     * Convert enum to array representation
     *
     * @return array{name: string, value: string, label?: string}
     */
    public function toArray(): array
    {
        $array = [
            'name' => $this->name,
            'value' => $this->value,
        ];

        if (method_exists($this, 'label')) {
            $array['label'] = $this->label();
        }

        return $array;
    }
}
