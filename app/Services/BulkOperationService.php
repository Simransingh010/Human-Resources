<?php

namespace App\Services;

use App\Models\Batch;
use Illuminate\Support\Facades\DB;

class BulkOperationService
{
    /**
     * Start a new batch.
     */
    public static function start(string $moduleComponent, string $action = null, string $title = null): Batch
    {
        return Batch::create([
            'firm_id'          => session('firm_id'),
            'user_id'          => auth()->id(),
            'modulecomponent'  => $moduleComponent,
            'action'           => $action,
            'title'            => $title,
        ]);
    }

    public static function logInsert(Batch $batch, $model): void
    {
        $batch->items()->create([
            'operation'  => 'insert',
            'model_type' => get_class($model),
            'model_id'   => $model->getKey(),
            'new_data'   => json_encode($model->getAttributes()),
        ]);
    }

    public static function logUpdate(Batch $batch, $model, array $original): void
    {
        $batch->items()->create([
            'operation'     => 'update',
            'model_type'    => get_class($model),
            'model_id'      => $model->getKey(),
            'original_data' => json_encode($original),
            'new_data'      => json_encode($model->getChanges()),
        ]);
    }

    public static function logDelete(Batch $batch, $model): void
    {
        $batch->items()->create([
            'operation'     => 'delete',
            'model_type'    => get_class($model),
            'model_id'      => $model->getKey(),
            'original_data' => json_encode($model->getAttributes()),
        ]);
    }

    /**
     * Roll back the entire batch.
     */
    public static function rollback(Batch $batch): void
    {
        DB::transaction(function() use ($batch) {
            foreach ($batch->items()->latest('id')->get() as $item) {
                $M = $item->model_type;
                switch ($item->operation) {
                    case 'insert':
                        $M::destroy($item->model_id);
                        break;

                    case 'update':
                        if ($m = $M::find($item->model_id)) {
                            $m->fill($item->original_data)->save();
                        }
                        break;

                    case 'delete':
                        $M::create($item->original_data);
                        break;
                }
            }
            // mark as rolled back for audit
            $batch->update(['action' => "{$batch->action}_rolled_back"]);
        });
    }
}
