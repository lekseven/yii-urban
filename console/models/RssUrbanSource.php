<?php

namespace console\models;

use yii\web\NotFoundHttpException;

class RssUrbanSource extends UrbanSource
{
    const SOURCE_TYPE = 'rss';
    
    /**
     * @return \yii\db\ActiveQuery
     * @throws NotFoundHttpException
     */
    public static function find()
    {
        $sourceType = UrbanSourceType::findOne(['name' => self::SOURCE_TYPE]);
        if (!$sourceType) {
            throw new NotFoundHttpException('Source type ' . self::SOURCE_TYPE . ' not found in DB');
        }
        
        return parent::find()->where([
            'urban_source_type_id' => $sourceType->id,
            'active' => 1,
        ]);
    }
    
    /**
     * @throws \FeedException
     */
    public function fetch()
    {
        $rss = \Feed::loadRss($this->url);
    
        foreach ($rss->item as $item) {
            echo 'Title: ', $item->title;
            echo 'Link: ', $item->link;
            echo 'Timestamp: ', $item->timestamp;
            echo 'Description ', $item->description;
            echo 'HTML encoded content: ', $item->{'content:encoded'};
        }
    }
}
