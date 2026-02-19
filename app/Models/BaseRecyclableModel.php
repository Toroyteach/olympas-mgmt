<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Promethys\Revive\Concerns\Recyclable;

class BaseRecyclableModel extends Model
{
    use Recyclable;
}