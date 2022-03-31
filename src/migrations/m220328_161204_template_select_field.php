<?php

namespace alanrogers\tools\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use yii\db\Exception;

class m220328_161204_template_select_field extends Migration
{
    private const OLD_TPL_TYPE = 'superbig\\templateselect\\fields\\TemplateSelectField';
    private const NEW_TPL_TYPE = 'alanrogers\\tools\\fields\\TemplateSelectField';

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function safeUp()
    {
        $query = (new Query())
            ->select([ 'id', 'type' ])
            ->from([ 'fields' => Table::FIELDS ])
            ->where([ 'type' => self::OLD_TPL_TYPE ]);

        $update_ids = [];
        foreach ($query->all() as $row) {
            $update_ids[] = (int) $row['id'];
        }

        $this->getDb()->createCommand()->update(
            Table::FIELDS,
            ['type' => self::NEW_TPL_TYPE ],
            ['id' => $update_ids]
        )->execute();

        // thrown exception will prevent this if something goes wrong on DB
        return true;
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function safeDown()
    {
        $query = (new Query())
            ->select([ 'id', 'type' ])
            ->from([ 'fields' => Table::FIELDS ])
            ->where([ 'type' => self::NEW_TPL_TYPE ]);

        $update_ids = [];
        foreach ($query->all() as $row) {
            $update_ids[] = (int) $row['id'];
        }

        $this->getDb()->createCommand()->update(
            Table::FIELDS,
            ['type' => self::OLD_TPL_TYPE ],
            ['id' => $update_ids]
        )->execute();

        // thrown exception will prevent this if something goes wrong on DB
        return true;
    }
}