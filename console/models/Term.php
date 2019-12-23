<?php

namespace console\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "wp_terms".
 *
 * @property int $term_id
 * @property string $name
 * @property string $slug
 * @property int $term_group
 *
 * @property TermTaxonomy[] $termTaxonomies
 */
class Term extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wp_terms';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['term_group'], 'integer'],
            [['name', 'slug'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'term_id' => 'Term ID',
            'name' => 'Name',
            'slug' => 'Slug',
            'term_group' => 'Term Group',
        ];
    }
    
    public function beforeSave($insert)
    {
        if (!$this->slug) {
            $this->slug = $this->name;
        }
        
        return parent::beforeSave($insert);
    }
    
    public function getTermTaxonomies(): ActiveQuery
    {
        return $this->hasMany(TermTaxonomy::class, ['term_id' => 'term_id']);
    }
}
