<?php
namespace osim\craft\tenon\records;

use craft\db\ActiveRecord;

class Project extends ActiveRecord
{
    const TABLE = '{{%osim_tenon_projects}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
