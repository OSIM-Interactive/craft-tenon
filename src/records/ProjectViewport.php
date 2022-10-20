<?php
namespace osim\craft\tenon\records;

use craft\db\ActiveRecord;

class ProjectViewport extends ActiveRecord
{
    const TABLE = '{{%osim_tenon_projects_viewports}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
