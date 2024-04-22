<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Repositories\OrderRepository;
use Illuminate\Support\Str;

class PaystackController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(OrderRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request){
        if($request->currency_code != "NGN")
        {
            return redirect()->back()->with('unsuccess','Please Select NGN Currency For Paystack.');
        }

        $invest = Invest::findOrFail($request->invest_id);
        $addionalData = ['txnid'=>$request->paystack_txn, 'status'=>'running', 'method' => $request->method];
        $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

        return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest successfully complete.');
    }
}
