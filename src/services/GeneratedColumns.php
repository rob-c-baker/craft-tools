<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use alanrogers\tools\exceptions\GeneratedColumnException;
use alanrogers\tools\services\generatedcolumns\Column;
use alanrogers\tools\services\generatedcolumns\ColumnType;
use Craft;
use craft\base\FieldInterface;

class GeneratedColumns
{
    /**
     * The default table generated columns are stored on. In 99% of cases this has to be `elements_sites` because
     * that's the table where the JSON is stored in the `content` column.
     */
    private const string TABLE = 'elements_sites';

    /**
     * Gets a specific column instance.
     * @param string $field_name
     * @param string $type_handle
     * @param ColumnType $type
     * @param string|null $table
     * @return Column
     * @throws GeneratedColumnException
     */
    public function getColumn(string $field_name, string $type_handle, ColumnType $type, ?string $table = self::TABLE): Column
    {
        return new Column(
            $this,
            $table,
            $field_name,
            $type_handle,
            $type
        );
    }

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
}