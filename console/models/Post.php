<?php

namespace console\models;

use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * This is the model class for table "wp_posts".
 *
 * @property int $ID
 * @property int $post_author
 * @property string $post_date
 * @property string $post_date_gmt
 * @property string $post_content
 * @property string $post_title
 * @property string $post_excerpt
 * @property string $post_status
 * @property string $comment_status
 * @property string $ping_status
 * @property string $post_password
 * @property string $post_name
 * @property string $to_ping
 * @property string $pinged
 * @property string $post_modified
 * @property string $post_modified_gmt
 * @property string $post_content_filtered
 * @property int $post_parent
 * @property string $guid
 * @property int $menu_order
 * @property string $post_type
 * @property string $post_mime_type
 * @property int $comment_count
 */
class Post extends \yii\db\ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    
    const TITLE_LENGTH = 54;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wp_posts';
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['post_title', 'post_content', 'post_date'], 'required'],
            
            [['post_author', 'post_parent', 'menu_order', 'comment_count'], 'integer'],
            
            [['post_date'], 'datetime', 'format' => \Yii::$app->formatter->datetimeFormat],
            
            [['to_ping', 'pinged', 'post_content_filtered', 'post_excerpt', 'post_title'], 'default', 'value' => ''],
            [['post_status'], 'default', 'value' => self::STATUS_DRAFT],
            
            [['post_content', 'post_title', 'post_excerpt', 'to_ping', 'pinged', 'post_content_filtered'], 'string'],
            [['post_status', 'comment_status', 'ping_status', 'post_type'], 'string', 'max' => 20],
            [['post_password', 'guid'], 'string', 'max' => 255],
            [['post_name'], 'string', 'max' => 200],
            [['post_mime_type'], 'string', 'max' => 100],
        ];
    }
    
    public function beforeSave($insert)
    {
        $this->post_date_gmt = $this->post_date;
    
        $this->post_modified = \Yii::$app->formatter->asDatetime(time());
        $this->post_modified_gmt = $this->post_modified;
        
        return parent::beforeSave($insert);
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ID' => 'ID',
            'post_author' => 'Post Author',
            'post_date' => 'Post Date',
            'post_date_gmt' => 'Post Date Gmt',
            'post_content' => 'Post Content',
            'post_title' => 'Post Title',
            'post_excerpt' => 'Post Excerpt',
            'post_status' => 'Post Status',
            'comment_status' => 'Comment Status',
            'ping_status' => 'Ping Status',
            'post_password' => 'Post Password',
            'post_name' => 'Post Name',
            'to_ping' => 'To Ping',
            'pinged' => 'Pinged',
            'post_modified' => 'Post Modified',
            'post_modified_gmt' => 'Post Modified Gmt',
            'post_content_filtered' => 'Post Content Filtered',
            'post_parent' => 'Post Parent',
            'guid' => 'Guid',
            'menu_order' => 'Menu Order',
            'post_type' => 'Post Type',
            'post_mime_type' => 'Post Mime Type',
            'comment_count' => 'Comment Count',
        ];
    }
    
    /**
     * @param TermTaxonomy|string $tag
     * @throws Exception
     * @throws \yii\base\Exception
     */
    public function addTag($tag): void
    {
        if ($tag instanceof TermTaxonomy) {
            $termTaxonomy = $tag;
        } elseif (is_string($tag)) {
            $termTaxonomy = TermTaxonomy::findOrCreate($tag);
        } else {
            throw new InvalidArgumentException();
        }
    
        $termRelationship = new TermRelationship();
        $termRelationship->object_id = $this->ID;
        $termRelationship->term_taxonomy_id = $termTaxonomy->term_taxonomy_id;
        if (!$termRelationship->save()) {
            throw new Exception();
        }
    }
    
    public function setTitle(string $title): void
    {
        $this->post_title = mb_substr($title, 0, self::TITLE_LENGTH);
    }
    
    public function setContent(string $content): void
    {
        $this->post_content = trim($content);
    }
    
    /**
     * @param string|int $date
     * @throws \yii\base\InvalidConfigException
     */
    public function setDate($date): void
    {
        if (is_int($date)) {
            $this->post_date = \Yii::$app->formatter->asDatetime($date);
        } else {
            $this->post_date = $date;
        }
    }
    
    public function addLink(string $url): void
    {
        $url = trim($url);
        if ($url) {
            $this->post_content .= "\n\n" . $url;
        }
    }
}
