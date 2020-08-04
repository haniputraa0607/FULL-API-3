<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Modules\Users\Http\Controllers\ApiUser;

use App\Http\Models\promotion;
use App\Http\Models\PromotionQueue;
use App\Http\Models\PromotionSent;
use App\Http\Models\user;
use db;

class GeneratePromotionRecipient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $promotion,$user,$promotion_type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($promotion, $promotion_type)
    {
        $this->user="Modules\Users\Http\Controllers\ApiUser";
        $this->promotion=$promotion;
        $this->promotion_type=$promotion_type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

		$timeNow = date('H:i:00');
		$promotion = $this->promotion;
		$promotion_type = $this->promotion_type;
		$dataPromotionQueue = [];
		
		switch ($promotion_type) {
			case 'series':
				// check promotion content if exist
				if(count($promotion->contents) > 0)
				{
					foreach ($promotion->contents as $key => $content) 
					{
						if($content['promotion_series_days'] > 0)
						{
							$dateSend = date('Y-m-d', strtotime('-'.$content['promotion_series_days'].' days', strtotime(date('Y-m-d'))));
							$idUsers = 	PromotionSent::where('id_promotion_content', $promotion->contents[$key-1]['id_promotion_content'])
										->whereDate('send_at', $dateSend)
										->whereNotIn('id_user', function($q) use ($content){
											$q->select('id_user')->from('promotion_sents')->where('id_promotion_content', $content['id_promotion_content']);
										})
										->select('id_user')
										->distinct();
										// ->get();
							// $users = User::whereIn('id', $idUsers)->get();
		
							if($idUsers){
					            $idUsers->chunk(500,function($users) use ($content, $timeNow){

									$dataPromotionQueue = [];
					                foreach ($users as $user) {
					                    $queue['id_user'] 				= $user['id_user'];
										$queue['id_promotion_content'] 	= $content['id_promotion_content'];
										$queue['send_at'] 				= date('Y-m-d').' '.$timeNow;
										$queue['created_at'] 			= date('Y-m-d H:i:s');
										$queue['updated_at'] 			= date('Y-m-d H:i:s');

										$dataPromotionQueue[] = $queue;
					                }

					                PromotionQueue::insert($dataPromotionQueue);
					            });
					        }
						}
					}
				}

				break;
			
			case 'other':
				// get all users when there are no filters
				if(count($promotion->promotion_rule_parents) <= 0){
					$users = User::select('id','phone','email')->chunk(500, function($users) use ($promotion, $timeNow){
					
						$dataPromotionQueue = [];
		                foreach ($users as $user) {
		                    $queue['id_user'] 				= $user['id'];
							$queue['id_promotion_content'] 	= $promotion['contents'][0]['id_promotion_content'];
							$queue['send_at'] 				= date('Y-m-d').' '.$timeNow;
							$queue['created_at'] 			= date('Y-m-d H:i:s');
							$queue['updated_at'] 			= date('Y-m-d H:i:s');

							$dataPromotionQueue[] = $queue;
		                }

		                PromotionQueue::insert($dataPromotionQueue);
					});
				
				}
				// filter user
				else{
					$cond = Promotion::with(['promotion_rule_parents', 'promotion_rule_parents.rules'])->where('id_promotion','=',$promotion['id_promotion'])->first();
					// $userFilter = app($this->user)->UserFilter($cond['promotion_rule_parents']);
					$userFilter = app($this->user)->UserFilter($cond['promotion_rule_parents'],'id','asc',0,999999999,null,['id','phone','email'],true)??false;
					$users = [];

					if($userFilter)
					{
						$userFilter->whereDoesntHave('promotion_queue', function($q) use ($promotion){
							$q->where('id_promotion_content', $promotion->contents[0]['id_promotion_content']);
						});

					}

					if($promotion['promotion_user_limit'] == '1' || $promotion['promotion_type'] == 'Campaign Series'){

						$userFilter->whereDoesntHave('promotion_queue', function($q) use ($promotion){
							$q->where('id_promotion_content', $promotion->contents[0]['id_promotion_content']);
						});

						$userFilter->whereDoesntHave('promotionSents', function($q) use ($promotion){
							$q->where('id_promotion_content', $promotion->contents[0]['id_promotion_content']);
						});

					}

			        // db::beginTransaction();
					if($userFilter){
			            $userFilter->chunk(500,function($users) use ($promotion, $timeNow){
							$dataPromotionQueue = [];
			                foreach ($users as $user) {
			                    $queue['id_user'] 				= $user['id'];
								$queue['id_promotion_content'] 	= $promotion['contents'][0]['id_promotion_content'];
								$queue['send_at'] 				= date('Y-m-d').' '.$timeNow;
								$queue['created_at'] 			= date('Y-m-d H:i:s');
								$queue['updated_at'] 			= date('Y-m-d H:i:s');

								$dataPromotionQueue[] = $queue;
			                }

			                PromotionQueue::insert($dataPromotionQueue);
			            });
			        }
			        // db::commit();
				}
				break;

			default:
				return true;
				break;
		}
		
		return true;
    }
}
