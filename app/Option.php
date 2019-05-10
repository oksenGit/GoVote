<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    public function rule(){
        return $this->belongsTo("App\Rule");
    }
}
