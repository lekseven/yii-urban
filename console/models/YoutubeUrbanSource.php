<?php

namespace console\models;

class YoutubeUrbanSource extends UrbanSource
{
    const SOURCE_TYPE = 'youtube';
    
    public function getLastVideoId(): ?string
    {
        return $this->latest_record;
    }
}
