<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%wp_urban_source}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%wp_urban_source}}`
 */
class m191218_225519_create_wp_urban_source_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        
        $this->createTable('{{%wp_urban_source}}', [
            'id' => $this->primaryKey(),
            'url' => $this->string()->notNull()->unique(),
            'short_name' => $this->string()->notNull(),
            'latest_record' => $this->string(),
            'urban_source_type_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // creates index for column `urban_source_type_id`
        $this->createIndex(
            '{{%idx-wp_urban_source-urban_source_type_id}}',
            '{{%wp_urban_source}}',
            'urban_source_type_id'
        );

        // add foreign key for table `{{%wp_urban_source}}`
        $this->addForeignKey(
            '{{%fk-wp_urban_source-urban_source_type_id}}',
            '{{%wp_urban_source}}',
            'urban_source_type_id',
            '{{%wp_urban_source_type}}',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%wp_urban_source}}`
        $this->dropForeignKey(
            '{{%fk-wp_urban_source-urban_source_type_id}}',
            '{{%wp_urban_source}}'
        );

        // drops index for column `urban_source_type_id`
        $this->dropIndex(
            '{{%idx-wp_urban_source-urban_source_type_id}}',
            '{{%wp_urban_source}}'
        );

        $this->dropTable('{{%wp_urban_source}}');
    }
}
