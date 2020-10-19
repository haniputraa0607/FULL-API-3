<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Modules\Users\Http\Controllers\ApiUser;

use App\Http\Models\Campaign;
use App\Http\Models\CampaignRuleView;

class GenerateCampaignRecipient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$user,$camp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user="Modules\Users\Http\Controllers\ApiUser";
        $this->camp="Modules\Campaign\Http\Controllers\ApiCampaign";
        $this->data=$data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // UserFilter($rule='and', $conditions = null, $order_field='id', $order_method='asc', $skip=0, $take=99999999999, $keyword=null, $select=null,ObjectOnly=false)
        // get campaign data
        $campaign=Campaign::where('id_campaign',$this->data['id_campaign'])->with(['campaign_rule_parents', 'campaign_rule_parents.rules'])->first();
        // campaign exist?
        if(!$campaign){
            echo 'Campaign with id {'.$this->data['id_campaign'].'} cant be found';
            return true;
        }
        // generate campaign user to collection
        $user=app($this->user)->UserFilter($campaign['campaign_rule_parents'],'id','asc',0,999999999,null,['phone','email'],true)??false;

        $recipient=['phone'=>'','email'=>''];
        $get=[];
        if($campaign->campaign_media_email=='Yes'){
            $get[]='email';
        }
        if(strpos($campaign->campaign_media_email.$campaign->campaign_media_sms.$campaign->campaign_media_push.$campaign->campaign_media_inbox, 'Yes')>=-1){
            $get[]='phone';
        }
        if($user!=false){
            $user->chunk(10000,function($users) use (&$recipient,$get){
                foreach ($users as $user) {
                    foreach ($get as $key) {
                        if(!empty($key)){
                            $recipient[$key].=$user->$key.',';
                        }
                    }
                }
            });
        }
        if($campaign->campaign_media_email=='Yes'){
            $data['campaign_email_receipient']=trim(trim($campaign->campaign_email_more_recipient,',').','.$recipient['email'],',');
            $recipientx=array_filter(explode(',',$data['campaign_email_receipient']),function($var){return !empty($var);});
            $data['campaign_email_count_all']=count($recipientx);
        }
        if($campaign->campaign_media_sms=='Yes'){
            $data['campaign_sms_receipient']=trim(trim($campaign->campaign_sms_more_recipient,',').','.$recipient['phone'],',');
            $recipientx=array_filter(explode(',',$data['campaign_sms_receipient']),function($var){return !empty($var);});
            $data['campaign_sms_count_all']=count($recipientx);
        }
        if($campaign->campaign_media_push=='Yes'){
            $data['campaign_push_receipient']=trim(trim($campaign->campaign_push_more_recipient,',').','.$recipient['phone'],',');
            $recipientx=array_filter(explode(',',$data['campaign_push_receipient']),function($var){return !empty($var);});
            $data['campaign_push_count_all']=count($recipientx);
        }
        if($campaign->campaign_media_inbox=='Yes'){
            $data['campaign_inbox_receipient']=trim(trim($campaign->campaign_inbox_more_recipient,',').','.$recipient['phone'],',');
            $recipientx=array_filter(explode(',',$data['campaign_inbox_receipient']),function($var){return !empty($var);});
            $data['campaign_inbox_count']=count($recipientx);
        }
        if($campaign->campaign_media_whatsapp=='Yes'){
            $data['campaign_whatsapp_receipient']=trim(trim($campaign->campaign_whatsapp_more_recipient,',').','.$recipient['phone'],',');
            $recipientx=array_filter(explode(',',$data['campaign_whatsapp_receipient']),function($var){return !empty($var);});
            $data['campaign_whatsapp_count_all']=count($recipientx);
        }
        $id_campaign=$this->data['id_campaign'];
        $data['generate_recipient_status'] = 1;
        $update = Campaign::where('id_campaign','=',$id_campaign)->update($data);

        $getCampaign = Campaign::where('id_campaign','=',$id_campaign)->first()->toArray();

        if($update && !empty($campaign->campaign_send_at) && $campaign->campaign_generate_receipient=='Send At Time' && $getCampaign['campaign_is_sent'] == 'No'){
            SendCampaignNow::dispatch($getCampaign)->allOnConnection('database');
        }

        return $update;
    }
}
