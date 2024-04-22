@extends('layouts.user')

@push('css')

@endpush

@section('contents')
<div class="breadcrumb-area">
    <h3 class="title">@lang('BLOCK.IO')</h3>
    <ul class="breadcrumb">
        <li>
            <a href="#">@lang('Deposit')</a>
        </li>
        <li>
            @lang('BLOCK.IO Deposit')
        </li>
    </ul>
</div>


<div class="dashboard--content-item">
    <div class="row">
        <div class="col-lg-12">
            <div class="order-details-box">
                <div class="header">
                    <h4 class="title text-center">
                        @lang('Block.io') ({{ Session::get('coin') }}) @lang('Deposit Information')
                    </h4>
                </div>

                <div class="row justify-content-center px-4 py-5">
                    <div class="col-lg-10 col-xxl-8">
                        <div class="card default--card">
                            <div class="card-body">
                                <div class="content">
                                    <div class="panel-body text-center verify-success">
                                        <img src="{{ Session::get('qrcode_url') }}" class="mb-4">
                                        <h4 class="text-center mb-4">Address: {{ Session::get('address') }}</h4>
                                        <p>Please send approximately <b>{{ Session::get('amount') }}</b> {{ Session::get('coin') }} to this address. After completing your payment, <b>{{ Session::get('currency_sign') }}{{ Session::get('currency_value') }}</b> invest will be deposited. <br>This Process may take some time for confirmations. Thank you.</p>

                                        <a href="javascript:history.back();" class="cmn--btn">Go Back</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')

@endpush
