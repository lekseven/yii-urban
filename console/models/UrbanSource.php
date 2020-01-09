<?php

namespace console\models;

use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "wp_urban_source".
 *
 * @property int $id
 * @property string $url
 * @property string|null $latest_record
 * @property int $urban_source_type_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property UrbanSourceType $urbanSourceType
 */
class UrbanSource extends \yii\db\ActiveRecord
{
    // Кол-во дней, в рамках которых выполняется поиск новых записей
    const MIN_DATE = 5;
    
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
            [['url', 'latest_record'], 'string', 'max' => 255],
            ['url', 'url', 'defaultScheme' => 'http'],
            [['urban_source_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => UrbanSourceType::class,
             'targetAttribute' => ['urban_source_type_id' => 'id']],
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
    
    public function updateLatestRecord(int $date): void
    {
        $latestRecordTimestamp = (int) $this->latest_record;
        if ($latestRecordTimestamp < $date) {
            $this->latest_record = (string) $date;
        }
        $this->save();
    }
}
