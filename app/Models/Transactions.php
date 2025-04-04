<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $fillable = ['track_id', 'is_successful', 'raw_data'];
    protected $casts    = [
        'raw_data'      => 'array',
        'is_successful' => 'boolean',
    ];
}
