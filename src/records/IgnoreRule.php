<?php
namespace osim\craft\tenon\records;

use craft\db\ActiveRecord;

class IgnoreRule extends ActiveRecord
{
    const TABLE = '{{%osim_tenon_ignore_rules}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
