<?php

namespace App\Models\DB1;

use Illuminate\Database\Eloquent\Model;

class BugReport extends Model
{
    protected $connection = 'db1';

    protected $table = 'bug_reports';

    protected $fillable = [
        'reporter_user_id',
        'reporter_email',
        'client_id',
        'title',
        'description',
        'severity',
        'page_url',
        'user_agent',
        'status',
    ];
}
