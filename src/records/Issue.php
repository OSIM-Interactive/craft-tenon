<?php
namespace osim\craft\tenon\records;

use craft\db\ActiveRecord;

class Issue extends ActiveRecord
{
    const TABLE = '{{%osim_tenon_issues}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
