<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

trait Moderatable
{
    public function scopeApproved(Builder $query)
    {
        return $query->where('approved', true);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('approved', false);
    }

    public function approve()
    {
        $this->update(['approved' => true]);
    }

    public function disapprove()
    {
        $this->update(['approved' => false]);
    }

    public function isApproved()
    {
        return $this->approved;
    }
}
