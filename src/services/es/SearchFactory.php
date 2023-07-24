<?php declare(strict_types=1);

namespace alanrogers\tools\services\es;

class SearchFactory
{
    /**
     * @var Search[]
     */
    protected static array $instances = [];

    /**
     * Tries to find, instantiate and return an ES search class object.
     * @param string $index_name - before any ES index name normalisation applied
     * @return Search|null
     * @throws ESException
     */
    public static function getSearch(string $index_name) : ?Search
    {
        if (isset(static::$instances[$index_name])) {
            return static::$instances[$index_name];
        }

        $index = Config::getInstance()->getIndexByName($index_name, false);

        if ($index) {
            static::$instances[$index_name] = $index->getSearch();
        } else {
            throw new ESException(sprintf('ES Index "%s" does not exist while getting search instance.', $index_name));
        }

        return static::$instances[$index_name];
    }
}