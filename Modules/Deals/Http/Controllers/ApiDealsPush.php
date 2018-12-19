<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use App\Http\Models\Deal;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsPaymentManual;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;

use DB;

use Modules\Deals\Http\Requests\Deals\Create;
use Modules\Deals\Http\Requests\Deals\Update;
use Modules\Deals\Http\Requests\Deals\Delete;

use Illuminate\Support\Facades\Schema;

class ApiDealsPush extends Controller
{

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public $saveImage = "img/deals/";

    /* PUSH NOTIFICATION */
    function pushForDeals($deals) {

    }

    
}