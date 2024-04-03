<?php
declare(strict_types=1);

namespace alanrogers\tools\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\errors\InvalidFieldException;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\db\Schema;

/**
 * This field allows a selection from a configured set of sections.
 * @see https://github.com/charliedevelopment/craft3-section-field
 * @license https://github.com/charliedevelopment/craft3-section-field/blob/master/LICENSE.md
 */
class SectionField extends Field implements PreviewableFieldInterface
{
    /**
     * @var bool Whether the field allows multiple selections.
     */
    public bool $allowMultiple = false;

    /**
     * @var array What sections have been whitelisted as selectable for this field.
     */
    public array $whitelistedSections = [];

    /**
     * @inheritdoc
     * @see craft\base\ComponentInterface
     */
    public static function displayName(): string
    {
        return 'Section';
    }

    /**
     * @inheritdoc
     * @see craft\base\Field
     */
    public static function hasContentColumn(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @see craft\base\Field
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     * @see craft\base\SavableComponentInterface
     */
    public function getSettingsHtml(): string
    {
        try {
            return Craft::$app->getView()->renderTemplate(
                '_ar-tools/section-field/_settings',
                [
                    'field' => $this,
                    'sections' => $this->getSections()
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError|Exception $e) {
            throw new \Exception('Error rendering section field settings.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     * @see craft\base\Field
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['whitelistedSections'], 'validateSectionWhitelist'];
        return $rules;
    }

    /**
     * Ensures the section IDs selected for the whitelist are for valid sections.
     * @param string $attribute The name of the attribute being validated.
     * @return void
     */
    public function validateSectionWhitelist(string $attribute): void
    {
        $sections = $this->getSections();

        foreach ($this->whitelistedSections as $section) {
            if (!isset($sections[$section])) {
                $this->addError($attribute, 'Invalid section selected.');
            }
        }
    }

    /**
     * @inheritdoc
     * @throws Exception
     * @see craft\base\Field
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $sections = $this->getSections(); // Get all sections available to the current user.
        $whitelist = array_flip($this->whitelistedSections); // Get all whitelisted sections.
        $whitelist[''] = true; // Add a blank entry in, in case the field's options allow a 'None' selection.

        // required state is handled by the field layout's custom field
        $custom_fields = [];
        if ($element) {
            $custom_fields = array_filter(
                $element->getFieldLayout()->getCustomFieldElements(),
                fn (CustomField $custom_field) => $custom_field->attribute() === $this->handle
            );
        }

        if (!$this->allowMultiple && (!$custom_fields || $custom_fields[0]->required)) { // Add a 'None' option specifically for optional, single value fields.
            $sections = [ '' => 'None' ] + $sections;
        }
        $whitelist = array_intersect_key($sections, $whitelist); // Discard any sections not available within the whitelist.

        try {
            return Craft::$app->getView()->renderTemplate(
                '_ar-tools/section-field/_input', [
                    'field' => $this,
                    'value' => $value,
                    'sections' => $whitelist,
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError|Exception $e) {
            throw new Exception('Error rendering input HTML for section field.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     * @see craft\base\Field
     */
    public function getElementValidationRules(): array
    {
        return [
            ['validateSections'],
        ];
    }

    /**
     * Ensures the section IDs selected are available to the current user.
     * @param ElementInterface $element The element with the value being validated.
     * @return void
     * @throws InvalidFieldException
     */
    public function validateSections(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        if (!is_array($value)) {
            $value = [$value];
        }

        $sections = $this->getSections();

        foreach ($value as $section) {
            if (!isset($sections[$section])) {
                $element->addError($this->handle, 'Invalid section selected.');
            }
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null) : mixed
    {
        // Convert string representation from db into plain array/int.
        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        if (is_int($value)
            && $this->allowMultiple) {
            // Int, but field allows multiple, convert to array.
            $value = [$value];
        } else if (is_array($value)
            && !$this->allowMultiple
            && count($value) == 1) {
            // Array, but field allows only one, if single value, convert.
            $value = intval($value[0]);
        }

        // Convert string IDs to integers (for pre 1.1.0 data).
        if (is_array($value)) {
            foreach ($value as $key => $id) {
                $value[$key] = intval($id);
            }
        }

        return $value;
    }

    /**
     * @param $value
     * @param ElementInterface|null $element
     * @return string
     */
    public function serializeValue($value, ElementInterface $element = null): string
    {
        // Convert string IDs to integers for storage.
        if (is_array($value)) {
            foreach ($value as $key => $id) {
                $value[$key] = intval($id);
            }
        }

        return Json::encode($value);
    }

    /**
     * Retrieves all sections in an id-name pair, suitable for the underlying options display.
     */
    private function getSections(): array
    {
        $sections = array();
        foreach (Craft::$app->getSections()->getEditableSections() as $section) {
            $sections[$section->id] = $section->name;
        }
        return $sections;
    }
}
