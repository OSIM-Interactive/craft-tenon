<?php
namespace osim\craft\tenon;

use Craft;

class Variables
{
    public function name(): string
    {
        $plugin = Plugin::getInstance();
        return Plugin::t($plugin->name);
    }

    public function accounts(): array
    {
        $plugin = Plugin::getInstance();
        return $plugin->getAccounts()->getAllAccounts();
    }

    public function viewports(): array
    {
        $plugin = Plugin::getInstance();
        return $plugin->getViewports()->getAllViewports();
    }

    public function projects(): array
    {
        $plugin = Plugin::getInstance();
        return $plugin->getProjects()->getAllProjects();
    }

    public function pageCount(): int
    {
        $plugin = Plugin::getInstance();
        return $plugin->getPages()->getPageCount();
    }
    public function issueCount(?bool $resolved = null, ?string $level = null): int
    {
        $plugin = Plugin::getInstance();
        return $plugin->getIssues()->getIssueCount($resolved, $level);
    }

    public function accountOptions($emptyOption = null): array
    {
        $plugin = Plugin::getInstance();
        return $plugin->getAccounts()->getAccountOptions($emptyOption);
    }

    public function projectOptions($emptyOption = null): array
    {
        $plugin = Plugin::getInstance();
        return $plugin->getProjects()->getProjectOptions($emptyOption);
    }

    public function viewportOptions($emptyOption = null): array
    {
        $plugin = Plugin::getInstance();
        return $plugin->getViewports()->getViewportOptions($emptyOption);
    }

    public function hasAccounts(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getAccounts()->hasAccounts();
    }
    public function hasIgnoreRules(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getIgnoreRules()->hasIgnoreRules();
    }
    public function hasProjects(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getProjects()->hasProjects();
    }
    public function hasViewports(): bool
    {
        $plugin = Plugin::getInstance();
        return $plugin->getViewports()->hasViewports();
    }

    public function siteOptions($emptyOption = null): array
    {
        $options = [];

        if ($emptyOption !== null) {
            $options[0] = strval($emptyOption);
        }

        foreach (Craft::$app->sites->getAllSites() as $model) {
            $options[$model->id] = $model->name;
        }

        return $options;
    }

    public function displayViewportOptions(): array
    {
        return [
            'full' => Plugin::t('Full: Desktop [1920 × 1080]'),
            'name' => Plugin::t('Name: Desktop'),
            'size' => Plugin::t('Size: 1920 × 1080'),
        ];
    }

    public function displayLevelOptions(): array
    {
        return [
            'full' => Plugin::t('All: AA, AAA'),
            'minimum' => Plugin::t('Minimum: AA'),
        ];
    }

    public function comparatorOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            'exact' => Plugin::t('Exact Match'),
            'contains' => Plugin::t('Contains'),
            'notContains' => Plugin::t('Does Not Contain'),
            'startsWith' => Plugin::t('Starts With'),
            'notStartsWith' => Plugin::t('Does Not Start With'),
            'endsWith' => Plugin::t('Ends With'),
            'notEndsWith' => Plugin::t('Does Not End With'),
        ];

        if ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }

    public function tenonCertaintyOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            '0' => '0',
            '20' => '20',
            '40' => '40',
            '60' => '60',
            '80' => '80',
            '100' => '100'
        ];

        if ($emptyOption === '0' || $emptyOption === 0) {
            $options[''] = '';
            unset($options['0']);
        } elseif ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }
    public function tenonPriorityOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            '0' => '0',
            '20' => '20',
            '40' => '40',
            '60' => '60',
            '80' => '80',
            '100' => '100'
        ];

        if ($emptyOption === '0' || $emptyOption === 0) {
            $options[''] = '';
            unset($options['0']);
        } elseif ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }
    public function tenonLevelOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            'A' => 'A',
            'AA' => 'AA',
            'AAA' => 'AAA',
        ];

        if ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }

    public function tenonStoreOptions($emptyOption = null): array
    {
        $options = [
            '' => $emptyOption,
            '0' => Craft::t('app', 'No'),
            '1' => Craft::t('app', 'Yes'),
        ];

        if ($emptyOption === null) {
            unset($options['']);
        }

        return $options;
    }
}
