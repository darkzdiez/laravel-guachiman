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
}
