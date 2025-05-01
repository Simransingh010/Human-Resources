<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithPagination;

class BulkEditor extends Component
{
    use WithPagination;

    public string $modelClass;
    public int    $perPage = 10;

    // Just field names you want to edit
    public array $fields = [];

    // key => [id => label] for each select
    public array $listsForFields = [];

    // [ recordId => [ fieldName => value ] ]
    public array $updateData = [];
    public string $labelHeader;
    public array  $labelFields = [];

    public function mount(
        string $modelClass,
        array  $fields,
        array  $listsForFields,
        string $labelHeader,
        array  $labelFields
    ) {
        $this->modelClass     = $modelClass;
        $this->fields         = $fields;
        $this->listsForFields = $listsForFields;

        $this->labelHeader = $labelHeader;
        $this->labelFields = $labelFields;
    }

    // Lazy‐loaded paginator
    public function getItemsProperty()
    {
        return ($this->modelClass)::paginate($this->perPage);
    }

    // Prepopulate updateData every time items change
    public function hydrateItems()
    {
        foreach ($this->items as $item) {
            $this->updateData[$item->id] = array_intersect_key(
                $item->toArray(),
                array_flip($this->fields)
            );
        }
    }

    public function saveField(int $id, string $field)
    {
        $model = ($this->modelClass)::findOrFail($id);
        $model->{$field} = $this->updateData[$id][$field] ?? null;
        $model->save();
    }

    public function render()
    {
        return view('livewire.panel.bulk-editor');
    }
}
