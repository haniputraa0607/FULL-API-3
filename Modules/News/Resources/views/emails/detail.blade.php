<table style="border:1px solid #C0C0C0;border-collapse:collapse;padding:5px; width: 100%">
    <tbody>
        @foreach($news[0] as $key => $value)
        <?php
        
        if(strpos(strtolower($key), 'date') !== false ||strpos(strtolower($key), 'expired') !== false ){
            $value = $value?date('d F Y H:i', strtotime($value)):'';
        } elseif (strpos(strtolower($key), 'time') !== false) {
            $value = $value?date('H:i', strtotime(date('Y-m-d ').$value)):'';
        } elseif (strpos(strtolower($key), 'image') !== false) {
            if(strpos(strtolower($value), 'http') !== false) {
                $value = "<img src='$value' style='max-width: 300px'/>";
            } elseif ($value) {
                $value = '<img src="'.env('STORAGE_URL_API').$value.'" style="max-width: 300px"/>';
            }
        }

        switch($key) {
            case 'id_news_category':
                $value = $value?(\App\Http\Models\NewsCategory::select('category_name')->where('id_news_category',$value)->pluck('category_name')->first()?:'Unchategorized'):'Unchategorized';
                break;

            case 'news_video':
                $value = $value?'<ul>'.implode('',array_map(function($x) {
                    return "<li><a href=\"$x\">$x</a></li>";
                }, explode(';',$value))).'</ul>':'';
                break;

            case 'news_image_dalam':
                $key = 'image_lanscape';
        }

        $key = str_replace(['id_','_id','news'],'',$key);
        ?>
        <tr>
            <th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">{{trim(ucwords(str_replace('_',' ',$key)))}}</th>
            <td style="border:1px solid #C0C0C0;padding:5px;">{!!$value?:'-'!!}</td>
        </tr>
        @endforeach
    </tbody>
</table>