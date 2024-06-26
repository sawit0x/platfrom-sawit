<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Validator;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizeController extends Controller
{
    public $orderRepositorty;

    public function __construct(OrderRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request){
        $support = ['USD'];
        if(!in_array($request->currency_code,$support)){
            return redirect()->back()->with('warning','Please Select USD Currency For Authorize.');
        }

        $settings = Generalsetting::find(1);

        $authorizeinfo    = PaymentGateway::whereKeyword('authorize.net')->first();
        $authorizesettings= $authorizeinfo->convertAutoData();

        $invest = Invest::findOrFail($request->invest_id);
        $item_name = $settings->title." Order";
        $item_number = $invest->transaction_no;
        $item_amount = $request->amount;


        $validator = Validator::make($request->all(),[
            'cardNumber' => 'required',
            'cardCVC' => 'required',
            'month' => 'required',
            'year' => 'required',
        ]);

        if ($validator->passes()) {
            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($authorizesettings['login_id']);
            $merchantAuthentication->setTransactionKey($authorizesettings['txn_key']);

            $refId = 'ref' . time();

            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber(str_replace(' ','',$request->cardNumber));
            $year = $request->year;
            $month = $request->month;
            $creditCard->setExpirationDate($year.'-'.$month);
            $creditCard->setCardCode($request->cardCVC);

            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            $orderr = new AnetAPI\OrderType();
            $orderr->setInvoiceNumber($item_number);
            $orderr->setDescription($item_name);

            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($item_amount);
            $transactionRequestType->setOrder($orderr);
            $transactionRequestType->setPayment($paymentOne);

            $requestt = new AnetAPI\CreateTransactionRequest();
            $requestt->setMerchantAuthentication($merchantAuthentication);
            $requestt->setRefId($refId);
            $requestt->setTransactionRequest($transactionRequestType);

            $controller = new AnetController\CreateTransactionController($requestt);
            if($authorizesettings['sandbox_check'] == 1){
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
            }
            else {
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
            }


            if ($response != null) {
                if ($response->getMessages()->getResultCode() == "Ok") {
                    $tresponse = $response->getTransactionResponse();


                    if ($tresponse != null && $tresponse->getMessages() != null) {
                        $addionalData = ['txnid'=>$tresponse->getTransId(), 'status'=>'running', 'method' => $request->method];
                        $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

                        return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest Successfully Complete.');

                    } else {
                        return redirect()->route('api.user.invest.checkout',$invest->id)->with('warning', 'Payment Failed.');
                    }
                } else {
                    return redirect()->route('api.user.invest.checkout',$invest->id)->with('warning', 'Payment Failed.');
                }
            } else {
                return redirect()->route('api.user.invest.checkout',$invest->id)->with('warning', 'Payment Failed.');
            }

        }
        return redirect()->route('api.user.invest.checkout',$invest->id)->with('warning', 'Invalid Payment Details.');
    }
}
