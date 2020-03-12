<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    //
    public function games() {

        return $this->belongsToMany('App\Game');
    }
}
