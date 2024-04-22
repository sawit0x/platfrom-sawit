<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PayeerController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(OrderRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
        $this->payment = PaymentGateway::whereKeyword('payeer')->first();
        $this->paydata = $this->payment->convertAutoData();
    }

    public function store(Request $request){
        $gs = Generalsetting::findOrFail(1);
        $invest_name = $gs->title." Invest";
        $invest_number = Str::random(4).time();

        $invest = Invest::findOrFail($request->invest_id);

        Session::put('method',$request->method);
        Session::put('invest_id',$request->invest_id);

        $arHash = [
            trim($this->paydata['merchant_id']),
            $invest->transaction_no,
            $request->amount,
            $request->currency_code,
            base64_encode("Pay To $gs->title"),
            trim($this->paydata['secret_key'])
        ];

        $info['m_shop'] = trim($this->paydata['merchant_id']);
        $info['m_orderid'] = $invest->transaction_no;
        $info['m_amount'] = $request->amount;
        $info['m_curr'] = $request->currency_code;
        $info['m_desc'] = base64_encode("Pay To $gs->title");
        $info['m_sign'] = strtoupper(hash('sha256', implode(":", $arHash)));
        $info['lang'] = 'en';

        $data['info'] = $info;
        $data['method'] = "GET";
        $data['url'] = "https://payeer.com/merchant";


        return view('payment.redirect',compact('data'));
    }

    public function notify(Request $request)
    {
        $method = Session::get('method');
        $invest_id = Session::get('invest_id');

        $invest = Invest::findOrFail($invest_id);

        if (isset($request->m_operation_id) && isset($request->m_sign)) {
            $sign_hash = strtoupper(hash('sha256', implode(":", array(
                $request->m_operation_id,
                $request->m_operation_ps,
                $request->m_operation_date,
                $request->m_operation_pay_date,
                $request->m_shop,
                $request->m_orderid,
                $request->m_amount,
                $request->m_curr,
                $request->m_desc,
                $request->m_status,
                $this->paydata['secret_key']
            ))));

            if ($request->m_sign != $sign_hash) {
                return redirect()->route('api.user.invest.checkout',$invest->id)->with('unsuccess','digital signature not matched!');
            }
            else {
                $invest = Invest::where('transaction_no', $request->m_orderid)->first();
                if ($request->m_amount == round($invest->amount,2) && $request->m_status == 'success' && $invest->status == 0) {
                    $addionalData = ['status'=>'running','method'=>$method];
                    $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

                    return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest successfully complete.');
                } else {
                    return redirect()->route('api.user.invest.checkout',$invest->id)->with('unsuccess','transaction was unsuccessful!');
                }
            }
        } else {
            return redirect()->route('api.user.invest.checkout',$invest->id)->with('unsuccess','transaction was unsuccessful!');
        }
    }
}
