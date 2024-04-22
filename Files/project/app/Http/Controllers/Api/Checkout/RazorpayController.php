<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Session;

class RazorpayController extends Controller
{
    public $orderRepositorty;

    public function __construct(OrderRepository $orderRepositorty)
    {
        $data = PaymentGateway::whereKeyword('razorpay')->first();
        $paydata = $data->convertAutoData();
        $this->keyId = $paydata['key'];
        $this->keySecret = $paydata['secret'];
        $this->displayCurrency = 'INR';
        $this->api = new Api($this->keyId, $this->keySecret);

        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request)
    {
        if($request->currency_code != "INR")
        {
            return redirect()->back()->with('unsuccess','Please Select INR Currency For Rezorpay.');
        }

        $settings = Generalsetting::findOrFail(1);
        $invest = Invest::findOrFail($request->invest_id);
        $item_name = $settings->title." Invest";

        $order['item_name'] = $item_name;
        $order['item_number'] = $invest->transaction_no;
        $order['item_amount'] = round($request->amount,2);

        $cancel_url = route('api.checkout.razorpay.cancel');
        $notify_url = route('api.checkout.razorpay.notify');

        $orderData = [
            'receipt'         => $order['item_number'],
            'amount'          => $order['item_amount'] * 100,
            'currency'        => 'INR',
            'payment_capture' => 1
        ];

        $razorpayOrder = $this->api->order->create($orderData);
        $input['user_id'] = $request->user_id;

        Session::put('method',$request->method);
        Session::put('invest_id',$request->invest_id);
        Session::put('order_data',$order);
        Session::put('order_payment_id', $razorpayOrder['id']);

        $displayAmount = $amount = $orderData['amount'];

        if ($this->displayCurrency !== 'INR')
        {
            $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);

            $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
        }

        $checkout = 'automatic';

        if (isset($_GET['checkout']) and in_array($_GET['checkout'], ['automatic', 'manual'], true))
        {
            $checkout = $_GET['checkout'];
        }

        $data = [
            "key"               => $this->keyId,
            "amount"            => $amount,
            "name"              => $order['item_name'],
            "description"       => $order['item_name'],
            "prefill"           => [
                "name"              => $request->customer_name,
                "email"             => $request->customer_email,
                "contact"           => $request->customer_phone,
            ],
            "notes"             => [
                "address"           => $request->customer_address,
                "merchant_order_id" => $order['item_number'],
            ],
            "theme"             => [
                "color"             => "{{$settings->colors}}"
            ],
            "order_id"          => $razorpayOrder['id'],
        ];

        if ($this->displayCurrency !== 'INR')
        {
            $data['display_currency']  = $this->displayCurrency;
            $data['display_amount']    = $displayAmount;
        }

        $json = json_encode($data);
        $displayCurrency = $this->displayCurrency;

        return view( 'frontend.razorpay-checkout', compact( 'data','displayCurrency','json','notify_url' ) );
    }

    public function notify(Request $request)
    {
        $input_data = $request->all();
        $payment_id = Session::get('order_payment_id');
        $input = Session::get('request_data');

        $success = true;

        if (empty($input_data['razorpay_payment_id']) === false)
        {
            try
            {
                $attributes = array(
                    'razorpay_order_id' => $payment_id,
                    'razorpay_payment_id' => $input_data['razorpay_payment_id'],
                    'razorpay_signature' => $input_data['razorpay_signature']
                );

                $this->api->utility->verifyPaymentSignature($attributes);
            }
            catch(SignatureVerificationError $e)
            {
                $success = false;
            }
        }

        $method = Session::get('method');
        $invest_id = Session::get('invest_id');

        if ($success === true){
            $invest = Invest::findOrFail($invest_id);
            $addionalData = ['txnid' => $payment_id, 'status'=>'running','method'=>$method];

            $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

            return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest successfully complete.');
        }
        return redirect()->route('api.user.invest.checkout',$invest->id)->with('warning','Payment Cancelled!');
    }
}
