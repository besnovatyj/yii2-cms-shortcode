<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\forms\search;

use Besnovatyj\Shortcode\entities\Shortcode;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class ShortcodeSearch extends Model
{
    public $id;
    public $shortcode;
    public $type;
    public $replacement;

    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['shortcode', 'type', 'replacement'], 'safe'],
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search(array $params): ActiveDataProvider
    {
        $query = Shortcode::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_ASC]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'type' => $this->type,
        ]);

        $query
            ->andFilterWhere(['like', 'shortcode', $this->shortcode])
            ->andFilterWhere(['like', 'replacement', $this->replacement]);
        return $dataProvider;
    }

    public static function typesList(): array
    {
        return [
            Shortcode::TYPE_TEXT => Shortcode::TYPE_TEXT,
            Shortcode::TYPE_WIDGET => Shortcode::TYPE_WIDGET,
        ];
    }
}
