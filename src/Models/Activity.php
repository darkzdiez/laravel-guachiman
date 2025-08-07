<?php

namespace AporteWeb\Guachiman\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $appends = [
        'created_at_formatted',
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
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    public function causer(): MorphTo
    {
        return $this->morphTo('causer', 'causer_type', 'causer_id');
    }

    protected function createdAtFormatted(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => isset($attributes['created_at']) ? \Carbon\Carbon::parse($attributes['created_at'])->format('d/m/Y h:i A') : null,
        );
    }
}
