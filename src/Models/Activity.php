<?php

namespace Destefano\Guachiman\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'causer_type',
        'causer_id',
        'causer_name',
        'properties',
        'ref_name',
        'ref',
        'sapi_name',
        'ip_address',
        'batch_uuid',
    ];

    protected $casts = [
        'properties' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->connection)) {
            $this->setConnection(config('guachiman.database_connection'));
        }

        if (! isset($this->table)) {
            $this->setTable(config('guachiman.table_name'));
        }

        parent::__construct($attributes);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}
