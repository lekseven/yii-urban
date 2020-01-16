<?php


namespace console\controllers;


use console\models\UrbanSource;
use console\models\UrbanSourceType;
use yii\web\NotFoundHttpException;

trait SourceArguments
{
    public ?array $sourceId = [];
    
    public ?array $sourceUrl = [];
    
    public function options($actionID): array
    {
        return [
            'sourceId',
            'sourceUrl',
        ];
    }
    
    public function optionAliases(): array
    {
        return [
            'i' => 'sourceId',
            'u' => 'sourceUrl',
        ];
    }
    
    /**
     * @param string|null $sourceTypeName
     * @return UrbanSource[]
     * @throws NotFoundHttpException
     */
    private function fetchSources(?string $sourceTypeName = null): array
    {
        $urbanSources = [];
        
        if ($this->sourceId) {
            $urbanSources = UrbanSource::findAll(['id' => $this->sourceId]);
        } elseif ($this->sourceUrl) {
            $urbanSources = UrbanSource::findAll(['url' => $this->sourceUrl]);
        } elseif ($sourceTypeName) {
            $sourceType = UrbanSourceType::findOne(['name' => $sourceTypeName]);
            if (!$sourceType) {
                throw new NotFoundHttpException();
            }
            
            $urbanSources = UrbanSource::findAll([
                'urban_source_type_id' => $sourceType->id,
                'active' => 1,
            ]);
        }
    
        return $urbanSources;
    }
}
