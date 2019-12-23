<?php

namespace app\models;

use console\models\TermTaxonomy;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "wp_term_relationships".
 *
 * @property int $object_id
 * @property int $term_taxonomy_id
 * @property int $term_order
 *
 * @property TermTaxonomy $termTaxonomy
 */
class TermRelationship extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wp_term_relationships';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_id', 'term_taxonomy_id'], 'required'],
            [['object_id', 'term_taxonomy_id', 'term_order'], 'integer'],
            [['object_id', 'term_taxonomy_id'], 'unique', 'targetAttribute' => ['object_id', 'term_taxonomy_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'object_id' => 'Object ID',
            'term_taxonomy_id' => 'Term Taxonomy ID',
            'term_order' => 'Term Order',
        ];
    }
    
    public function getTermTaxonomy(): ActiveQuery
    {
        return $this->hasOne(TermTaxonomy::class, ['term_taxonomy_id' => 'term_taxonomy_id']);
    }
}
