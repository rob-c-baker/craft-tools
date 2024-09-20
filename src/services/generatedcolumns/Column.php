<?php declare(strict_types=1);

namespace alanrogers\tools\services\generatedcolumns;

use alanrogers\tools\exceptions\GeneratedColumnException;
use alanrogers\tools\services\GeneratedColumns;
use Craft;
use craft\base\FieldInterface;
use yii\base\NotSupportedException;

class Column
{
    /**
     * The Craft field instance for the generated column.
     * @var FieldInterface
     */
    private Fieldinterface $field;

    /**
     * @throws GeneratedColumnException
     */
    public function __construct(
        GeneratedColumns $generated_columns,
        public readonly string $table,
        public readonly string $field_name,
        public readonly string $type_handle,
        public readonly ColumnType $type
    ) {
        $this->field = match ($type) {
            ColumnType::ENTRY_TYPE => $generated_columns->getFieldFromEntryType($type_handle, $field_name),
            ColumnType::ASSET_VOLUME => $generated_columns->getFieldFromAssetVolume($type_handle, $field_name),
        };
    }

    /**
     * Detects if the generated column exists on the database.
     * @return bool
     */
    public function exists(): bool
    {
        try {
            return Craft::$app->getDb()->columnExists($this->table, $this->getDBName(false));
        } catch (NotSupportedException $e) {
            // Happens if no support for this action with the driver. We are using MySQL, so there is.
            return false;
        }
    }

    /**
     * Detects if there is an index for the generated column.
     * @return bool
     */
    public function hasIndex(): bool
    {
        try {
            $indexes = Craft::$app->getDb()->getSchema()->findIndexes($this->table);
        } catch (NotSupportedException $e) {
            // Happens if no support for this action with the driver. We are using MySQL, so there is.
            return false;
        }

        return $indexes && isset(array_keys($indexes)[$this->getIndexName()]);
    }

    /**
     * Gets the column name for the generated column based on the type, type handle and field handle.
     * @param bool $include_table If true, will prefix the column name with the table name separated with a `.`.
     * @return string
     */
    public function getDBName(bool $include_table = true): string
    {
        $base_name = implode('_', [
            'gen',
            $this->type->dbIdentifier(),
            $this->type_handle,
            $this->field_name
        ]);
        return ($include_table ? $this->table . '.' : '') . $base_name;
    }

    /**
     * Gets the index name for the generated column.
     * @return string
     */
    public function getIndexName(): string
    {
        return implode('_', [
            $this->type->dbIdentifier(),
            $this->type_handle,
            $this->field->handle,
            'gidx'
        ]);
    }

    /**
     * Generates SQL to create the generated column.
     * @param string $column_type i.e. "VARCHAR(255)"
     * @param string|null $key The data key to fetch from the JSON column, if this field stores multiple values
     * @param bool $stored Whether the column should be "STORED" or "VIRTUAL".
     * @return string
     */
    public function createColumnSQL(
        string $column_type,
        ?string $key = null,
        bool $stored = true
    ): string {
        return sprintf(
            'ALTER TABLE `%s` ADD COLUMN `%s` %s GENERATED ALWAYS AS (%s) %s',
            $this->table,
            $this->getDBName(false),
            $column_type,
            $this->field->getValueSql($key),
            $stored ? 'STORED' : 'VIRTUAL'
        );
    }

    /**
     * Returns SQL to create the index for the generated column.
     * @return string
     */
    public function createIndexSQL(): string
    {
        return sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (`%s`)',
            $this->table,
            $this->getIndexName(),
            $this->getDBName(false)
        );
    }

    /**
     * Returns SQL to drop the generated column.
     * @return string
     */
    public function getDropColumnSQL(): string
    {
        return sprintf(
            'ALTER TABLE `%s` DROP COLUMN `%s`',
            $this->table,
            $this->getDBName()
        );
    }

    /**
     * Returns SQL to drop the index for the generated column.
     * @return string
     */
    public function getDropIndexSQL(): string
    {
        return sprintf(
            'ALTER TABLE `%s` DROP INDEX `%s`',
            $this->table,
            $this->getIndexName()
        );
    }
}