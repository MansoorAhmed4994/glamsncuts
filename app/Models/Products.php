<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    public $timestamps = false;
    protected $fillable = ['sku','name','created_by','updated_by','status'];
    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    } 
}
