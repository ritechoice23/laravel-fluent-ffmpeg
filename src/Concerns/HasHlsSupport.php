<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Exporters\HlsExporter;

trait HasHlsSupport
{
    /**
     * Start an HLS export
     */
    public function exportForHLS(): HlsExporter
    {
        return new HlsExporter($this);
    }
}
