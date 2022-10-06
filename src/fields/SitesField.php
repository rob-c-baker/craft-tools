<?php
declare(strict_types=1);

namespace alanrogers\tools\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\InvalidFieldException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\db\Schema;

/**
 * @see https://github.com/tonioseiler/propagated-sites-field-plugin
 * @license https://github.com/tonioseiler/propagated-sites-field-plugin/blob/master/LICENSE.md
 */
class SitesField extends Field implements PreviewableFieldInterface
{
    /**
     * @var bool Wether or not the entry should be propagated to the sites selected in the sites field.
     */
    public bool $propagate = false;

    /**
     * @var array What sites have been whitelisted as selectable for this field.
     */
    public array $whitelistedSites = [];

    /**
     * used for backward compatibility
     */
    public bool $allowMultiple = false;

    /**
     * @inheritdoc
     * @see \craft\base\ComponentInterface
     */
    public static function displayName(): string
    {
        return 'Sites Field';
    }

    /**
     * @inheritdoc
     * @see \craft\base\Field
     */
    public static function hasContentColumn(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @see \craft\base\Field
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     * @throws Exception
     * @see craft\base\SavableComponentInterface
     */
    public function getSettingsHtml(): string
    {
        try {
            return Craft::$app->getView()->renderTemplate(
                'alanrogers-tools/sites-field/_settings.twig',
                [
                    'field' => $this,
                    'sites' => $this->getSites()
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError|Exception $e) {
            throw new Exception('Error rendering sites field settings HTML template.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     * @see craft\base\Field
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['whitelistedSites'], 'validateSitesWhitelist'];

        return $rules;
    }

    /**
     * Ensures the site IDs selected for the whitelist are for valid sites.
     * @param string $attribute The name of the attribute being validated.
     * @return void
     */
    public function validateSitesWhitelist(string $attribute)
    {
        $sites = $this->getSites();

        foreach ($this->whitelistedSites as $site) {
            if (!isset($sites[$site])) {
                $this->addError($attribute, 'Invalid site selected.');
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

        $sites = $this->getSites(); // Get all sites available to the current user.
        $whitelist = array_flip($this->whitelistedSites); // Get all whitelisted sites.
        $whitelist[''] = true; // Add a blank entry in, in case the field's options allow a 'None' selection.

        $whitelist = array_intersect_key($sites, $whitelist); // Discard any sites not available within the whitelist.

        if (empty($element->id) || empty($value)) {
            // By default all need to be switched on
            $value = array_keys($sites);
        }

        try {
            return Craft::$app->getView()->renderTemplate(
                'alanrogers-tools/sites-field/_input.twig',
                [
                    'field' => $this,
                    'value' => $value,
                    'sites' => $whitelist,
                    'currentSiteId' => $element->siteId
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError|Exception $e) {
            throw new Exception('Error rendering sites field input HTML template.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     * @see craft\base\Field
     */
    public function getElementValidationRules(): array
    {
        return [
            ['validateSites'],
        ];
    }

    /**
     * Ensures the site IDs selected are available to the current user.
     * @param ElementInterface $element The element with the value being validated.
     * @return void
     * @throws InvalidFieldException
     */
    public function validateSites(ElementInterface $element)
    {
        $value = $element->getFieldValue($this->handle);
        $sites = $this->getSites();

        if (is_array($value)) {
            foreach ($value as $id) {
                if (!isset($sites[$id])) {
                    $element->addError($this->handle, 'Invalid site selected.');
                }
            }
        } else {
            if (!isset($sites[$value])) {
                $element->addError($this->handle, 'Invalid site selected.');
            }
        }
    }

    /**
     * Retrieves all sites in an id, name pair, suitable for the underlying options display.
     */
    private function getSites() : array
    {
        $sites = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $sites[$site->id] = $site->name;
        }
        return $sites;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null) : array
    {
        if (is_array($value)) {
            return $value;
        }

        return array_map('intval', explode(',', $value));
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ?ElementInterface $element = null) : mixed
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value) : void
    {
        // modify the query when called in template or in PHP

        if ($value !== null) {

            $field_name = 'content.' . Craft::$app->getContent()->fieldColumnPrefix . $this->handle;

            /** @var ElementQuery $query */
            $query->subQuery->andWhere(
                'FIND_IN_SET(:val, ' . $field_name . ')',
                [
                    ':val' => $value
                ]
            );
        }
    }
}