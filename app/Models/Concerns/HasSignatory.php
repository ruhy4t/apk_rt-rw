<?php

namespace App\Models\Concerns;

trait HasSignatory
{
    public function initializeHasSignatory(): void
    {
        $this->mergeCasts(['signatory_data' => 'encrypted:array']);
        $this->mergeHidden(['signatory_data']);
    }
}
