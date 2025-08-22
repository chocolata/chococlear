<?php namespace Chocolata\ChocoClear;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'chocolata.chococlear::lang.plugin.name',
            'description' => 'chocolata.chococlear::lang.plugin.description',
            'author'      => 'Chocolata',
            'icon'        => 'icon-trash',
        ];
    }

    public function registerReportWidgets()
    {
        return [
            'Chocolata\ChocoClear\ReportWidgets\ClearCache' => [
                'label'   => 'Clear Cache',
                'context' => 'dashboard'
            ],
            'Chocolata\ChocoClear\ReportWidgets\PurgeFiles' => [
                'label'   => 'Purge Files',
                'context' => 'dashboard'
            ]
        ];
    }
}
