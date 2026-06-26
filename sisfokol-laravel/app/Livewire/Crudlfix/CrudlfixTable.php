<?php

namespace App\Livewire\Crudlfix;

use App\Support\Crudlfix\CrudlfixConfig;
use Livewire\Component;

/**
 * Livewire data table component for Crudlfix.
 *
 * Provides search, sort, filter, pagination, and bulk actions
 * without page reload.
 */
class CrudlfixTable extends Component
{
    use \App\Livewire\Crudlfix\Traits\HasCrudlfixTable;
    use \App\Livewire\Crudlfix\Traits\HasCrudlfixActions;

    public CrudlfixConfig $config;
    public array $viewData = [];
    public array $columns = [];

    public function mount(CrudlfixConfig $config, array $viewData = [], array $columns = []): void
    {
        $this->config = $config;
        $this->viewData = $viewData;
        $this->columns = $columns;
        $this->initTable($config);
    }

    protected function getConfigProperty(): CrudlfixConfig
    {
        return $this->config;
    }

    public function render()
    {
        $rows = $this->getRowsProperty();

        return view('livewire.crudlfix.table', [
            'rows' => $rows,
        ]);
    }
}
