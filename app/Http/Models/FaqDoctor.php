<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FaqDoctor
 *
 * @property int $id_faq_doctor
 * @property string $question
 * @property string $answer
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class FaqDoctor extends Model
{
    protected $primaryKey = 'id_faq_doctor';

    protected $fillable = [
        'faq_number_list',
        'question',
        'answer'
    ];
}
