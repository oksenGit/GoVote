<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class Rule extends Model
{
    //
    public $timestamps = true;

    public function options(){
        return $this->hasMany('App\Option');
    }
}
