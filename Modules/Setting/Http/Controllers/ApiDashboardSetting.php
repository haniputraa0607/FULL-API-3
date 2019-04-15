<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\DashboardUser;
use App\Http\Models\DashboardCard;
use App\Http\Models\DashboardDateRange;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Http\Models\Transaction;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\DailyReportTrxMenu;
use App\Http\Models\DailyReportTrx;

use App\Lib\MyHelper;
use DB;

class ApiDashboardSetting extends Controller
{
	function __construct() {
		date_default_timezone_set('Asia/Jakarta');
		
    }
	
	function updateDashboard(Request $request){
		$user = $request->user();
		$post = $request->json()->all();

		DB::beginTransaction();

		$dashboardUser['id_user'] = $user->id;

		//for update
		if(isset($post['id_dashboard_user'])){
			$dataDashboardUser = DashboardUser::find($post['id_dashboard_user']);
			if(!$dataDashboardUser){
				return response()->json([
					'status' => 'fail',
					'messages' => ['Data dashboard user not found.']
				]);
			}

			if(isset($post['section_title'])){
				$dataDashboardUser->section_title = $post['section_title'];
				$dataDashboardUser->update();
				if(!$dataDashboardUser){
					DB::rollBack();
					return response()->json([
						'status' => 'fail',
						'messages' => ['Update dashboard user failed.']
					]);
				}
				$data = $dataDashboardUser;
			}
		}
		//for create
		else{
			$sectionOrder = DashboardUser::where('id_user', $user->id)->orderBy('section_order', 'DESC')->first();
			if($sectionOrder){
				$sectionOrder = $sectionOrder->section_order + 1;
			}else{
				$sectionOrder = 1;
			}
			$dashboardUser['section_title'] = $post['section_title'];
			$dashboardUser['section_order'] = $sectionOrder;
	
			$dataDashboardUser = DashboardUser::create($dashboardUser);
			if(!$dataDashboardUser){
				DB::rollBack();
				return response()->json([
					'status' => 'fail',
					'messages' => ['Create dashboard user failed.']
				]);
			}

			//insert card
			$cardOrder = DashboardCard::where('id_dashboard_user', $dataDashboardUser->id_dashboard_user)->orderBy('card_order', 'DESC')->first();
			if($cardOrder){
				$cardOrder = $cardOrder->card_order + 1;
			}else{
				$cardOrder = 1;
			}

			foreach($post['cards'] as $card){
				$dataCard['id_dashboard_user'] = $dataDashboardUser->id_dashboard_user;
				$dataCard['card_name'] = $card['card'];
				$dataCard['card_order'] = $cardOrder;
				$dataCard['created_at'] = date('Y-m-d H:i:s');
				$dataCard['updated_at'] = date('Y-m-d H:i:s');
	
				$cards[] = $dataCard;

				$cardOrder++;
			}
	
			$data = DashboardCard::insert($cards);
			if(!$data){
				DB::rollBack();
				return response()->json([
					'status' => 'fail',
					'messages' => ['Create dashboard card failed.']
				]);
			}
		}

		//update card
		if(isset($post['card'])){
			//for delete card
			if(isset($post['card']['id']) && !isset($post['card']['card_name'])){
				$data = DashboardCard::where('id_dashboard_card', $post['card']['id'])->delete();
				if(!$data){
					DB::rollBack();
					return response()->json([
						'status' => 'fail',
						'messages' => ['Delete dashboard card failed.']
					]);
				}
			}

			//insert new card
			if(!isset($post['card']['id']) && isset($post['card']['card_name'])){
				$cardOrder = DashboardCard::where('id_dashboard_user', $dataDashboardUser->id_dashboard_user)->orderBy('card_order', 'DESC')->first();
				if($cardOrder){
					$position = $cardOrder->card_order + 1;
				}else{
					$position = 1;
				}

				$newCard['card_name']  = $post['card']['card_name'];
				$newCard['card_order'] = $position;
				$newCard['id_dashboard_user'] = $dataDashboardUser->id_dashboard_user;
				$data = DashboardCard::create($newCard);
				if(!$data){
					DB::rollBack();
					return response()->json([
						'status' => 'fail',
						'messages' => ['Create dashboard card failed.']
					]);
				}
				
			}
			//update card
			if(isset($post['card']['id']) && isset($post['card']['card_name'])){
				$data = DashboardCard::find($post['card']['id']);
				if(!$data){
					DB::rollBack();
					return response()->json([
						'status' => 'fail',
						'messages' => ['Data dashboard card not found.']
					]);
				}

				$data['card_name']  = $post['card']['card_name'];
				$data->update();
				if(!$data){
					DB::rollBack();
					return response()->json([
						'status' => 'fail',
						'messages' => ['Update dashboard card failed.']
					]);
				}
				
			}

		}

		DB::commit();
		return response()->json(MyHelper::checkGet($data));
	}

	function updateOrderSection(Request $request){
		$post = $request->json()->all();

		DB::beginTransaction();
		$dataUpdate = [];
		foreach ($post['order'] as $key => $value) {
			$section = DashboardUser::find($value['id']);
			if(!$section){
				DB::rollBack();
				return response()->json([
					'status' => 'fail',
					'messages' => ['Data dashboard user not found.']
				]);
			}

			$section->section_order = $value['position'];
			$section->update();
			if(!$section){
				DB::rollBack();
				return response()->json([
					'status' => 'fail',
					'messages' => ['Update order dashboard user failed.']
				]);
			}

			$dataUpdate[] = $section;
		}

		DB::commit();
		return response()->json([
			'status' => 'success',
			'result' => $dataUpdate
		]);

	}

	function updateOrderCard(Request $request){
		$post = $request->json()->all();

		DB::beginTransaction();
		$dataUpdate = [];
		foreach ($post['order'] as $key => $value) {
			$card = DashboardCard::find($value['id']);
			if(!$card){
				DB::rollBack();
				return response()->json([
					'status' => 'fail',
					'messages' => ['Data dashboard card not found.']
				]);
			}

			$card->card_order = $value['position'];
			$card->update();
			if(!$card){
				DB::rollBack();
				return response()->json([
					'status' => 'fail',
					'messages' => ['Update order dashboard card failed.']
				]);
			}

			$dataUpdate[] = $card;
		}

		DB::commit();
		return response()->json([
			'status' => 'success',
			'result' => $dataUpdate
		]);

	}

	function getListDashboard(Request $request){
		$user = $request->user();
		$post = $request->json()->all();

		$dashboard = DashboardUser::with('dashboard_card')->where('id_user', $user->id)->orderBy('section_order', 'ASC')->get();
		$dateRange = DashboardDateRange::where('id_user', $user->id)->first();
		if($dateRange){
			$dashboard['date_range'] = $dateRange->default_date_range;
		}

		return response()->json(MyHelper::checkGet($dashboard));
	}

	function deleteDashboard(Request $request){
		$post = $request->json()->all();
		$dashboardUser = DashboardUser::find($post['id_dashboard_user']);
		if(!$dashboardUser){
			return response()->json([
				'status' => 'fail',
				'messages' => ['Data not found.']
			]);
		}

		DB::beginTransaction();
		//delete card
		if(count($dashboardUser->dashboard_card) > 0){
			$deleteCard = DashboardCard::where('id_dashboard_user', $dashboardUser->id_dashboard_user)->delete();
			if(!$deleteCard){
				DB::rollBack();
				return response()->json([
					'status' => 'fail',
					'messages' => ['Delete dashboard card failed.']
				]);
			}
		}

		$dashboardUser->delete();
		if(!$dashboardUser){
			return response()->json([
				'status' => 'fail',
				'messages' => ['Delete dashboard user failed.']
			]);
		}

		DB::commit();
		return response()->json(MyHelper::checkDelete($dashboardUser));
	}

	function updateDateRange(Request $request){
		$user = $request->user();
		$post = $request->json()->all();

		$dateRange = DashboardDateRange::updateOrCreate(['id_user' => $user->id], $post);

		return response()->json(MyHelper::checkCreate($dateRange));
	}

	function getDashboard(Request $request){
		$user = $request->user();
		$post = $request->json()->all();

		//filter by button in home page
		if(isset($post['date_start']) && isset($post['date_end'])){
			$start = $post['date_start'];
			$end = $post['date_end'];
			if($post['year'] == 'alltime'){
				$dateRange['default_date_range'] = 'All Time';
			}elseif($post['year'] == 'last7days'){
				$dateRange['default_date_range'] = '7 days';
			}elseif($post['year'] == 'last30days'){
				$dateRange['default_date_range'] = '30 days';
			}elseif($post['year'] == 'last3months'){
				$dateRange['default_date_range'] = '3 months';
			}else{
				$dateRange['default_date_range'] = date('F Y', strtotime('1-'.$post['month'].'-'.$post['year']));
			}
		}else{
			$dateRange = DashboardDateRange::where('id_user', $user->id)->first();
			if(!$dateRange){
				$dateRange['default_date_range'] = '30 days';
			}
			
			if(strpos($dateRange['default_date_range'], 'days') !== false){
				$day = str_replace(' days', '', $dateRange['default_date_range']);
				$start = date('Y-m-d', strtotime('-'.$day.' days'));
			}else{
				if($dateRange['default_date_range'] == 'this month'){
					$start = date('Y-m-01');
					$dateRange['default_date_range'] = date('F Y');
				}else{
					$month = str_replace(' months', '', $dateRange['default_date_range']);
					$start = date('Y-m-d', strtotime('-'.$month.' months'));
				}
				
			}
	
			$end = date('Y-m-d');
		}

		$dashboard = DashboardUser::with('dashboard_card')->where('id_user', $user->id)->orderBy('section_order', 'ASC')->get();

		if(count($dashboard) == 0){
			$dashboard = [];
			$dashboard[0]['section_title'] = 'Transaction Summary';
			$dashboard[0]['dashboard_card'][0]['card_name'] = 'Total Transaction Value';
			$dashboard[0]['dashboard_card'][1]['card_name'] = 'Total Transaction Count';
			$dashboard[0]['dashboard_card'][2]['card_name'] = 'Transaction Average per Day';
			$dashboard[1]['section_title'] = 'User Summary';
			$dashboard[1]['dashboard_card'][0]['card_name'] = 'New Customer';
			$dashboard[1]['dashboard_card'][1]['card_name'] = 'Total Customer Verified';
			$dashboard[1]['dashboard_card'][2]['card_name'] = 'Total Customer Not Verified';
			$dashboard[1]['dashboard_card'][3]['card_name'] = 'Total User';
			$dashboard[1]['dashboard_card'][4]['card_name'] = 'Total Customer';
			$dashboard[1]['dashboard_card'][5]['card_name'] = 'Total Admin';
			$dashboard[1]['dashboard_card'][6]['card_name'] = 'Total Super Admin';
			$dashboard[1]['dashboard_card'][7]['card_name'] = 'Total Male Customer';
			$dashboard[1]['dashboard_card'][8]['card_name'] = 'Total Female Customer';
			$dashboard[1]['dashboard_card'][9]['card_name'] = 'Total Android Customer';
			$dashboard[1]['dashboard_card'][10]['card_name'] = 'Total IOS Customer';
		}
		foreach($dashboard as $key => $dash){
			foreach($dash['dashboard_card'] as $index => $card){
				$value = 0;
				$url = null;

				if(strpos($card['card_name'], 'Admin Outlet') !== false){
					$value = UserOutlet::count();
				}

				elseif(strpos($card['card_name'], 'Top 10') !== false){

					if(strpos($card['card_name'], 'Outlet') !== false){
						$value = DailyReportTrx::join('outlets', 'daily_report_trx.id_outlet', 'outlets.id_outlet')
								->whereDate('daily_report_trx.trx_date', '>=', $start)
								->whereDate('daily_report_trx.trx_date', '<=', $end)
								->groupBy('outlets.id_outlet');
	
						if(strpos($card['card_name'], 'Count') !== false){
							$value = $value->select(DB::raw('outlets.outlet_name, outlets.id_outlet as id, outlets.outlet_code, SUM(daily_report_trx.trx_count) as transaction_count'))
										   ->orderBy('transaction_count', 'DESC');
						}
						elseif(strpos($card['card_name'], 'Value') !== false){
							$value = $value->select(DB::raw('outlets.outlet_name, outlets.id_outlet as id, SUM(daily_report_trx.trx_grand) as transaction_value'))
										   ->orderBy('transaction_value', 'DESC');
						}
						$value = $value->limit(10)->get();
						$url = $start.'/'.$end;
					}
					elseif(strpos($card['card_name'], 'Product') !== false){
						$value = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
								->whereDate('daily_report_trx_menu.trx_date', '>=', $start)
								->whereDate('daily_report_trx_menu.trx_date', '<=', $end)
								->groupBy('products.id_product');
	
						if(strpos($card['card_name'], 'Recurring') !== false){
						$value = $value->select(DB::raw('products.product_name, products.id_product as id, products.product_code, SUM(daily_report_trx_menu.total_rec) as total_recurring'))
									   ->orderBy('total_recurring', 'DESC');
						}
						elseif(strpos($card['card_name'], 'Quantity') !== false){
							$value = $value->select(DB::raw('products.product_name, products.id_product as id, products.product_code, SUM(daily_report_trx_menu.total_qty) as total_quantity'))
									->orderBy('total_quantity', 'DESC');
						}
						$value = $value->limit(10)->get();
						$url = $start.'/'.$end;
					}

					elseif(strpos($card['card_name'], 'User') !== false || strpos($card['card_name'], 'Customer') !== false){
						$value = Transaction::leftJoin('users', 'transactions.id_user', 'users.id')
								->where('transaction_payment_status', 'Completed')
								->whereDate('transaction_date', '>=', $start)
								->whereDate('transaction_date', '<=', $end)
								->select(DB::raw('users.name, users.phone, SUM(transaction_grandtotal) as nominal'))
								->orderBy('nominal', 'DESC')
								->groupBy('users.id');
						if(strpos($card['card_name'], 'Customer') !== false){
							$value = $value->where('users.level', 'Customer');
						}
						$value = $value->limit(10)->get();
					}
				}

				elseif(strpos($card['card_name'], 'Customer') !== false || strpos($card['card_name'], 'User') !== false || strpos($card['card_name'], 'Admin') !== false){
					$value = User::whereDate('created_at', '<=', $end);
					if(strpos($card['card_name'], 'Customer') !== false){
						$value = $value->where('level', 'Customer');
						if($url){
							$url = $url."&level=Customer";
						}else{
							$url = "level=Customer";
						}
					}
					if($card['card_name'] == 'Total Admin'){
						$value = $value->where('level', 'Admin');
						if($url){
							$url = $url."&level=Admin";
						}else{
							$url = "level=Admin";
						}
					}
					if(strpos($card['card_name'], 'Super Admin') !== false){
						$value =$value->where('level', 'Super Admin');
						if($url){
							$url = $url."&level=Super Admin";
						}else{
							$url = "level=Super Admin";
						}
					}
					if(strpos($card['card_name'], 'New') !== false){
						$value = $value->whereDate('created_at', '>=', $start);
						if($url){
							$url = $url.'&regis_date_start='.$start.'&regis_date_end='.$end;
						}else{
							$url = 'regis_date_start='.$start.'&regis_date_end='.$end;
						}
					}
					if(strpos($card['card_name'], 'Male') !== false){
						$value = $value->where('gender', '=', 'Male');
						if($url){
							$url = $url.'&gender=male';
						}else{
							$url = 'gender=male';
						}
					}
					if(strpos($card['card_name'], 'Female') !== false){
						$value = $value->where('gender', '=', 'Female');
						if($url){
							$url = $url.'&gender=female';
						}else{
							$url = 'gender=female';
						}
					}
					if(strpos($card['card_name'], 'Verified') !== false){
						$value = $value->where('phone_verified', '=', '1');
					}
					if(strpos($card['card_name'], 'Not Verified') !== false){
						$value = $value->where('phone_verified', '=', '0');
					}
					if(strpos($card['card_name'], 'Android') !== false){
						$value = $value->where('android_device', '!=', '');
						if($url){
							$url = $url.'&device=android';
						}else{
							$url = 'device=android';
						}
					}
					if(strpos($card['card_name'], 'IOS') !== false){
						$value = $value->where('ios_device', '!=', '');
						if($url){
							$url = $url.'&device=ios';
						}else{
							$url = 'device=ios';
						}
					}
					if(strpos($card['card_name'], 'Subscribed') !== false){
						$value = $value->where('email_unsubscribed', '=', '0');
					}
					if(strpos($card['card_name'], 'Unsubscribed') !== false){
						$value = $value->where('email_unsubscribed', '=', '1');
					}
					$value = $value->count();
				}
				
				elseif(strpos($card['card_name'], 'Transaction') !== false){
					$value = DailyReportTrx::whereDate('trx_date', '<=', $end)->whereDate('trx_date', '>=', $start);

					if($card['card_name'] == 'Total Transaction Count'){
						$value = $value->sum('trx_count');
					}
					if($card['card_name'] == 'Total Transaction Value'){
						$value = $value->sum('trx_grand');
					}
					if($card['card_name'] == 'Transaction Average'){
						$sum = $value->sum('trx_grand');
						$count = $value->sum('trx_count');
						if($sum > 0 && $count > 0){
							$value = (int) $sum/$count;
						}else{
							$value = 0;
						}
					}
					if($card['card_name'] == 'Transaction Average per Day'){
						$sum = $value->sum('trx_grand');
						$count = $value->get()->groupBy('trx_date')->count();
						if($sum > 0 && $count > 0){
							$value = (int) $sum/$count;
						}else{
							$value = 0;
						}
					}
					$url = 'date_start='.$start.'&date_end='.$end;
				}

				$dashboard[$key]['dashboard_card'][$index]['value'] = $value;
				$dashboard[$key]['dashboard_card'][$index]['url'] = $url;
			}
		}
		
		$result = ['dashboard' => $dashboard, 'daterange' => $dateRange['default_date_range']];

		return response()->json(MyHelper::checkGet($result));
	}

}
