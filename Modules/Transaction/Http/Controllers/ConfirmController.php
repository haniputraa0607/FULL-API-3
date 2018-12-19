<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\LogTopup;
use App\Http\Models\LogTopupMidtrans;
use App\Http\Models\LogTopupManual;
use App\Http\Models\Transaction;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\TransactionPaymentMidtran;

use DB;
use App\Lib\MyHelper;
use App\Lib\Midtrans;

use Modules\Transaction\Http\Requests\Transaction\ConfirmPayment;

class ConfirmController extends Controller
{
    public $saveImage = "img/payment/manual/";

    public function confirmTransaction(ConfirmPayment $request) {
        DB::beginTransaction();
        $post = $request->json()->all();
        $user = User::where('id', $request->user()->id)->first();

        $productMidtrans = [];
        $dataDetailProduct = [];

        $check = Transaction::with('transaction_shipments', 'productTransaction.product', 'logTopup')->where('transaction_receipt_number', $post['id'])->first();

        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Not Found']
            ]);
        }

        if ($check['transaction_payment_status'] != 'Pending') {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Invalid']
            ]);
        }

        if ($check['trasaction_type'] == 'Delivery') {
            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name'  => $check['transaction_shipments']['destination_name'],
                    'phone'       => $check['transaction_shipments']['destination_phone'],
                    'address'     => $check['transaction_shipments']['destination_address']
                ],
            ];

            $dataShipping = [
                'first_name'  => $check['transaction_shipments']['name'],
                'phone'       => $check['transaction_shipments']['phone'],
                'address'     => $check['transaction_shipments']['address'],
                'postal_code' => $check['transaction_shipments']['postal_code']
            ];
        } else {
            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name'  => $user['name'],
                    'phone'       => $user['phone']
                ],
            ];
        }

        if (isset($check['productTransaction'])) {
            foreach ($check['productTransaction'] as $key => $value) {
                $dataProductMidtrans = [
                    'id'       => $value['id_product'],
                    'price'    => $value['transaction_product_price'],
                    'name'     => $value['product']['product_name'],
                    'quantity' => $value['transaction_product_qty'],
                ];

                array_push($productMidtrans, $dataProductMidtrans);
            }
        }

        array_push($dataDetailProduct, $productMidtrans);

        $dataShip = [
            'id'       => null,
            'price'    => $check['transaction_shipment'],
            'name'     => 'Shipping',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataShip);

        $dataService = [
            'id'       => null,
            'price'    => $check['transaction_service'],
            'name'     => 'Service',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataService);

        $dataTax = [
            'id'       => null,
            'price'    => $check['transaction_tax'],
            'name'     => 'Tax',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataTax);

        $dataDis = [
            'id'       => null,
            'price'    => -$check['transaction_discount'],
            'name'     => 'Discount',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataDis);

        $detailPayment = [
            'subtotal' => $check['transaction_subtotal'],
            'shipping' => $check['transaction_shipment'],
            'tax'      => $check['transaction_tax'],
            'service'  => $check['transaction_service'],
            'discount' => -$check['transaction_discount'],
        ];

        if (isset($check['logTopup'])) {
            $detailPayment['balance'] = -$check['logTopup']['balance_before'];
        } else {
            $detailPayment['balance'] = 0;
        }

        if ($post['payment_type'] == 'Midtrans') {
            if (empty($check['logTopup'])) {
                $transaction_details = array(
                    'order_id'      => $check['transaction_receipt_number'],
                    'gross_amount'  => $check['transaction_grandtotal']
                );
            } else {
                $transaction_details = array(
                    'order_id'      => $check['transaction_receipt_number'],
                    'gross_amount'  => $check['logTopup']['topup_value']
                );
            }

            if ($check['trasaction_type'] == 'Delivery') {
                $dataMidtrans = array(
                    'transaction_details' => $transaction_details,
                    'customer_details'    => $dataUser,
                    'shipping_address'    => $dataShipping
                );

                if (empty($check['logTopup'])) {
                    $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $check['transaction_grandtotal'], $dataUser, $dataShipping);
                } else {
                    $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $check['logTopup']['topup_value'], $dataUser, $dataShipping);
                }
                

            } else {
                $dataMidtrans = array(
                    'transaction_details' => $transaction_details,
                    'customer_details'    => $dataUser,
                );

                if (empty($check['logTopup'])) {
                    $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $check['transaction_grandtotal'], $dataUser);
                } else {
                    $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $check['logTopup']['topup_value'], $dataUser);
                }
            }

            if (empty($connectMidtrans['token'])) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => [
                        'Midtrans token is empty. Please try again.'
                    ]
                ]);
            } else {
                if (empty($check['logTopup'])) {
                    $dataNotifMidtrans = [
                        'id_transaction' => $check['id_transaction'],
                        'gross_amount'   => $check['transaction_grandtotal'],
                        'order_id'       => $check['transaction_receipt_number']
                    ];

                    $insertNotifMidtrans = TransactionPaymentMidtran::create($dataNotifMidtrans);
                    if (!$insertNotifMidtrans) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => [
                                'Payment Midtrans Failed.'
                            ]
                        ]);
                    }

                    $dataMidtrans['items']   = $productMidtrans;
                    $dataMidtrans['payment'] = $detailPayment;

                    $update = Transaction::where('transaction_receipt_number', $post['id'])->update(['trasaction_payment_type' => $post['payment_type']]);

                    if (!$update) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => [
                                'Payment Midtrans Failed.'
                            ]
                        ]);
                    }
                } else {
                    $dataNotifMidtrans = [
                        'id_log_topup' => $check['logTopup']['id_log_topup'],
                        'gross_amount' => $check['logTopup']['topup_value'],
                        'order_id'     => $check['transaction_receipt_number']
                    ];

                    $insertNotifMidtrans = LogTopupMidtrans::create($dataNotifMidtrans);
                    if (!$insertNotifMidtrans) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => [
                                'Payment Midtrans Failed.'
                            ]
                        ]);
                    }

                    $dataMidtrans['items']   = $productMidtrans;
                    $dataMidtrans['payment'] = $detailPayment;
                }
                

                DB::commit();
                return response()->json([
                    'status'           => 'success',
                    'snap_token'       => $connectMidtrans['token'],
                    'transaction_data' => $dataMidtrans,
                ]);
            }
        } else {
            if (empty($check['logTopup'])) {
                if (isset($post['id_manual_payment_method'])) {
                    $checkPaymentMethod = ManualPaymentMethod::where('id_manual_payment_method', $post['id_manual_payment_method'])->first();
                    if (empty($checkPaymentMethod)) {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Payment Method Not Found']
                        ]);
                    }
                }
                
                if (isset($post['payment_receipt_image'])) {
                    if (!file_exists($this->saveImage)) {
                        mkdir($this->saveImage, 0777, true);
                    }

                    $save = MyHelper::uploadPhotoStrict($post['payment_receipt_image'], $this->saveImage, 300, 300);

                    if (isset($save['status']) && $save['status'] == "success") {
                        $post['payment_receipt_image'] = $save['path'];
                    }
                    else {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['fail upload image']
                        ]);
                    }
                } else {
                    $post['payment_receipt_image'] = null;
                }

                $dataManual = [
                    'id_transaction'           => $check['id_transaction'],
                    'payment_date'             => $post['payment_date'],
                    'id_bank_method'           => $post['id_bank_method'],
                    'id_bank'                  => $post['id_bank'],
                    'id_manual_payment'        => $post['id_manual_payment'],
                    'payment_time'             => $post['payment_time'],
                    'payment_bank'             => $post['payment_bank'],
                    'payment_method'           => $post['payment_method'],
                    'payment_account_number'   => $post['payment_account_number'],
                    'payment_account_name'     => $post['payment_account_name'],
                    'payment_nominal'          => $check['transaction_grandtotal'],
                    'payment_receipt_image'    => $post['payment_receipt_image'],
                    'payment_note'             => $post['payment_note']
                ];

                $insertPayment = MyHelper::manualPayment($dataManual, 'transaction');
                if (isset($insertPayment) && $insertPayment == 'success') {
                    $update = Transaction::where('transaction_receipt_number', $post['id'])->update(['transaction_payment_status' => 'Paid', 'trasaction_payment_type' => $post['payment_type']]);

                    if (!$update) {
                        DB::rollback();
                        return response()->json([
                            'status' => 'fail',
                            'messages' => ['Transaction Failed']
                        ]);
                    }

                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'result' => $check
                    ]);
                } elseif (isset($insertPayment) && $insertPayment == 'fail') {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Transaction Failed']
                    ]);
                } else {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Transaction Failed']
                    ]);
                }
            } else {
                if (isset($post['id_manual_payment_method'])) {
                    $checkPaymentMethod = ManualPaymentMethod::where('id_manual_payment_method', $post['id_manual_payment_method'])->first();
                    if (empty($checkPaymentMethod)) {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Payment Method Not Found']
                        ]);
                    }
                }
                
                if (isset($post['payment_receipt_image'])) {
                    if (!file_exists($this->saveImage)) {
                        mkdir($this->saveImage, 0777, true);
                    }

                    $save = MyHelper::uploadPhotoStrict($post['payment_receipt_image'], $this->saveImage, 300, 300);

                    if (isset($save['status']) && $save['status'] == "success") {
                        $post['payment_receipt_image'] = $save['path'];
                    }
                    else {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['fail upload image']
                        ]);
                    }
                } else {
                    $post['payment_receipt_image'] = null;
                }

                $dataManual = [
                    'id_log_topup'             => $check['logTopup']['id_log_topup'],
                    'payment_date'             => $post['payment_date'],
                    'id_bank_method'           => $post['id_bank_method'],
                    'id_bank'                  => $post['id_bank'],
                    'id_manual_payment'        => $post['id_manual_payment'],
                    'payment_time'             => $post['payment_time'],
                    'payment_bank'             => $post['payment_bank'],
                    'payment_method'           => $post['payment_method'],
                    'payment_account_number'   => $post['payment_account_number'],
                    'payment_account_name'     => $post['payment_account_name'],
                    'payment_nominal'          => $check['transaction_grandtotal'],
                    'payment_receipt_image'    => $post['payment_receipt_image'],
                    'payment_note'             => $post['payment_note']
                ];

                $insertPayment = MyHelper::manualPayment($dataManual, 'logtopup');
                if (isset($insertPayment) && $insertPayment == 'success') {
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'result' => $check
                    ]);
                } elseif (isset($insertPayment) && $insertPayment == 'fail') {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Transaction Failed']
                    ]);
                } else {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Transaction Failed 2']
                    ]);
                }
            }
            
        }
    }
}
