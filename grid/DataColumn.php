<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\grid;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;

/**
 * DataColumn is the default column type for the [[GridView]] widget.
 *
 * It is used to show data columns and allows sorting them.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DataColumn extends Column
{
	/**
	 * @var string the attribute name associated with this column. When neither [[content]] nor [[value]]
	 * is specified, the value of the specified attribute will be retrieved from each data model and displayed.
	 *
	 * Also, if [[header]] is not specified, the label associated with the attribute will be displayed.
	 */
	public $attribute;
	/**
	 * @var string label to be displayed in the [[header|header cell]] and also to be used as the sorting
	 * link label when sorting is enabled for this column.
	 * If it is not set and the models provided by the GridViews data provider are instances
	 * of [[\yii\db\ActiveRecord]], the label will be determined using [[\yii\db\ActiveRecord::getAttributeLabel()]].
	 * Otherwise [[\yii\helpers\Inflector::camel2words()]] will be used to get a label.
	 */
	public $label;
	/**
	 * @var string|\Closure the attribute name to be displayed in this column or an anonymous function that returns 
	 * the value to be displayed for every data model.
	 * The signature of this function is `function ($model, $index, $widget)`.
	 * If this is not set, `$model[$attribute]` will be used to obtain the value.
	 */
	public $value;
	/**
	 * @var string|array in which format should the value of each data model be displayed as (e.g. "raw", "text", "html",
	 * ['date', 'Y-m-d']). Supported formats are determined by the [[GridView::formatter|formatter]] used by
	 * the [[GridView]]. Default format is "text" which will format the value as an HTML-encoded plain text when
	 * [[\yii\base\Formatter::format()]] or [[\yii\i18n\Formatter::format()]] is used.
	 */
	public $format = 'text';
	/**
	 * @var boolean whether to allow sorting by this column. If true and [[attribute]] is found in
	 * the sort definition of [[GridView::dataProvider]], then the header cell of this column
	 * will contain a link that may trigger the sorting when being clicked.
	 */
	public $enableSorting = true;
	/**
	 * @var array the HTML attributes for the link tag in the header cell
	 * generated by [[\yii\data\Sort::link]] when sorting is enabled for this column.
	 */
	public $sortLinkOptions = [];
	/**
	 * @var string|array|boolean the HTML code representing a filter input (e.g. a text field, a dropdown list)
	 * that is used for this data column. This property is effective only when [[GridView::filterModel]] is set.
	 *
	 * - If this property is not set, a text field will be generated as the filter input;
	 * - If this property is an array, a dropdown list will be generated that uses this property value as
	 *   the list options.
	 * - If you don't want a filter for this data column, set this value to be false.
	 */
	public $filter;
	/**
	 * @var array the HTML attributes for the filter input fields. This property is used in combination with
	 * the [[filter]] property. When [[filter]] is not set or is an array, this property will be used to
	 * render the HTML attributes for the generated filter input fields.
	 */
	public $filterInputOptions = ['class' => 'form-control', 'id' => null];


	protected function renderHeaderCellContent()
	{
		if ($this->header !== null || $this->label === null && $this->attribute === null) {
			return parent::renderHeaderCellContent();
		}

		$provider = $this->grid->dataProvider;

		if ($this->label === null) {
			if ($provider instanceof ActiveDataProvider && $provider->query instanceof ActiveQueryInterface) {
				/** @var Model $model */
				$model = new $provider->query->modelClass;
				$label = $model->getAttributeLabel($this->attribute);
			} else {
				$models = $provider->getModels();
				if (($model = reset($models)) instanceof Model) {
					/** @var Model $model */
					$label = $model->getAttributeLabel($this->attribute);
				} else {
					$label = Inflector::camel2words($this->attribute);
				}
			}
		} else {
			$label = $this->label;
		}

		if ($this->attribute !== null && $this->enableSorting &&
			($sort = $provider->getSort()) !== false && $sort->hasAttribute($this->attribute)) {

			return $sort->link($this->attribute, array_merge($this->sortLinkOptions, ['label' => Html::encode($label)]));
		} else {
			return Html::encode($label);
		}
	}

	protected function renderFilterCellContent()
	{
		if (is_string($this->filter)) {
			return $this->filter;
		} elseif ($this->filter !== false && $this->grid->filterModel instanceof Model &&
				  $this->attribute !== null && $this->grid->filterModel->isAttributeActive($this->attribute))
		{
			if (is_array($this->filter)) {
				$options = array_merge(['prompt' => ''], $this->filterInputOptions);
				return Html::activeDropDownList($this->grid->filterModel, $this->attribute, $this->filter, $options);
			} else {
				return Html::activeTextInput($this->grid->filterModel, $this->attribute, $this->filterInputOptions);
			}
		} else {
			return parent::renderFilterCellContent();
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function getDataCellContent($model, $key, $index)
	{
		if ($this->value !== null) {
			if (is_string($this->value)) {
				$value = ArrayHelper::getValue($model, $this->value);
			} else {
				$value = call_user_func($this->value, $model, $index, $this);
			}
		} elseif ($this->content === null && $this->attribute !== null) {
			$value = ArrayHelper::getValue($model, $this->attribute);
		} else {
			return parent::getDataCellContent($model, $key, $index);
		}
		return $value;
	}

	/**
	 * @inheritdoc
	 */
	protected function renderDataCellContent($model, $key, $index)
	{
		return $this->grid->formatter->format($this->getDataCellContent($model, $key, $index), $this->format);
	}
}
