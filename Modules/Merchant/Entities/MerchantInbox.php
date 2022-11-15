<?php

namespace Modules\Merchant\Entities;

use Illuminate\Database\Eloquent\Model;

class MerchantInbox extends Model
{
    protected $table = 'merchant_inboxes';
    protected $primaryKey = 'id_merchant_inboxes';

    protected $fillable = [
        'id_campaign',
        'id_merchant',
        'inboxes_subject',
        'inboxes_content',
        'inboxes_clickto',
        'inboxes_link',
        'inboxes_id_reference',
        'inboxes_send_at',
        'read',
        'id_brand'
    ];
}
