<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkinDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id','jenis_kulit', 'masalah_kulit'
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
