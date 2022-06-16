<?php
declare(strict_types=1);

namespace alanrogers\tools\fields\collections;

use ArrayIterator;
use ArrayObject;
use Craft;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\models\CategoryGroup;
use Exception;

/**
 * Category Group Collection class.
 * 
 * @package ttempleton\categorygroupsfield\collections
 * @author Thomas Templeton
 * @since 1.2.0
 */
class CategoryGroupCollection extends ArrayObject
{
    /**
     * @var CategoryGroup[] The category groups of this collection
     */
    private array $_groups = [];

    /**
     * @var string[] The settings applied to this collection, in the order they were applied
     */
    private array $_settings = [];

    /**
     * @var bool
     */
    private bool $_inReverse = false;

    /**
     * @param CategoryGroup[] $groups
     * @throws Exception
     */
    public function __construct(array $groups)
    {
        parent::__construct();

        // Ensure it's an array of category groups
        foreach ($groups as $group) {
            if (!($group instanceof CategoryGroup)) {
                throw new Exception('Trying to create a CategoryGroupCollection that does not contain only category groups');
            }
        }

        $this->_groups = $groups;
    }

    /**
     * Sets whether the category groups should be returned in reverse order.
     * @param bool $reverse
     * @return static
     */
    public function inReverse(bool $reverse = true) : CategoryGroupCollection
    {
        $this->_inReverse = $reverse;
        $this->_settings['inReverse'] = $reverse;
        return $this;
    }

    /**
     * Returns the number of category groups in this collection.
     * @return int
     */
    public function count(): int
    {
        return count($this->_getResults());
    }

    /**
     * Returns the category group collection array.
     * @return CategoryGroup[]
     */
    public function all(): array
    {
        return $this->_getResults();
    }

    /**
     * Returns the first category group in the collection, or null if the collection is empty.
     * @return CategoryGroup|null
     */
    public function one() : ?CategoryGroup
    {
        $groups = $this->_getResults();

        if (!empty($groups)) {
            return $groups[0];
        }

        return null;
    }

    /**
     * Returns the category group at the given index, or null if the index is invalid.
     * @param int $index
     * @return CategoryGroup|null
     */
    public function nth(int $index): ?CategoryGroup
    {
        $groups = $this->_getResults();

        if ($index >= 0 && count($groups) > $index) {
            return $groups[$index];
        }

        return null;
    }

    /**
     * Returns the IDs of the category groups.
     * @return int[]
     */
    public function ids(): array
    {
        $ids = [];

        foreach ($this->_getResults() as $group) {
            $ids[] = $group->id;
        }

        return $ids;
    }

    /**
     * Used when iterating directly over the category group collection in a template.
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->_getResults());
    }

    /**
     * Returns a category query prepared with the IDs of the groups in this collection, optionally
     * with other criteria applied.
     * @param array $criteria
     * @return CategoryQuery
     */
    public function categories(array $criteria = []) : object
    {
        // Add our IDs to any `groupId` passed in
        if (isset($criteria['groupId'])) {
            $otherGroupIds = is_array($criteria['groupId']) ? $criteria['groupId'] : [$criteria['groupId']];
            $criteria['groupId'] = array_merge($this->ids(), $otherGroupIds);
        } else {
            $criteria['groupId'] = $this->ids();
        }

        return Craft::configure(Category::find(), $criteria);
    }

    private function _getResults(): array
    {
        $groups = $this->_groups;

        if (!empty($this->_settings['inReverse']) && $this->_inReverse) {
            $groups = array_reverse($groups, false);
        }

        return $groups;
    }
}
