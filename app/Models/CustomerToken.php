<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CustomerToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'token',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

}
