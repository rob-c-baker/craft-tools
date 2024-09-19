<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use alanrogers\tools\exceptions\GeneratedColumnException;
use Craft;
use craft\base\FieldInterface;
use yii\base\NotSupportedException;

class GeneratedColumns
{
    /**
     * The table generated columns are stored on. This has to be `elements_sites` because that's the table the JSON
     * is stored in the `content` column.
     */
    private const string TABLE = 'elements_sites';

    /**
     * Used to get the field from a specific entry type.
     * The resulting `FieldInterface` can then be used to generate the value SQL via a call to
     * `FieldInterface::getSqlValue()` on the returned field.
     * @param string $entry_type The handle for the `EntryType`
     * @param string $field The handle for the field on the entry type
     * @return FieldInterface
     * @throws GeneratedColumnException
     */
    public function getFieldFromEntryType(string $entry_type, string $field): FieldInterface
    {
        $entry_type = Craft::$app->getEntries()->getEntryTypeByHandle($entry_type);
        if ($entry_type === null) {
            throw new GeneratedColumnException(sprintf('Invalid entry type "%s". ', $entry_type));
        }

        $field_layout = $entry_type->getFieldLayout();

        $field = $field_layout->getFieldByHandle($field);
        if ($field === null) {
            throw new GeneratedColumnException(sprintf('Invalid field "%s" on entry type "%s".', $field, $entry_type->handle));
        }

        return $field;
    }

    /**
     * Used to get the field from a specific volume.
     * The resulting `FieldInterface` can then be used to generate the value SQL via a call to
     * `FieldInterface::getSqlValue()` on the returned field.
     * @param string $volume_handle The handle for the `Volume`
     * @param string $field The handle for the field on the volume.
     * @throws GeneratedColumnException
     */
    public function getFieldFromAssetVolume(string $volume_handle, string $field): FieldInterface
    {
        $volume = Craft::$app->getVolumes()->getVolumeByHandle($volume_handle);
        if ($volume === null) {
            throw new GeneratedColumnException(sprintf('Invalid asset volume "%s".', $volume_handle));
        }

        $field_layout = $volume->getFieldLayout();

        $field = $field_layout->getFieldByHandle($field);
        if ($field === null) {
            throw new GeneratedColumnException(sprintf('Invalid field "%s" asset volume "%s".', $field, $volume_handle));
        }

        return $field;
    }

    /**
     * Detects if there is a generated column for the given field.
     * @param FieldInterface $field
     * @return bool
     */
    public function hasColumn(FieldInterface $field): bool
    {
        try {
            return Craft::$app->getDb()->columnExists(self::TABLE, $this->getColumnName($field, false));
        } catch (NotSupportedException $e) {
            // Happens if no support for this action with the driver. We are using MySQL, so there is.
            return false;
        }
    }

    /**
     * Detects if there is an index for the generated column.
     * @param FieldInterface $field
     * @return bool
     */
    public function hasIndex(FieldInterface $field): bool
    {
        try {
            $indexes = Craft::$app->getDb()->getSchema()->findIndexes(self::TABLE);
        } catch (NotSupportedException $e) {
            // Happens if no support for this action with the driver. We are using MySQL, so there is.
            return false;
        }

        return $indexes && isset(array_keys($indexes)[$this->getIndexName($field)]);
    }

    /**
     * Gets the column name for the generated column based on the field handle.
     * @param FieldInterface $field
     * @param bool $include_table If true, will prefix the column name with the table name separated with a `.`.
     * @return string
     */
    public function getColumnName(FieldInterface $field, bool $include_table = true): string
    {
        return ($include_table ? self::TABLE . '.' : '') . 'generated_' . $field->handle;
    }

    /**
     * Gets the index name for the generated column.
     * @param FieldInterface $field
     * @return string
     */
    public function getIndexName(FieldInterface $field): string
    {
        return $field->handle . '_generated_idx';
    }

    /**
     * Generates SQL to create the generated column.
     * @param FieldInterface $field
     * @param string $column_type i.e. "VARCHAR(255)"
     * @param string|null $key The data key to fetch from the JSON column, if this field stores multiple values
     * @param bool $stored Whether the column should be "STORED" or "VIRTUAL".
     * @return string
     */
    public function getCreateColumnSQL(
        FieldInterface $field,
        string $column_type,
        ?string $key = null,
        bool $stored = true
    ): string {
        return sprintf(
            'ALTER TABLE `%s` ADD COLUMN `%s` %s GENERATED ALWAYS AS (%s) %s',
            self::TABLE,
            $this->getColumnName($field, false),
            $column_type,
            $field->getValueSql($key),
            $stored ? 'STORED' : 'VIRTUAL'
        );
    }

    /**
     * Returns SQL to create the index for the generated column.
     * @param FieldInterface $field
     * @return string
     */
    public function getCreateIndexSQL(FieldInterface $field): string
    {
        return sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (`%s`)',
            self::TABLE,
            $this->getIndexName($field),
            $this->getColumnName($field, false)
        );
    }

    /**
     * Returns SQL to drop the generated column.
     * @param FieldInterface $field
     * @return string
     */
    public function getDropColumnSQL(FieldInterface $field): string
    {
        return sprintf(
            'ALTER TABLE `%s` DROP COLUMN `%s`',
            self::TABLE,
            $this->getColumnName($field)
        );
    }

    /**
     * Returns SQL to drop the index for the generated column.
     * @param FieldInterface $field
     * @return string
     */
    public function getDropIndexSQL(FieldInterface $field): string
    {
        return sprintf(
            'ALTER TABLE `%s` DROP INDEX `%s`',
            self::TABLE,
            $this->getIndexName($field)
        );
    }
}