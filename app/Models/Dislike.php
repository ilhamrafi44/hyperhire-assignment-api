<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dislike extends Model
{
    use HasFactory;
    protected $fillable = ['person_id','device_id'];
    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
