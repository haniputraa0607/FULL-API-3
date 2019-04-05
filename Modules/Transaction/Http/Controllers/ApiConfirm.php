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
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentBalance;

use DB;
use App\Lib\MyHelper;
use App\Lib\Midtrans;

use Modules\Transaction\Http\Requests\Transaction\ConfirmPayment;

class ApiConfirm extends Controller
{
    public $saveImage = "img/payment/manual/";

    public function confirmTransaction(ConfirmPayment $request) {
        DB::beginTransaction();
        $post = $request->json()->all();
        $user = User::where('id', $request->user()->id)->first();

        $productMidtrans = [];
        $dataDetailProduct = [];

        $check = Transaction::with('transaction_shipments', 'productTransaction.product')->where('transaction_receipt_number', $post['id'])->first();

        if (empty($check)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Not Found']
            ]);
        }

        if ($check['transaction_payment_status'] != 'Pending') {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Invalid']
            ]);
        }

        $checkPayment = TransactionMultiplePayment::where('id_transaction', $check['id_transaction'])->first();
        $countGrandTotal = $check['transaction_grandtotal'];

        if (isset($check['productTransaction'])) {
            foreach ($check['productTransaction'] as $key => $value) {
                $dataProductMidtrans = [
                    'id'       => $value['id_product'],
                    'price'    => abs($value['transaction_product_price']),
                    'name'     => $value['product']['product_name'],
                    'quantity' => $value['transaction_product_qty'],
                ];

                array_push($productMidtrans, $dataProductMidtrans);
                array_push($dataDetailProduct, $dataProductMidtrans);
            }
        }

        if ($check['transaction_shipment'] > 0) {
            $dataShip = [
                'id'       => null,
                'price'    => abs($check['transaction_shipment']),
                'name'     => 'Shipping',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataShip);
        }
        
        if ($check['transaction_service'] > 0) {
            $dataService = [
                'id'       => null,
                'price'    => abs($check['transaction_service']),
                'name'     => 'Service',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataService);
        }

        if ($check['transaction_tax'] > 0) {
            $dataTax = [
                'id'       => null,
                'price'    => abs($check['transaction_tax']),
                'name'     => 'Tax',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataTax);
        }

        if ($check['transaction_discount'] > 0) {
            $dataDis = [
                'id'       => null,
                'price'    => -abs($check['transaction_discount']),
                'name'     => 'Discount',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataDis);
        }

        $detailPayment = [
            'subtotal' => $check['transaction_subtotal'],
            'shipping' => $check['transaction_shipment'],
            'tax'      => $check['transaction_tax'],
            'service'  => $check['transaction_service'],
            'discount' => -$check['transaction_discount'],
        ];

        if (!empty($checkPayment)) {
            if ($checkPayment['type'] == 'Balance') {
                $checkPaymentBalance = TransactionPaymentBalance::where('id_transaction', $check['id_transaction'])->first();
                if (empty($checkPaymentBalance)) {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Transaction is invalid']
                    ]);
                }

                $countGrandTotal = $countGrandTotal - $checkPaymentBalance['balance_nominal'];
                $dataBalance = [
                    'id'       => null,
                    'price'    => -abs($checkPaymentBalance['balance_nominal']),
                    'name'     => 'Balance',
                    'quantity' => 1,
                ];

                array_push($dataDetailProduct, $dataBalance);

                $detailPayment['balance'] = -$checkPaymentBalance['balance_nominal'];
            }
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

        if ($post['payment_type'] == 'Midtrans') {
            $transaction_details = array(
                'order_id'      => $check['transaction_receipt_number'],
                'gross_amount'  => $countGrandTotal
            );

            if ($check['trasaction_type'] == 'Delivery') {
                $dataMidtrans = array(
                    'transaction_details' => $transaction_details,
                    'customer_details'    => $dataUser,
                    'shipping_address'    => $dataShipping
                );

                $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $countGrandTotal, $dataUser, $dataShipping, $dataDetailProduct);
            } else {
                $dataMidtrans = array(
                    'transaction_details' => $transaction_details,
                    'customer_details'    => $dataUser,
                );

                $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $countGrandTotal, $dataUser, $ship=null, $dataDetailProduct);
            }

            if (empty($connectMidtrans['token'])) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => [
                        'Midtrans token is empty. Please try again.'
                    ],
                    'error' => [$connectMidtrans],
                    'data' => [
                        'trx'         => $transaction_details,
                        'grand_total' => $countGrandTotal,
                        'product'     => $dataDetailProduct,
                        'user'        => $dataUser
                    ]
                ]);
            }

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
                    ],
                    'data' => [$connectMidtrans]
                ]);
            }

            $dataMultiple = [
                'id_transaction' => $check['id_transaction'],
                'type'           => 'Midtrans',
                'id_payment'     => $insertNotifMidtrans['id_transaction_payment']
            ];

            $saveMultiple = TransactionMultiplePayment::create($dataMultiple);
            if (!$saveMultiple) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail to confirm transaction']
                ]);
            }

            $dataMidtrans['items']   = $productMidtrans;
            $dataMidtrans['payment'] = $detailPayment;
            $dataMidtrans['midtrans_product'] = $dataDetailProduct;

            // $update = Transaction::where('transaction_receipt_number', $post['id'])->update(['trasaction_payment_type' => $post['payment_type']]);

            // if (!$update) {
            //     DB::rollback();
            //     return response()->json([
            //         'status'    => 'fail',
            //         'messages'  => [
            //             'Payment Midtrans Invalid.'
            //         ],
            //         'data' => [$connectMidtrans]
            //     ]);
            // }

            DB::commit();
            return response()->json([
                'status'           => 'success',
                'snap_token'       => $connectMidtrans['token'],
                'redirect_url'     => $connectMidtrans['redirect_url'],
                'transaction_data' => $dataMidtrans,
            ]);
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

            DB::commit();
            return response()->json([
                'status' => 'success',
                'result' => $check
            ]);
            
        }
    }
}
