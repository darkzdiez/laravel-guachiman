<?php

namespace AporteWeb\Guachiman\Logger;

use AporteWeb\Guachiman\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    protected Activity $activity;

    public function __construct()
    {
        $this->activity = new Activity();
        $this->activity->causer_id = Auth::id();
        $this->activity->causer_type = Auth::user() ? get_class(Auth::user()) : null;
        $this->activity->causer_name = Auth::user() ? Auth::user()->name : null;
        $this->activity->ip_address = request()->ip();
        $this->activity->sapi_name = php_sapi_name();
    }

    public function onModel(Model $subject): self
    {
        $this->performedOn($subject);
        $this->activity->log_name = $subject->getTable();
        $refName = method_exists($subject, 'getLoggableRefNameForLog')
            ? $subject->getLoggableRefNameForLog()
            : $subject->getKeyName();
        $refValue = method_exists($subject, 'getLoggableRefValueForLog')
            ? $subject->getLoggableRefValueForLog($refName)
            : $subject->getKey();
        $this->activity->ref_name = $refName;
        $this->activity->ref = $refValue;
        return $this;
    }

    public function onLogName(string $logName): self
    {
        $this->activity->log_name = $logName;
        return $this;
    }

    public function forEvent(string $event): self
    {
        $this->activity->event = $event;
        return $this;
    }

    public function withRef(string $refName, $refValue): self
    {
        $this->activity->ref_name = $refName;
        $this->activity->ref = $refValue;
        return $this;
    }

    public function describe(string $description): self
    {
        $this->activity->description = $description;
        return $this;
    }

    public function causedBy(Model $causer): self
    {
        $this->activity->causer()->associate($causer);
        $this->activity->causer_name = $causer->name ?? null;

        return $this;
    }

    public function performedOn(Model $subject): self
    {
        $this->activity->subject()->associate($subject);

        return $this;
    }

    public function withProperties(array $properties): self
    {
        $this->activity->properties = $properties;

        return $this;
    }

    public function withProperty(string $key, $value): self
    {
        $properties = $this->activity->properties ?? [];
        $properties[$key] = $value;
        $this->activity->properties = $properties;

        return $this;
    }

    public function log(string $description): ?Activity
    {
    $this->activity->description = $description;
        $this->activity->save();

        return $this->activity;
    }

    /**
     * Loguea un cambio de relación (sync/attach/detach) de forma estandarizada.
     * - relationName: nombre lógico del campo (ej: 'groups')
     * - originalIds: IDs antes del cambio
     * - newIds: IDs después del cambio
     * - labelsById: ['id' => 'Nombre'] mapeo opcional para mostrar etiquetas
     * - meta: datos extra para properties
     */
    public function logRelationSync(
        string $relationName,
        array $originalIds,
        array $newIds,
        array $labelsById = [],
        array $meta = []
    ): ?Activity {
        $added = array_values(array_diff($newIds, $originalIds));
        $removed = array_values(array_diff($originalIds, $newIds));

        if (!count($added) && !count($removed)) {
            return null; // nada que loguear
        }

        $payload = [
            'field' => $relationName,
            'label' => $meta['label'] ?? ucfirst(str_replace('_', ' ', $relationName)),
            'old_value' => $originalIds,
            'new_value' => $newIds,
            'old_labels' => array_values(array_map(fn ($id) => $labelsById[$id] ?? null, $originalIds)),
            'new_labels' => array_values(array_map(fn ($id) => $labelsById[$id] ?? null, $newIds)),
            'added' => [
                'ids' => $added,
                'labels' => array_values(array_map(fn ($id) => $labelsById[$id] ?? null, $added)),
            ],
            'removed' => [
                'ids' => $removed,
                'labels' => array_values(array_map(fn ($id) => $labelsById[$id] ?? null, $removed)),
            ],
        ];

        // Permitir anexar metadata adicional
        foreach ($meta as $k => $v) {
            if (!array_key_exists($k, $payload)) {
                $payload[$k] = $v;
            }
        }

        // anexar a properties->changes[] preservando otras properties
        $properties = is_array($this->activity->properties ?? null)
            ? $this->activity->properties
            : [];
        $changes = $properties['changes'] ?? [];
        $changes[] = $payload;
        $properties['changes'] = $changes;

        $this->activity->properties = $properties;

        // Si no hay descripción aún, generar una por defecto
        if (!$this->activity->description) {
            $this->activity->description = sprintf(
                'Actualización de relación %s',
                $relationName
            );
        }

        $this->activity->save();
        return $this->activity;
    }
}
