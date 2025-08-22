<?php namespace Chocolata\ChocoClear\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Artisan;
use Flash;
use Lang;
use Chocolata\ChocoClear\Classes\SizeHelper;
use System\Models\File as FileModel;

class PurgeFiles extends ReportWidgetBase
{
    const THUMBS_PATH       = '/app/uploads/public';
    const THUMBS_REGEX      = '/^thumb_.*/';
    const RESIZER_PATH      = '/app/resources/resize';
    const TEMP_FOLDER_PATH  = '/temp';

    const UPLOADS_PATH      = '/app/uploads';

    protected $defaultAlias = 'chocolata_purge_files';

    public function render(){
        $this->vars['size'] = $this->getSizes();
        $this->vars['radius'] = $this->property("radius");
        $widget = ($this->property("nochart"))? 'widget2' : 'widget';
        return $this->makePartial($widget);
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'Purge Files',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error'
            ],
            'nochart' => [
                'title'             => 'chocolata.chococlear::lang.plugin.nochart',
                'type'              => 'checkbox',
            ],
            'radius' => [
                'title'             => 'chocolata.chococlear::lang.plugin.radius',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Only numbers!',
                'default'           => '200',
            ],
            'purge_thumbs' => [
                'title'             => 'chocolata.chococlear::lang.plugin.purge_thumbs',
                'type'              => 'checkbox',
                'default'           => true,
            ],
            'purge_resizer' => [
                'title'             => 'chocolata.chococlear::lang.plugin.purge_resizer',
                'type'              => 'checkbox',
                'default'           => true,
            ],
            'purge_uploads' => [
                'title'             => 'chocolata.chococlear::lang.plugin.purge_uploads',
                'type'              => 'checkbox',
                'default'           => false,
            ],
            'purge_orphans' => [
                'title'             => 'chocolata.chococlear::lang.plugin.purge_orphans',
                'type'              => 'checkbox',
                'default'           => false,
            ],
            'purge_temp_folder' => [
                'title'             => 'chocolata.chococlear::lang.plugin.purge_temp_folder',
                'type'              => 'checkbox',
                'default'           => false,
            ]
        ];
    }

    public function onClear(){
        Artisan::call('cache:clear');
        if ($this->property("purge_thumbs")) {
            Artisan::call("october:util", [
                'name' => 'purge thumbs',
                '--force' => true,
                '--no-interaction' => true,
            ]);
        }
        if($this->property("purge_resizer")){
            Artisan::call('october:util', [
                'name' => 'purge resizer',
                '--force' => true,
                '--no-interaction' => true,
            ]);
        }
        if($this->property("purge_uploads")){
            Artisan::call('october:util', [
                'name' => 'purge uploads',
                '--force' => true,
                '--no-interaction' => true,
            ]);
        }
        if($this->property("purge_orphans")){
            Artisan::call('october:util', [
                'name' => 'purge orphans',
                '--force' => true,
                '--no-interaction' => true,
            ]);
        }
        if($this->property('purge_temp_folder')){
            $path = storage_path() . self::TEMP_FOLDER_PATH;
            if (\File::isDirectory($path)) {
                \File::cleanDirectory($path);
            }
        }

        Flash::success(Lang::get('chocolata.chococlear::lang.plugin.success'));
        $widget = ($this->property("nochart"))? 'widget2' : 'widget';
        return [
            'partial' => $this->makePartial(
                $widget,
                [
                    'size'   => $this->getSizes(),
                    'radius' => $this->property("radius")
                ]
            )
        ];
    }

    private function getSizes(){
        $s['thumbs_b'] = SizeHelper::dirSize(
            storage_path() . self::THUMBS_PATH,
            false,
            self::THUMBS_REGEX,
            false
        );
        $s['thumbs']        = SizeHelper::formatSize($s['thumbs_b']);
        $s['resizer_b']     = SizeHelper::dirSize(storage_path() . self::RESIZER_PATH);
        $s['resizer']       = SizeHelper::formatSize($s['resizer_b']);
        $s['uploads_b']     = $this->purgeableUploadsBytes((storage_path() . self::UPLOADS_PATH).'/public') +
                              $this->purgeableUploadsBytes((storage_path() . self::UPLOADS_PATH).'/protected');
        $s['uploads']       = SizeHelper::formatSize($s['uploads_b']);
        $s['orphans_b']     = $this->orphanedFilesBytes();
        $s['orphans']       = SizeHelper::formatSize($s['orphans_b']);
        $s['temp_folder_b'] = SizeHelper::dirSize(storage_path() . self::TEMP_FOLDER_PATH);
        $s['temp_folder']   = SizeHelper::formatSize($s['temp_folder_b']);

        $s['all']         = SizeHelper::formatSize($s['thumbs_b'] + $s['resizer_b'] + $s['temp_folder_b'] + $s['uploads_b'] + $s['orphans_b']);
        return $s;
    }



    private function purgeableUploadsBytes(string $localPath): int
    {
        if (!is_dir($localPath)) {
            return 0;
        }

        $total = 0;

        // Grotere chunks zijn efficiënter; pas aan naar smaak
        $chunks = collect(\File::allFiles($localPath))->chunk(500);

        foreach ($chunks as $chunk) {
            // verzamel bestandsnamen (disk_name)
            $names = [];
            foreach ($chunk as $file) {
                $names[] = $file->getFilename();
            }

            if (!$names) {
                continue;
            }

            // bekende disk_names in DB ophalen en omzetten naar set voor O(1) lookup
            $present = array_flip(
                FileModel::whereIn('disk_name', $names)->pluck('disk_name')->all()
            );

            foreach ($chunk as $file) {
                $name = $file->getFilename();

                if (!isset($present[$name])) {
                    try {
                        $total += $file->getSize();
                    } catch (\Throwable $e) {
                        // onleesbaar / race condition -> overslaan
                    }
                }
            }
        }

        return $total;
    }


    private function orphanedFilesBytes(): int
    {
        $total = 0;

        FileModel::whereNull('attachment_id')
            ->chunkById(1000, function ($files) use (&$total) {
                foreach ($files as $file) {
                    try {
                        // Bepaal disk + pad zoals October het bewaart
                        $disk = $file->disk ?: 'local';
                        $path = method_exists($file, 'getDiskPath') ? $file->getDiskPath() : null;

                        if ($path && \Storage::disk($disk)->exists($path)) {
                            // Neem de DB-kolom file_size (snel) maar tel alleen als het bestand echt bestaat
                            $total += (int) $file->file_size;
                        }
                    } catch (\Throwable $e) {
                        // overslaan bij race conditions / onleesbare disks
                    }
                }
            });

        return $total;
    }

}
