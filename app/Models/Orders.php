<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    use HasFactory;
    protected $fillable = ['countitem',
        'sum',
        'deliverytype',
        'name',
        'phone',
        'secondphone',
        'email',
        'endsum',
        'typepayment',
        'paid',
        'UserID',
        'status',
        'city',
        'region',
        'house'
    ];
}
