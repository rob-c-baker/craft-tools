<?php
declare(strict_types=1);

namespace alanrogers\tools\fields;

use alanrogers\tools\fields\collections\CategoryGroupCollection;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Json as JsonHelper;
use craft\helpers\UrlHelper;
use craft\models\CategoryGroup;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

/**
 * A category groups field type for Craft CMS.
 * @see https://github.com/ttempleton/craft-category-groups-field
 * @license https://github.com/ttempleton/craft-category-groups-field/blob/main/LICENSE
 */
class CategoryGroupsField extends Field implements PreviewableFieldInterface
{
    /**
     * @var string|string[]
     */
    public $allowedGroups = '*';

    /**
     * @var bool Whether this field is limited to selecting one category group
     */
    public bool $singleSelection = false;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Category Groups';
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function getSettingsHtml() : string
    {
        try {
            return Craft::$app->getView()->renderTemplate(
                '_ar-tools/category-groups-field/_settings',
                [
                    'field' => $this,
                    'options' => $this->_getGroupsSettingsData(Craft::$app->getCategories()->getAllGroups()),
                    'is_single_section' => $this->_isSingleSelection()
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError|Exception $e) {
            throw new Exception('Error rendering category groups field settings.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $options = array_merge(
            [[
                'label' => '',
                'value' => null,
            ]],
            $this->_getGroupsInputData()
        );

        try {
            return Craft::$app->getView()->renderTemplate(
                '_ar-tools/category-groups-field/_input',
                [
                    'field' => $this,
                    'value' => $value,
                    'options' => $options,
                    'is_single_section' => $this->_isSingleSelection()
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError|Exception $e) {
            throw new Exception('Error rendering category groups field settings.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($value instanceof CategoryGroupCollection) {
            $html = [];

            foreach ($value->all() as $group) {
                $url = UrlHelper::cpUrl('categories/'. $group->handle);
                $html[] = '<a href="' . $url . '">' . $group->name . '</a>';
            }

            return implode('; ', $html);
        }

        if ($value instanceof CategoryGroup) {
            $url = UrlHelper::cpUrl('categories/'. $value->handle);
            return '<a href="' . $url . '">' . $value->name . '</a>';
        }

        return '';
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value === null) {
            return null;
        }

        // In case $value is a category group collection already
        if ($value instanceof CategoryGroupCollection) {
            return $value;
        }

        if (!is_array($value)) {
            $value = JsonHelper::decodeIfJson($value);
        }

        $categoriesService = Craft::$app->getCategories();

        // Single selection
        if ($this->_isSingleSelection()) {
            // Just query for that one
            if (is_array($value)) {
                $value = $value[0];
            }

            if ($value instanceof CategoryGroup) {
                return $value;
            }

            return $value !== null ? $categoriesService->getGroupById($value) : null;
        }

        // Multi-selection
        if (!empty($value)) {
            // Rather than query for each group individually, get all groups and filter for the ones we want
            $allGroups = $categoriesService->getAllGroups();

            $fieldGroups = array_filter($allGroups, function($group) use($value) {
                return in_array($group->id, $value);
            });
            $fieldGroups = array_values($fieldGroups);

            return new CategoryGroupCollection($fieldGroups);
        }

        // No category groups selected
        return null;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof CategoryGroup) {
            // Single selection is enabled, but return an array anyway, in case that setting is disabled in the future
            return [$value->id];
        }

        return $value?->ids();
    }

    private function _getGroupsSettingsData(array $groups): array
    {
        $settings = [];

        foreach ($groups as $group) {
            $settings[] = [
                'label' => $group->name,
                'value' => 'group:' . $group->uid,
            ];
        }

        return $settings;
    }

    private function _getGroupsInputData(): array
    {
        $options = [];

        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $groupSource = 'group:' . $group->uid;

            if (!is_array($this->allowedGroups) || in_array($groupSource, $this->allowedGroups)) {
                $options[] = [
                    'label' => $group->name,
                    'value' => $group->id,
                ];
            }
        }

        return $options;
    }

    private function _isSingleSelection(): bool
    {
        return $this->singleSelection;
    }
}
