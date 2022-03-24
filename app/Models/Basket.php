<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Basket extends Model
{
    public $timestamps = false;
    protected $fillable = ['UserID', 'ItemID'];
}
