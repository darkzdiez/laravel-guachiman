<?php

namespace AporteWeb\Guachiman\Traits;

use AporteWeb\Guachiman\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait LogsChanges
{
    public static function bootLogsChanges()
    {
        static::updated(function (Model $model) {
            $changes = $model->getDirty();
            $original = $model->getOriginal();
            $loggableRefName = $model->getKeyName();
            $loggableRefValue = $model->getKey();

            // si $model->loggable es null, o es un array vacio, se loguean todas las columnas
            if (is_null($model->loggable) || empty($model->loggable)) {
                $model->loggable = array_keys($changes);
            }

            $properties = [];

            foreach ($changes as $field => $newValue) {
                try {
                    if (in_array($field, $model->loggable ?? []) && $original[$field] != $newValue) {

                        $loggableLabels = $model->loggableLabels ?? [];

                        $properties[] = [
                            'field' => $field,
                            'label' => $loggableLabels[$field] ?? null,
                            'old_value' => $original[$field],
                            'new_value' => $newValue,
                        ];

                        // si el campo se llama proveedor_id, se busca el metodo resolveProveedorIdLoggable
                        $methodName = 'resolve' . Str::studly($field) . 'Loggable';
                        if (method_exists($model, $methodName)) {
                            $old_value = $model->$methodName($original[$field]);
                            $new_value = $model->$methodName($newValue);
                            $key = array_keys($old_value)[0];
                            // chequear si en $properties ya existe un campo con el mismo nombre
                            $found = false;
                            foreach ($properties as $k => $v) {
                                if ($v['field'] == $key) {
                                    $found = true;
                                    $properties[$k]['old_value'] = $old_value[$key];
                                    $properties[$k]['new_value'] = $new_value[$key];
                                    $properties[$k]['label'] = $loggableLabels[$key] ?? null;
                                }
                            }
                            if (!$found) {
                                $properties[] = [
                                    'field' => $key,
                                    'label' => $loggableLabels[$key] ?? null,
                                    'old_value' => $old_value[$key],
                                    'new_value' => $new_value[$key],
                                ];
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    $properties[] = [
                        'field' => $field,
                        'old_value' => array_key_exists($field, $original) ? $original[$field] : null,
                        'new_value' => $newValue,
                        'error' => $th->getMessage() . ' in ' . $th->getFile() . ' on line ' . $th->getLine()
                    ];
                }
            }

            if (!empty($properties)) {
                $userName = Auth::user() ? Auth::user()->name : 'system';
                $description = "El usuario {$userName} actualizó el registro {$loggableRefValue} en la tabla " . $model->getTable();

                $logData = [
                    'log_name' => $model->getTable(),
                    'description' => $description,
                    'subject_type' => get_class($model),
                    'subject_id' => $model->id,
                    'event' => 'update',
                    'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                    'causer_id' => Auth::id(),
                    'causer_name' => Auth::user()?->resolved_description,
                    'properties' => ['changes' => $properties],
                    'ref_name' => $loggableRefName,
                    'ref' => $loggableRefValue,
                    'sapi_name' => php_sapi_name(),
                    'ip_address' => request()->ip(),
                ];

                if (method_exists($model, 'getParentLogData')) {
                    $logData['properties']['parent'] = $model->getParentLogData();
                }

                Activity::create($logData);
            }
        });
        static::created(function (Model $model) {
            $loggableRefName = $model->getKeyName();
            $loggableRefValue = $model->getKey();
            $userName = Auth::user() ? Auth::user()->name : 'system';
            $description = "El usuario {$userName} creó el registro {$loggableRefValue} en la tabla " . $model->getTable();

            $logData = [
                'log_name' => $model->getTable(),
                'description' => $description,
                'subject_type' => get_class($model),
                'subject_id' => $model->id,
                'event' => 'create',
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'causer_name' => Auth::user() ? Auth::user()->name : null,
                'properties' => ['attributes' => $model->toArray()],
                'ref_name' => $loggableRefName,
                'ref' => $loggableRefValue,
                'sapi_name' => php_sapi_name(),
                'ip_address' => request()->ip(),
            ];

            if (method_exists($model, 'getParentLogData')) {
                $logData['properties']['parent'] = $model->getParentLogData();
            }
            Activity::create($logData);
        });
        static::deleted(function (Model $model) {
            $loggableRefName = $model->getKeyName();
            $loggableRefValue = $model->getKey();
            $userName = Auth::user() ? Auth::user()->name : 'system';
            $description = "El usuario {$userName} eliminó el registro {$loggableRefValue} en la tabla " . $model->getTable();

            $logData = [
                'log_name' => $model->getTable(),
                'description' => $description,
                'subject_type' => get_class($model),
                'subject_id' => $model->id,
                'event' => 'delete',
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'causer_name' => Auth::user() ? Auth::user()->name : null,
                'properties' => ['attributes' => $model->toArray()],
                'ref_name' => $loggableRefName,
                'ref' => $loggableRefValue,
                'sapi_name' => php_sapi_name(),
                'ip_address' => request()->ip(),
            ];

            if (method_exists($model, 'getParentLogData')) {
                $logData['properties']['parent'] = $model->getParentLogData();
            }
            Activity::create($logData);
        });
    }
}
