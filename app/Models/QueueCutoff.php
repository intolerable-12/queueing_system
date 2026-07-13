<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueCutoff extends Model
{
    protected $fillable = [

        'cutoff_date',

        'is_closed',

        'closed_by',

        'closed_at'

    ];
}