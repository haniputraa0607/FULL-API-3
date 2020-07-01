<table style="border:1px solid #C0C0C0;border-collapse:collapse;padding:5px; width: 100%">
    <tbody>
        @foreach($detail as $key => $value)
        <?php
        
        // hide this column
        if(in_array($key, ['step_complete','used_code','url_promo_campaign_warning_image'])){
            continue;
        }

        if(strpos(strtolower($key), '_date') !== false || strpos(strtolower($key), 'date_') !== false || strpos(strtolower($key), '_at') !== false || strpos(strtolower($key), 'expired') !== false ){
            $value = $value?date('d F Y H:i', strtotime($value)):'';
        } elseif (strpos(strtolower($key), '_time') !== false || strpos(strtolower($key), 'time_') !== false) {
            $value = $value?date('H:i', strtotime(date('Y-m-d ').$value)):'';
        } elseif (strpos(strtolower($key), '_image') !== false || strpos(strtolower($key), 'image_') !== false) {
            if(strpos(strtolower($value), 'http') !== false) {
                $value = "<img src='$value' style='max-width: 300px'/>";
            } elseif ($value) {
                $value = '<img src="'.config('url.storage_url_api').$value.'" style="max-width: 300px"/>';
            }
        }

        switch($key) {
            case 'total_coupon':
                $value = $value?number_format($value,0,',','.'):'Unlimited';
                break;

            case 'limitation_usage':
                $value = $value?number_format($value,0,',','.'):'Unlimited';
                break;

            case 'created_by':
                $value = $value?(\App\Http\Models\User::select('name')->where('id',$value)->pluck('name')->first()?:$value):'Unknown';
                break;

            case 'last_updated_by':
                $value = $value?(\App\Http\Models\User::select('name')->where('id',$value)->pluck('name')->first()?:$value):'Unknown';
                break;

        }

        $key = str_replace(['id_','_id','news'],'',$key);
        ?>
        <tr>
            <th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;width: 40%">{{trim(ucwords(str_replace('_',' ',$key)))}}</th>
            <td style="border:1px solid #C0C0C0;padding:5px;">{!!$value?:'-'!!}</td>
        </tr>
        @endforeach
    </tbody>
</table>