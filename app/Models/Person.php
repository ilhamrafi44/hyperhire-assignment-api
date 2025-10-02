<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;
    protected $fillable = ['name','age','lat','lng','city'];

    protected $table = 'people';

    public function pictures()
    {
        return $this->hasMany(Picture::class)->orderBy('sort_order');
    }
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
