<?php
namespace osim\craft\tenon\records;

use craft\db\ActiveRecord;

class History extends ActiveRecord
{
    const TABLE = '{{%osim_tenon_history}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
