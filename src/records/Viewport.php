<?php
namespace osim\craft\tenon\records;

use craft\db\ActiveRecord;

class Viewport extends ActiveRecord
{
    const TABLE = '{{%osim_tenon_viewports}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
