<?php

namespace console\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "wp_term_taxonomy".
 *
 * @property int $term_taxonomy_id
 * @property int $term_id
 * @property string $taxonomy
 * @property string $description
 * @property int $parent
 * @property int $count
 *
 * @property Term $term
 */
class TermTaxonomy extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wp_term_taxonomy';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['term_id', 'taxonomy'], 'required'],
            [['term_id', 'parent', 'count'], 'integer'],
            [['description'], 'string'],
            [['description'], 'default', 'value' => ''],
            [['taxonomy'], 'string', 'max' => 32],
            [['term_id', 'taxonomy'], 'unique', 'targetAttribute' => ['term_id', 'taxonomy']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'term_taxonomy_id' => 'Term Taxonomy ID',
            'term_id' => 'Term ID',
            'taxonomy' => 'Taxonomy',
            'description' => 'Description',
            'parent' => 'Parent',
            'count' => 'Count',
        ];
    }
    
    public function getTerm(): ActiveQuery
    {
        return $this->hasOne(Term::class, ['term_id' => 'term_id']);
    }
}
