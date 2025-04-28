<?php

namespace Klangch\LaravelIRCaptcha\Console\Commands;

use Illuminate\Console\Command;

class ClearExpiredCaptcha extends Command
{
    protected $signature = 'ir-captcha:clear-expired';

    protected $description = 'Clear expired captcha files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Clearing expired captcha files...');

        ir_captcha()->clearExpiredFiles();

        $this->comment('Done.');
    }
}
