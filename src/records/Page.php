<?php
namespace osim\craft\tenon\records;

use craft\db\ActiveRecord;

class Page extends ActiveRecord
{
    const TABLE = '{{%osim_tenon_pages}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
