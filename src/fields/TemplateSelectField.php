<?php

namespace alanrogers\tools\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\db\Schema;

class TemplateSelectField extends Field
{
    /**
     * @var string
     */
    public string $limitToSubfolder = '';

    /**
     * @inheritdoc
     */
    public static function displayName() : string
    {
        return 'Template Select';
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType() : string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml ()
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'alanrogers-tools/template-select-field/components/fields/_settings.twig',
            [
                'field' => $this,
            ]
        );
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function getInputHtml ($value, ElementInterface $element = null): string
    {
        // Get site templates path
        $templatesPath = $siteTemplatesPath = Craft::$app->path->getSiteTemplatesPath();

        $limitToSubfolder = $this->limitToSubfolder;

        if ( !empty($limitToSubfolder) ) {
            $templatesPath = $templatesPath . DIRECTORY_SEPARATOR . ltrim(rtrim($limitToSubfolder, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        // Normalize the path, so it also works as intended in Windows
        $templatesPath = FileHelper::normalizePath($templatesPath);

        // Check if folder exists, or give error
        if ( !file_exists($templatesPath) ) {
            throw new \InvalidArgumentException('(Template Select) Folder doesn\'t exist: ' . $templatesPath);
        }

        // Get folder contents
        $templates = FileHelper::findFiles($templatesPath, [
            'only' => [ '*.twig' ],
            'caseSensitive' => false,
        ]);

        // Add placeholder for when there is no template selected
        $filteredTemplates = [ '' => 'No template selected' ];

        // Iterate over template list
        foreach ($templates as $path) {
            $path = FileHelper::normalizePath($path);
            $pathWithoutBase = str_replace($templatesPath, '', $path);

            $filenameIncludingSubfolder = ltrim($pathWithoutBase, DIRECTORY_SEPARATOR);

            $filteredTemplates[ $filenameIncludingSubfolder ] = $filenameIncludingSubfolder;
        }

        // Sort filtered templates alphabetically, maintaining index -> value association
        asort($filteredTemplates);

        // Get our id and namespace
        $id = Html::id($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Render the input template
        try {
            return Craft::$app->getView()->renderTemplate(
                'alanrogers-tools/template-select-field/components/fields/_input.twig',
                [
                    'name' => $this->handle,
                    'value' => $value,
                    'field' => $this,
                    'id' => $id,
                    'namespacedId' => $namespacedId,
                    'templates' => $filteredTemplates,
                ]
            );
        } catch (LoaderError|Exception|SyntaxError|RuntimeError $e) {
            throw new \Exception('Error occurred during renering template-select template.', 0, $e);
        }
    }
}