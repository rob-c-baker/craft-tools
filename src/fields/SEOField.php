<?php declare(strict_types=1);

namespace alanrogers\tools\fields;

use alanrogers\tools\models\SEOFieldModel;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Html;
use craft\helpers\Json;
use yii\db\Schema;

class SEOField extends Field
{

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'SEO Fields';
    }

    public function getContentColumnType(): string
    {
        return Schema::TYPE_JSON;
    }

    public function normalizeValue($value, ElementInterface $element = null): SEOFieldModel
    {
        $model = new SEOFieldModel();
        if (is_array($value)) {
            $model->setAttributes($value);
        } elseif ($value instanceof SEOFieldModel) {
            $model = $value;
        } elseif ($value) {
            $model->setAttributes(Json::decodeIfJson($value));
        }
        return $model;
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $id = Html::id($this->handle);
        $namespace = Craft::$app->getView()->namespaceInputId($id);

        return Craft::$app->getView()->renderTemplate(
            '_ar-tools/seo-field/input',
            [
                'element' => $element,
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespace,
            ]
        );
    }
}