<?php

namespace Modules\Reward\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Reward;
use App\Http\Models\RewardUser;
use App\Http\Models\User;
use App\Http\Models\LogPoint;

use Modules\Reward\Http\Requests\Create;
use Modules\Reward\Http\Requests\Detail;
use Modules\Reward\Http\Requests\Update;
use Modules\Reward\Http\Requests\Buy;
use Modules\Reward\Http\Requests\WinnerChoosen;

use App\Lib\MyHelper;
use DB;

class ApiReward extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
    public $saveImage = "img/reward/";

    function create(Create $request)
    {
        $post = $request->json()->all();

        //upload image
        if (!file_exists($this->saveImage)) {
            mkdir($this->saveImage, 0777, true);
        }
        $upload = MyHelper::uploadPhotoStrict($post['reward_image'], $this->saveImage, 300, 300);;

        if (isset($upload['status']) && $upload['status'] == "success") {
            $post['reward_image'] = $upload['path'];
        }
        else {
            $result = [
                'status'   => 'fail',
                'messages' => ['fail upload image']
            ];

            return response()->json($result);
        }

        $post['reward_start']           = date('Y-m-d', strtotime($post['reward_start']));
        $post['reward_end']             = date('Y-m-d', strtotime($post['reward_end']));
        $post['reward_publish_start']   = date('Y-m-d', strtotime($post['reward_publish_start']));
        $post['reward_publish_end']     = date('Y-m-d', strtotime($post['reward_publish_end']));

        $reward = Reward::create($post);

        return response()->json(MyHelper::checkCreate($reward));
    }

    function list(Request $request){
        $post = $request->json()->all();

        $reward = Reward::with('reward_user.user');
        if (isset($post['id_reward'])) {
            $reward->where('id_reward', $post['id_reward']);
        }

        $reward = $reward->get();

        if (isset($post['id_reward'])) {

            //set winner when reward end
            if(date('Y-m-d', strtotime($reward[0]['reward_end'])) < date('Y-m-d') && $reward[0]['winner_type'] != 'Choosen'){
                //check participant
                $participant = $reward[0]->reward_user->count('id_user');
                if($participant > 0){
                    //chek winner has been set
                    $winner = $reward[0]->winner->count('id_user');
                    if($winner <= 0){
                        $setWinner = $this->setWinner($reward[0]->id_reward);
                        if((isset($setWinner['status']) && $setWinner['status'] == 'fail') || !isset($setWinner['status'])){
                            return response()->json($setWinner);
                        }
                        
                        $reward = Reward::with('reward_user.user')->where('id_reward', $post['id_reward'])->get();
                    }
                }

            }
            $reward[0]['reward_total_coupon'] = $reward[0]->reward_user->sum('total_coupon');
            $reward[0]['reward_total_user'] = $reward[0]->reward_user->count('id_user');
            $reward[0]['reward_total_winner'] = $reward[0]->winner->count('id_user');
        }
        return response()->json(MyHelper::checkGet($reward));
    }

    function update(Update $request)
    {
        $post = $request->json()->all();

        $reward = Reward::find($post['id_reward']);
        if(!$reward){
            $result = [
                'status'   => 'fail',
                'messages' => ['Reward not found.']
            ];

            return response()->json($result);
        }
        //upload image
        if(isset($post['reward_image'])){
            //delete old image
            if (!empty($reward->reward_image)) {
                $delete = MyHelper::deletePhoto($reward->reward_image);
            }

            //upload new image
            $upload = MyHelper::uploadPhotoStrict($post['reward_image'], $this->saveImage, 300, 300);
            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['reward_image'] = $upload['path'];
            }
            else {
                $result = [
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];
    
                return response()->json($result);
            }
        }

        $post['reward_start']           = date('Y-m-d', strtotime($post['reward_start']));
        $post['reward_end']             = date('Y-m-d', strtotime($post['reward_end']));
        $post['reward_publish_start']   = date('Y-m-d', strtotime($post['reward_publish_start']));
        $post['reward_publish_end']     = date('Y-m-d', strtotime($post['reward_publish_end']));

        $reward = Reward::where('id_reward', $post['id_reward'])->update($post);

        return response()->json(MyHelper::checkUpdate($reward));
    }

    function delete(Detail $request){
        $post = $request->json()->all();

        $reward = Reward::find($post['id_reward']);
        if(!$reward){
            $result = [
                'status'   => 'fail',
                'messages' => ['Reward not found.']
            ];

            return response()->json($result);
        }

        //cek reward user
        $reward_user = RewardUser::where('id_reward', $post['id_reward'])->get();
        if(count($reward_user) > 0){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Reward already used.']
            ]);
        }

        //delete image
        if (!empty($reward->reward_image)) {
            $delete = MyHelper::deletePhoto($reward->reward_image);
        }

        $delete = Reward::where('id_reward', $post['id_reward'])->delete();
        return response()->json(MyHelper::checkDelete($delete));
    }

    function buyCoupon(Buy $request){
        $post = $request->json()->all();
        $user = $request->user();
        $now = date('Y-m-d');

        //cek reward
        $reward = Reward::find($post['id_reward']);
        if(!$reward){
            $result = [
                'status'   => 'fail',
                'messages' => ['Reward not found.']
            ];

            return response()->json($result);
        }

        //cek reward start & reward end  
        if($now < $reward['reward_start'] || $now > $reward['reward_end']){
            $result = [
                'status'   => 'fail',
                'messages' => ['Reward not available.']
            ];

            return response()->json($result);
        }

        //cek point
        $point = LogPoint::where('id_user', $user['id'])->sum('point');
        $pointNeeded = $reward['reward_coupon_point'] * $post['total_coupon'];

        if($point < $pointNeeded){
            $result = [
                'status'   => 'fail',
                'messages' => ['User points are not enough.']
            ];

            return response()->json($result);
        }

        DB::BeginTransaction();

        //insert reward user
        $rewardUser = RewardUser::where('id_user', $user['id'])->where('id_reward', $reward['id_reward'])->first();
        if(!$rewardUser){
            $new['id_user'] = $user['id'];
            $new['id_reward'] = $reward['id_reward'];
            $new['total_coupon'] = $post['total_coupon'];
            $rewardUser = RewardUser::create($new);
        }else{
            $rewardUser->total_coupon = $rewardUser->total_coupon + $post['total_coupon'];
            $rewardUser->update();
        }

        if(!$rewardUser){
            DB::rollBack();
            $result = [
                'status'   => 'fail',
                'messages' => ['Update reward user failed.']
            ];

            return response()->json($result);
        }

        //create logpoint
        $log['id_user'] = $user['id'];
        $log['point']   = -$pointNeeded;
        $log['id_reference']    = $reward['id_reward'];
        $log['source']          = 'reward';
        $log['reward_coupon_point'] = $reward['reward_coupon_point'];
        $log['reward_total_coupon'] = $post['total_coupon'];

        $log = LogPoint::create($log);
        if(!$log){
            DB::rollBack();
            $result = [
                'status'   => 'fail',
                'messages' => ['Insert point failed.']
            ];

            return response()->json($result);
        }

        //update user point
        $sumPoint = LogPoint::where('id_user', $user['id'])->sum('point');
        $update = User::where('id', $user['id'])->update(['points' => $sumPoint]);
        if(!$log){
            DB::rollBack();
            $result = [
                'status'   => 'fail',
                'messages' => ['Insert point failed.']
            ];

            return response()->json($result);
        }

        DB::commit();
        $return = RewardUser::with('user', 'reward')->where('id_user', $user['id'])->where('id_reward', $reward['id_reward'])->first();
        $result = [
            'status' => 'success',
            'result' => $return
        ];
        return response()->json($result);

    }

    function listActive(){
        $now = date('Y-m-d');
        $reward = Reward::where('reward_publish_start', '<=', $now)->where('reward_publish_end', '>=', $now)->get();
        return response()->json(MyHelper::checkGet($reward));
    }

    function myCoupon(Request $request){
        $now = date('Y-m-d');
        $reward = Reward::join('reward_users', 'rewards.id_reward', 'reward_users.id_reward')
                        ->where('reward_start', '<=', $now)
                        ->where('reward_end', '>=', $now)
                        ->where('reward_users.id_user', $request->user()->id)
                        ->get();
        return response()->json(MyHelper::checkGet($reward));
    }

    function setWinner($id_reward){
        DB::beginTransaction();
        //cek reward
        $reward = Reward::find($id_reward);
        if(!$reward){
            $result = [
                'status'   => 'fail',
                'messages' => ['Reward not found.']
            ];

            return $result;
        }

        if($reward['winner_type'] == 'Highest Coupon'){
            $id_user = RewardUser::where('id_reward', $reward->id_reward)->limit($reward->count_winner)->orderBy('total_coupon', 'DESC')->get()->pluck('id_user');
        }else if($reward->winner_type == 'Random'){
            $rewardUser = RewardUser::where('id_reward', $reward->id_reward)->get();
            $win = array();
            foreach ($rewardUser as $index=>$value)
            {
                $win = array_merge($win, array_fill(0, $value['total_coupon'], $value['id_user']));
            }

            $random = array_rand($win, $reward->count_winner);
            foreach($random as $i => $rand){
                $id_user[] = $win[$rand];
            }
        }
        
        //set winner
        $winner = RewardUser::whereIn('id_user', $id_user)->where('id_reward', $reward->id_reward)->update(['is_winner' => '1']);
        if(!$winner){
            DB::rollBack();
            $result = [
                'status'   => 'fail',
                'messages' => ['Setting winner failed.']
            ];

            return $result;
        }

        //update winner yg tidak dipilih
        $notWinner = RewardUser::whereNotIn('id_user', $id_user)->where('id_reward', $reward->id_reward)->update(['is_winner' => '0']);

        DB::commit();

        $result = [
            'status'   => 'success'
        ];
        return $result;
    }

    function setWinnerChoosen(WinnerChoosen $request){
        $post = $request->json()->all();

        DB::beginTransaction();
        //cek reward
        $reward = Reward::find($post['id_reward']);
        if(!$reward){
            $result = [
                'status'   => 'fail',
                'messages' => ['Reward not found.']
            ];

            return response()->json($result);
        }

        //set winner
        $winner = RewardUser::whereIn('id_user', $post['id_user'])->where('id_reward', $reward->id_reward)->update(['is_winner' => '1']);
        if(!$winner){
            DB::rollBack();
            $result = [
                'status'   => 'fail',
                'messages' => ['Setting winner failed.']
            ];

            return $result;
        }

        DB::commit();

        $result = [
            'status'   => 'success',
            'result'   => $reward
        ];
        return response()->json($result);
    }
}
