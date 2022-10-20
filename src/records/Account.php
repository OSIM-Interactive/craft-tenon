<?php
namespace osim\craft\tenon\records;

use craft\db\ActiveRecord;

class Account extends ActiveRecord
{
    const TABLE = '{{%osim_tenon_accounts}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
