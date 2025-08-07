<?php
namespace AporteWeb\Guachiman\Http\Controllers;

use App\Http\Controllers\Controller;
use AporteWeb\Guachiman\Models\Activity;

class ActivityLogController extends Controller {
    public function timeline($log_name, $log_ref) {
        $activities = Activity::with(['causer'])
            ->where('log_name', $log_name)
            ->where('ref', $log_ref)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'log_name', 'ref', 'event', 'properties', 'created_at', 'causer_id', 'causer_type']);

        $formattedActivities = $activities->map(function ($activity) {
            return [
                'event' => $activity->event,
                'created_at_formatted' => $activity->created_at_formatted,
                'causer' => [
                    'fullname' => $activity->causer?->resolved_description,
                ],
                'properties' => [
                    'changes' => collect($activity->properties['changes'] ?? [])->map(function ($change) {
                        return [
                            'label' => $change['label'] ?? null,
                            'old_value' => $change['old_value'] ?? null,
                            'new_value' => $change['new_value'] ?? null,
                        ];
                    })
                ],
            ];
        });

        return response()->json([
            'log_name' => $log_name,
            'log_ref' => $log_ref,
            'activities' => $formattedActivities,
        ]);
    }
}