<?php declare(strict_types=1);

namespace alanrogers\tools\services\generatedcolumns;

enum ColumnType: string
{
    case ASSET_VOLUME = 'asset_volume';
    case ENTRY_TYPE = 'entry_type';

    public function dbIdentifier(): ?string
    {
        return match($this) {
            self::ASSET_VOLUME => 'av',
            self::ENTRY_TYPE => 'et',
        };
    }
}