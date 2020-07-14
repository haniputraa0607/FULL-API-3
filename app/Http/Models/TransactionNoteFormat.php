<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionNoteFormat extends Model
{
    protected $table = 'transaction_note_formats';

    protected $primaryKey = 'id_transaction_note_format';

    protected $fillable = ['format_type', 'content'];
}
