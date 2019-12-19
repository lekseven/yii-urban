<?php

namespace app\models;

use console\models\UrbanSourceType;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "wp_urban_source".
 *
 * @property int $id
 * @property string $url
 * @property string $short_name
 * @property string|null $latest_record
 * @property int $urban_source_type_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property UrbanSource $urbanSourceType
 */
class UrbanSource extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wp_urban_source';
    }
    
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['url', 'urban_source_type_id'], 'required'],
            [['urban_source_type_id'], 'integer'],
            [['url', 'latest_record', 'short_name'], 'string', 'max' => 255],
            [['urban_source_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => UrbanSourceType::class, 'targetAttribute' => ['urban_source_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'Url',
            'short_name' => 'Short Name',
            'latest_record' => 'Latest Record',
            'urban_source_type_id' => 'Urban Source Type ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUrbanSourceType()
    {
        return $this->hasOne(UrbanSourceType::class, ['id' => 'urban_source_type_id']);
    }
    
    public function fields()
    {
        return parent::fields();
    }
}
