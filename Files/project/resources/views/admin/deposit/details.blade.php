@extends('layouts.load')
@section('content')

                        <div class="content-area no-padding">
                            <div class="add-product-content">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="product-description">
                                            <div class="body-area" id="modalEdit">
                                                <div class="table-responsive show-table">
                                                    <table class="table">
                                                        <tr>
                                                            <th>{{ __("User ID#") }}</th>
                                                            <td>{{$deposit->user->id}}</td>
                                                        </tr>

                                                        <tr>
                                                            <th>{{ __("User Name") }}</th>
                                                            <td>
                                                                <a href="{{route('admin-user-show',$deposit->user->id)}}" target="_blank">{{$deposit->user->name}}</a>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <th>{{ __("Deposit Number") }}</th>
                                                            <td>{{ $deposit->deposit_number }}</td>
                                                        </tr>

                                                        @if ($deposit->method == 'Manual')
                                                            <tr>
                                                                <th>{{ __("Transaction ID/Number") }}</th>
                                                                <td>{{$deposit->txnid}}</td>
                                                            </tr>
                                                            
                                                        @endif
                                                        <tr>
                                                            <th>{{ __("Deposit Number") }}</th>
                                                            <td>{{ $deposit->deposit_number }}</td>
                                                        </tr>

                                                        <tr>
                                                            <th>{{ __("Deposit Amount") }}</th>
                                                            <td>${{ round($deposit->amount, 2) }}</td>
                                                        </tr>
        
                                                        <tr>
                                                            <th>{{ __("Deposit Process Date") }}</th>
                                                            <td>{{date('d-M-Y',strtotime($deposit->created_at))}}</td>
                                                        </tr>

                                                        <tr>
                                                            <th>{{ __("Deposit Status") }}</th>
                                                            <td>{{ucfirst($deposit->status)}}</td>
                                                        </tr>

                                                        <tr>
                                                            <th>{{ __("User Email") }}</th>
                                                            <td>{{$deposit->user->email}}</td>
                                                        </tr>

                                                        <tr>
                                                            <th>{{ __("User Phone") }}</th>
                                                            <td>{{$deposit->user->phone}}</td>
                                                        </tr>

                                                        <tr>
                                                            <th>{{ __("Withdraw Method") }}</th>
                                                            <td>{{$deposit->method}}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

@endsection
