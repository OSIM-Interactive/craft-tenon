<?php
namespace osim\craft\tenon\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\elements\Issue as IssueElement;
use osim\craft\tenon\elements\Page as PageElement;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTable(
            '{{%osim_tenon_accounts}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(250)->notNull(),
                'tenonApiKey' => $this->string(250)->notNull(),
                'certainty' => $this->integer(),
                'priority' => $this->integer(),
                'level' => $this->string(3),
                'store' => $this->boolean(),
                'uaString' => $this->string(250),
                'delay' => $this->integer(),
                'uid' => $this->uid(),
            ]
        );

        $this->createTable(
            '{{%osim_tenon_viewports}}',
            [
                'id' => $this->primaryKey(),
                'accountId' => $this->integer(),
                'name' => $this->string(250)->notNull(),
                'width' => $this->integer()->notNull(),
                'height' => $this->integer()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('accountId', '{{%osim_tenon_viewports}}', 'accountId');

        $this->createTable(
            '{{%osim_tenon_projects}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(250),
                'siteId' => $this->integer()->notNull(),
                'accountId' => $this->integer()->notNull(),
                'tenonProjectId' => $this->string(250),
                'sitemapUrl' => $this->string(250)->notNull(),
                'certainty' => $this->integer(),
                'priority' => $this->integer(),
                'level' => $this->string(3),
                'store' => $this->boolean(),
                'uaString' => $this->string(250),
                'delay' => $this->integer(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('siteId', '{{%osim_tenon_projects}}', 'siteId');
        $this->createIndex('accountId', '{{%osim_tenon_projects}}', 'accountId');

        $this->createTable(
            '{{%osim_tenon_projects_viewports}}',
            [
                'id' => $this->primaryKey(),
                'projectId' => $this->integer()->notNull(),
                'viewportId' => $this->integer()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('projectId', '{{%osim_tenon_projects_viewports}}', 'projectId');
        $this->createIndex('viewportId', '{{%osim_tenon_projects_viewports}}', 'viewportId');

        $this->createTable(
            '{{%osim_tenon_pages}}',
            [
                'id' => $this->primaryKey(),
                'projectId' => $this->integer()->notNull(),
                'pageTitle' => $this->string(250)->notNull(),
                'pageUrl' => $this->string(250)->notNull(),
                'levelAIssues' => $this->integer()->notNull(),
                'levelAaIssues' => $this->integer()->notNull(),
                'levelAaaIssues' => $this->integer()->notNull(),
                'totalIssues' => $this->integer()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('projectId', '{{%osim_tenon_pages}}', 'projectId');
        $this->createIndex('pageUrl', '{{%osim_tenon_pages}}', ['projectId', 'pageUrl'], true);
        $this->createIndex('levelAIssues', '{{%osim_tenon_pages}}', 'levelAIssues');
        $this->createIndex('levelAaIssues', '{{%osim_tenon_pages}}', 'levelAaIssues');
        $this->createIndex('levelAaaIssues', '{{%osim_tenon_pages}}', 'levelAaaIssues');
        $this->createIndex('totalIssues', '{{%osim_tenon_pages}}', 'totalIssues');

        $this->createTable(
            '{{%osim_tenon_issues}}',
            [
                'id' => $this->primaryKey(),
                'pageId' => $this->integer()->notNull(),
                'viewportId' => $this->integer()->notNull(),
                'certainty' => $this->integer()->notNull(),
                'priority' => $this->integer()->notNull(),
                'errorGroupId' => $this->integer()->notNull(),
                'errorGroupTitle' => $this->string(250)->notNull(),
                'errorId' => $this->integer()->notNull(),
                'errorTitle' => $this->string(250)->notNull(),
                'errorDescription' => $this->text()->notNull(),
                'errorSnippet' => $this->text()->notNull(),
                'errorXpath' => $this->text()->notNull(),
                'levelA' => $this->boolean()->notNull(),
                'levelAa' => $this->boolean()->notNull(),
                'levelAaa' => $this->boolean()->notNull(),
                'resolved' => $this->boolean()->notNull()->defaultValue(false),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('pageId', '{{%osim_tenon_issues}}', 'pageId');
        $this->createIndex('viewportId', '{{%osim_tenon_issues}}', 'viewportId');
        $this->createIndex('priority', '{{%osim_tenon_issues}}', 'priority');
        $this->createIndex('certainty', '{{%osim_tenon_issues}}', 'certainty');
        $this->createIndex('errorGroupId', '{{%osim_tenon_issues}}', 'errorGroupId');
        $this->createIndex('errorGroupTitle', '{{%osim_tenon_issues}}', 'errorGroupTitle');
        $this->createIndex('errorId', '{{%osim_tenon_issues}}', 'errorId');
        $this->createIndex('errorTitle', '{{%osim_tenon_issues}}', 'errorTitle');
        $this->createIndex('errorXpath', '{{%osim_tenon_issues}}', 'errorXpath');
        $this->createIndex('levelA', '{{%osim_tenon_issues}}', 'levelA');
        $this->createIndex('levelAa', '{{%osim_tenon_issues}}', 'levelAa');
        $this->createIndex('levelAaa', '{{%osim_tenon_issues}}', 'levelAaa');
        $this->createIndex('resolved', '{{%osim_tenon_issues}}', 'resolved');

        $this->createTable(
            '{{%osim_tenon_ignore_rules}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(250)->notNull(),
                'accountId' => $this->integer(),
                'projectId' => $this->integer(),
                'viewportId' => $this->integer(),
                'pageUrlComparator' => $this->string(32),
                'pageUrlValue' => $this->string(250),
                'errorGroupId' => $this->integer(),
                'errorId' => $this->integer(),
                'errorXpathComparator' => $this->string(32),
                'errorXpathValue' => $this->text(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('accountId', '{{%osim_tenon_ignore_rules}}', 'accountId');
        $this->createIndex('projectId', '{{%osim_tenon_ignore_rules}}', 'projectId');
        $this->createIndex('viewportId', '{{%osim_tenon_ignore_rules}}', 'viewportId');

        $this->createTable(
            '{{%osim_tenon_history}}',
            [
                'id' => $this->primaryKey(),
                'projectId' => $this->integer()->notNull(),
                'viewportId' => $this->integer(),
                'dateJob' => $this->dateTime(),
                'dateLast' => $this->dateTime(),
                'status' => $this->integer(),
            ]
        );
        $this->createIndex('projectId', '{{%osim_tenon_history}}', 'projectId');
        $this->createIndex('viewportId', '{{%osim_tenon_history}}', 'viewportId');

        // Viewports
        $this->addForeignKey(
            'osim_tenon_viewports_account_id',
            '{{%osim_tenon_viewports}}',
            'accountId',
            '{{%osim_tenon_accounts}}',
            'id',
            'CASCADE'
        );

        // Projects
        $this->addForeignKey(
            null,
            '{{%osim_tenon_projects}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_tenon_projects}}',
            'accountId',
            '{{%osim_tenon_accounts}}',
            'id',
            'CASCADE'
        );

        // Projects Viewports
        $this->addForeignKey(
            null,
            '{{%osim_tenon_projects_viewports}}',
            'projectId',
            '{{%osim_tenon_projects}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_tenon_projects_viewports}}',
            'viewportId',
            '{{%osim_tenon_viewports}}',
            'id',
            'CASCADE'
        );

        // Pages
        $this->addForeignKey(
            null,
            '{{%osim_tenon_pages}}',
            'projectId',
            '{{%osim_tenon_projects}}',
            'id',
            'CASCADE'
        );

        // Issues
        $this->addForeignKey(
            null,
            '{{%osim_tenon_issues}}',
            'pageId',
            '{{%osim_tenon_pages}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_tenon_issues}}',
            'viewportId',
            '{{%osim_tenon_viewports}}',
            'id',
            'CASCADE'
        );

        // Ignore rules
        $this->addForeignKey(
            null,
            '{{%osim_tenon_ignore_rules}}',
            'accountId',
            '{{%osim_tenon_accounts}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_tenon_ignore_rules}}',
            'projectId',
            '{{%osim_tenon_projects}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_tenon_ignore_rules}}',
            'viewportId',
            '{{%osim_tenon_viewports}}',
            'id',
            'CASCADE'
        );

        // History
        $this->addForeignKey(
            null,
            '{{%osim_tenon_history}}',
            'projectId',
            '{{%osim_tenon_projects}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_tenon_history}}',
            'viewportId',
            '{{%osim_tenon_viewports}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $tables = [
            '{{%osim_tenon_accounts}}',
            '{{%osim_tenon_viewports}}',
            '{{%osim_tenon_projects}}',
            '{{%osim_tenon_projects_viewports}}',
            '{{%osim_tenon_pages}}',
            '{{%osim_tenon_issues}}',
            '{{%osim_tenon_ignore_rules}}',
            '{{%osim_tenon_history}}',
        ];

        foreach ($tables as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }

            MigrationHelper::dropTable($table, $this);
        }

        // Remove elements rows
        $this->delete('{{%elements}}', ['type' => PageElement::class]);
        $this->delete('{{%elements}}', ['type' => IssueElement::class]);

        // Remove project config
        Craft::$app->projectConfig->remove(Plugin::PROJECT_CONFIG_PATH);
    }
}
