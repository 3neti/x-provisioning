<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class User extends Model
{
    protected $table = 'users';

    protected $fillable = ['name'];
}
