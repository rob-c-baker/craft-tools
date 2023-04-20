<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Assert\Assertion;
use Assert\AssertionFailedException;
use craft\elements\Category;
use craft\records\CategoryGroup;

class CategoryId extends Base
{
    /**
     * Array of valid category group handles
     * @var array
     */
    private static array $category_groups = [];

    /**
     * Stores maps handle to category ids for a specific group so we only load them once per request
     * @var array
     */
    private static array $group_ids = [];

    /**
     * @inheritDoc
     */
    protected function validate(mixed $value): bool
    {
        if (!isset($this->options['group'])) {
            throw new \InvalidArgumentException('To use the CategoryId validator you must pass in an $options parameter to the constructor with with an array key of "group" containing the handle of the category group.');
        }

        try {
            $result = Assertion::integerish($value);
        } catch (AssertionFailedException $e) {
            $this->addError('The value must be a whole number (integer).');
            $result = false;
        }

        if ($result) {

            // check group is valid!
            if (empty(self::$category_groups)) {
                self::$category_groups = array_column(CategoryGroup::find()->all(), 'handle');
            }

            $result = in_array($this->options['group'], self::$category_groups);
            if (!$result) {
                throw new \InvalidArgumentException(sprintf('The supplied category group "%.50s" does not exist.', $this->options['group']));
            }

            if (empty(self::$group_ids[$this->options['group']])) {
                self::$group_ids[$this->options['group']] = Category::find()
                    ->group($this->options['group'])
                    ->withStructure(false)
                    ->status(null)
                    ->ids();
            }

            $result = in_array($value, self::$group_ids[$this->options['group']]);
            if (!$result) {
                $this->addError(sprintf('The category supplied does not exist in the group "%.50s".', $this->options['group']));
            }
        }

        return $result;
    }
}