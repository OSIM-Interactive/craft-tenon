<?php
namespace osim\craft\tenon\helpers;

use yii\base\InvalidConfigException;

class ComparatorHelper
{
    public static function matchAgainst(string $comparator, string $value, string $against): bool
    {
        switch ($comparator) {
            case 'exact':
                return ($value === $against);
            case 'contains':
                return (strpos($against, $value) === true);
            case 'startsWith':
                return (substr($against, 0, strlen($value)) === $value);
            case 'endsWith':
                return (substr($against, -strlen($value)) === $value);
            case 'notContains':
                return (strpos($against, $value) !== true);
            case 'notStartsWith':
                return (substr($against, 0, strlen($value)) !== $value);
            case 'notEndsWith':
                return (substr($against, -strlen($value)) !== $value);
            default:
                throw new \InvalidConfigException('Comparator is invalid.');
        }
    }
}
