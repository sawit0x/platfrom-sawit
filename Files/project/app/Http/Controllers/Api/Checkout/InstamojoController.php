<?php

namespace App\Http\Controllers\Api\Checkout;

use Illuminate\Support\Facades\Session;
use App\Repositories\OrderRepository;
use App\Http\Controllers\Controller;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Classes\Instamojo;
use App\Models\Currency;
use App\Models\Invest;
use App\Models\User;

class InstamojoController extends Controller
{
    public $orderRepositorty;

    public function __construct(OrderRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request)
    {
        $data = PaymentGateway::whereKeyword('instamojo')->first();
        $invest = Invest::findOrFail($request->invest_id);
        $user = User::findOrFail($invest->user_id);

        $gs = Generalsetting::first();
        $total =  $request->amount;

        $paydata = $data->convertAutoData();
        if($request->currency_code != "INR")
        {
            return redirect()->back()->with('unsuccess',__('Please Select INR Currency For This Payment.'));
        }

        $order['item_name'] = $gs->title." Order";
        $order['item_number'] = $invest->transaction_no;
        $order['item_amount'] = $total;
        $cancel_url = route('api.checkout.instamojo.cancel');
        $notify_url = route('api.checkout.instamojo.notify');

        if($paydata['sandbox_check'] == 1){
            $api = new Instamojo($paydata['key'], $paydata['token'], 'https://test.instamojo.com/api/1.1/');
        }
        else {
            $api = new Instamojo($paydata['key'], $paydata['token']);
        }

        try {
            $response = $api->paymentRequestCreate(array(
                "purpose" => $order['item_name'],
                "amount" => $order['item_amount'],
                "send_email" => true,
                "email" => $user->email,
                "redirect_url" => $notify_url
            ));
            $redirect_url = $response['longurl'];

            Session::put('method',$request->method);
            Session::put('invest_id',$request->invest_id);

            Session::put('order_data',$order);
            Session::put('order_payment_id', $response['id']);

            return redirect($redirect_url);

        }
        catch (Exception $e) {
            return redirect($cancel_url)->with('unsuccess','Error: ' . $e->getMessage());
        }
    }

    public function notify(Request $request)
    {
        $input_data = $request->all();

        $payment_id = Session::get('order_payment_id');
        $method = Session::get('method');
        $invest_id = Session::get('invest_id');

        $invest = Invest::findOrFail($invest_id);

        if($input_data['payment_status'] == 'Failed'){
            return redirect()->route('api.user.invest.checkout',$invest->id)->with('error','Something went wrong!');
        }

        if ($input_data['payment_request_id'] == $payment_id) {

            $addionalData = ['txnid' => $payment_id, 'status'=>'running','method'=>$method];
            $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

            return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest successfully complete.');

        }
        return redirect()->route('api.user.invest.checkout',$invest->id)->with('warning','Something went wrong!');
    }

    public function cancel(){
        return redirect()->route('user.invest.checkout')->with('warning','Something went wrong!');
    }
}
